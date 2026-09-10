# Referencia de APIs

Documentación completa de las APIs que expone y consume la plataforma de envío de SMS.

Generada a partir del código en `routes/api.php`, `app/Http/Controllers/`, `app/Http/Middleware/`, `app/Enums/` y `app/Services/Channels/`.

## Índice

- [1. Mapa de APIs](#1-mapa-de-apis)
- [2. Plano desarrollador — /api/v1](#2-plano-desarrollador--apiv1)
- [3. Plano gateway — /api/v1/gateway](#3-plano-gateway--apiv1gateway)
- [4. Rutas legacy](#4-rutas-legacy)
- [5. Ciclo de vida de un mensaje](#5-ciclo-de-vida-de-un-mensaje)
- [6. API externa consumida — 51x.dev](#6-api-externa-consumida--51xdev)
- [7. Códigos de error](#7-códigos-de-error)
- [8. Ejemplos de integración](#8-ejemplos-de-integración)

---

## 1. Mapa de APIs

El sistema expone **tres planos de API** con mecanismos de autenticación distintos, y **consume una API externa** para los canales de tipo *push*.

| Plano | Prefijo | Autenticación | Consumidor | Formato |
|---|---|---|---|---|
| [Desarrollador](#2-plano-desarrollador--apiv1) | `/api/v1` | Token Sanctum (`Authorization: Bearer`) | Clientes/integradores del SaaS | JSON |
| [Gateway](#3-plano-gateway--apiv1gateway) | `/api/v1/gateway` | Clave de canal (`X-CHANNEL-KEY`) | Dispositivos ESP32 y gateways *pull* | Texto plano + JSON |
| [Legacy](#4-rutas-legacy) | `/api` | Token global (`X-API-TOKEN`) | ESP32 ya desplegado (compatibilidad) | Texto plano + JSON |
| [51x.dev](#6-api-externa-consumida--51xdev) | *(externo)* | `X-API-Key` | Lo llama Laravel | JSON |

Todas las rutas cuelgan de la URL base de la instalación, por ejemplo `https://tu-dominio.com`.

Existe además una página pública de documentación del plano desarrollador en `/docs` (`resources/views/docs.blade.php`), y un endpoint de salud `GET /up` (Laravel health check, sin autenticación).

### Inventario completo de rutas

```
POST        api/mensaje/crear                        MensajeController@crear
GET|POST    api/mensaje/{id}/procesado               MensajeController@marcarComoProcesado
GET         api/mensajes/status/{msg_id}             MensajeController@status
GET         api/pendientes                           MensajeController@pendientes
GET         api/user                                 closure (auth:sanctum)
GET|POST    api/v1/gateway/messages/{id}/enviado     Api\V1\GatewayController@enviado
GET|POST    api/v1/gateway/messages/{id}/error       Api\V1\GatewayController@error
GET         api/v1/gateway/pendientes                Api\V1\GatewayController@pendientes
GET         api/v1/messages                          Api\V1\MessageController@index
POST        api/v1/messages                          Api\V1\MessageController@store
POST        api/v1/messages/bulk                     Api\V1\MessageController@bulk
GET         api/v1/messages/{msg_id}                 Api\V1\MessageController@show
DELETE      api/v1/messages/{msg_id}                 Api\V1\MessageController@cancel
GET         user/api-tokens                          Jetstream (gestión de tokens, web)
GET         up                                       Health check
```

> Las rutas de `/api` **no tienen rate limiting** aplicado: `bootstrap/app.php` no invoca `->throttleApi()`. Tampoco tienen nombre (`->name()`).

---

## 2. Plano desarrollador — /api/v1

API principal para integraciones. Autenticada con **tokens personales de Laravel Sanctum** (middleware `auth:sanctum`).

### Autenticación

1. Inicia sesión en el panel.
2. Ve a **Perfil → API Tokens** (`/user/api-tokens`).
3. Crea un token y cópialo: solo se muestra una vez.

Envía el token en cada petición:

```
Authorization: Bearer TU_TOKEN
Accept: application/json
Content-Type: application/json
```

Sin token válido, Sanctum responde `401 Unauthenticated`. Si omites `Accept: application/json`, Laravel redirige (302) a `/login` en lugar de devolver JSON.

Notas de configuración (`config/sanctum.php`):

- Los tokens **no caducan** (`expiration => null`).
- Los *abilities* (permisos) del token **no se verifican** en ningún endpoint: cualquier token válido tiene acceso completo a los endpoints de este plano.

### Aislamiento por cliente

Los mensajes se asocian al `Client` cuya columna `user_id` apunta al usuario autenticado, y las lecturas (`index`, `show`, `cancel`) se filtran por ese `client_id`.

> ⚠️ **Comportamiento actual a tener en cuenta:** si el usuario autenticado **no tiene una fila en `clients`**, `MessageController::scopedQuery()` no aplica ningún filtro y la consulta devuelve los mensajes de todos los clientes. Los mensajes que cree ese usuario quedarán con `client_id = null`. Asegúrate de que cada usuario con token tenga su registro de cliente asociado.

---

### `POST /api/v1/messages` — Crear un mensaje

Validación en `App\Http\Requests\StoreMessageRequest`.

| Campo | Tipo | Requerido | Reglas |
|---|---|---|---|
| `mensaje` | string | Sí | — |
| `numero` | string | Sí | máx. 20 caracteres |
| `nombre` | string | No | máx. 255 caracteres (etiqueta del destinatario) |
| `fecha_envio` | string | No | formato exacto `Y-m-d` |
| `hora_envio` | string | No | `HH:MM` o `HH:MM:SS` |

**Request**

```json
{
  "nombre": "Juan Pérez",
  "numero": "5512345678",
  "mensaje": "Tu cita es mañana a las 10:00.",
  "fecha_envio": "2026-09-01",
  "hora_envio": "09:30"
}
```

**Respuesta `201 Created`**

```json
{
  "message": "Mensaje creado correctamente",
  "msg_id": "9b1f4a6e-3c2d-4f0a-8b7e-1d2c3a4b5c6d",
  "status": "programado"
}
```

El `status` inicial es `programado` si la fecha/hora de envío está en el futuro, o `por_enviar` si no hay programación (o ya venció). Guarda el `msg_id`: es el identificador con el que se consulta o cancela el mensaje.

**Errores:** `401` sin token, `422` si falla la validación.

---

### `POST /api/v1/messages/bulk` — Envío masivo

Validación en `App\Http\Requests\BulkMessageRequest`. Permite una programación **global** y una programación **por mensaje**; la del mensaje individual tiene prioridad sobre la global.

| Campo | Tipo | Requerido | Reglas |
|---|---|---|---|
| `fecha_envio` | string | No | `Y-m-d` — aplica a los mensajes sin fecha propia |
| `hora_envio` | string | No | `HH:MM[:SS]` — aplica a los mensajes sin hora propia |
| `mensajes` | array | Sí | mínimo 1 elemento |
| `mensajes.*.mensaje` | string | Sí | — |
| `mensajes.*.numero` | string | Sí | máx. 20 |
| `mensajes.*.nombre` | string | No | máx. 255 |
| `mensajes.*.fecha_envio` | string | No | `Y-m-d` |
| `mensajes.*.hora_envio` | string | No | `HH:MM[:SS]` |

**Request**

```json
{
  "fecha_envio": "2026-09-01",
  "hora_envio": "09:00",
  "mensajes": [
    { "nombre": "Juan",  "numero": "5512345678", "mensaje": "Recordatorio de cita." },
    { "nombre": "María", "numero": "5598765432", "mensaje": "Tu pedido va en camino.", "hora_envio": "18:00" }
  ]
}
```

**Respuesta `201 Created`**

```json
{
  "message": "2 mensajes creados",
  "data": [
    { "msg_id": "9b1f4a6e-...", "numero": "5512345678", "status": "programado" },
    { "msg_id": "7c3e2b1a-...", "numero": "5598765432", "status": "programado" }
  ]
}
```

La aplicación no impone un límite de tamaño de lote, y los mensajes se insertan uno a uno dentro de la misma petición (sin transacción). Para lotes muy grandes conviene partirlos en varias llamadas.

---

### `GET /api/v1/messages` — Listar mensajes

Devuelve los mensajes del cliente autenticado, más recientes primero, **paginados de 50 en 50**.

| Parámetro | Descripción |
|---|---|
| `status` | Filtra por estado exacto: `por_enviar`, `en_cola`, `programado`, `enviado`, `cancelado`, `error` |
| `page` | Número de página |

```
GET /api/v1/messages?status=enviado&page=2
```

El valor de `status` no se valida: un estado inexistente simplemente devuelve una página vacía.

**Respuesta `200 OK`** — paginador estándar de Laravel:

```json
{
  "current_page": 1,
  "data": [
    {
      "msg_id": "9b1f4a6e-...",
      "nombre": "Juan Pérez",
      "numero": "5512345678",
      "mensaje": "Tu cita es mañana a las 10:00.",
      "status": "enviado",
      "status_label": "Enviado",
      "fecha_envio": "2026-09-01",
      "hora_envio": "09:30:00",
      "sent_at": "2026-09-01 09:30:12",
      "error": null
    }
  ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 3,
  "last_page_url": "...",
  "next_page_url": "...",
  "path": "...",
  "per_page": 50,
  "prev_page_url": null,
  "to": 50,
  "total": 134
}
```

---

### `GET /api/v1/messages/{msg_id}` — Consultar un mensaje

`{msg_id}` es el UUID devuelto al crear el mensaje.

**Respuesta `200 OK`** — mismo objeto que cada elemento de `data` en el listado:

```json
{
  "msg_id": "9b1f4a6e-...",
  "nombre": "Juan Pérez",
  "numero": "5512345678",
  "mensaje": "Tu cita es mañana a las 10:00.",
  "status": "en_cola",
  "status_label": "En cola",
  "fecha_envio": "2026-09-01",
  "hora_envio": "09:30:00",
  "sent_at": null,
  "error": null
}
```

**`404 Not Found`** si el mensaje no existe o no pertenece al cliente:

```json
{ "error": "Mensaje no encontrado" }
```

---

### `DELETE /api/v1/messages/{msg_id}` — Cancelar un mensaje

Solo se puede cancelar un mensaje que aún no haya terminado su ciclo. Estados cancelables: `programado`, `por_enviar`, `en_cola`, `error`.

**Respuesta `200 OK`**

```json
{
  "message": "Mensaje cancelado",
  "status": "cancelado"
}
```

**`422 Unprocessable Entity`** si el mensaje ya está en un estado final (`enviado` o `cancelado`):

```json
{ "error": "No se puede cancelar un mensaje ya enviado o finalizado." }
```

**`404 Not Found`** si no existe o no es del cliente.

> ⚠️ La cancelación **solo cambia el estado local**. Para un mensaje ya empujado a un canal *push* (51x.dev), la cancelación **no se propaga al proveedor**: `MessageController::cancel()` no invoca `ChannelDriver::cancel()`. El SMS puede salir de todas formas aunque la API lo reporte como `cancelado`.

---

### `GET /api/user` — Usuario autenticado

Ruta utilitaria de Sanctum. Devuelve el modelo `User` del token en uso (sin password ni datos de 2FA, y con `profile_photo_url`). Útil para verificar que un token es válido.

```json
{ "id": 1, "name": "Kevin", "email": "kevin@ejemplo.com", "profile_photo_url": "...", "...": "..." }
```

---

## 3. Plano gateway — /api/v1/gateway

API que consumen los **dispositivos de envío** (gateways ESP32 y cualquier canal de tipo *pull*). Cada canal solo ve y confirma sus propios mensajes.

### Autenticación por clave de canal

Middleware `channel.token` (`App\Http\Middleware\ChannelTokenCheck`). La clave es la columna `clave` del canal, y se acepta por cualquiera de estas cuatro vías, en este orden de precedencia:

1. Header `X-CHANNEL-KEY`
2. Header `X-API-TOKEN` *(compatibilidad con el firmware ESP32 existente)*
3. Query string `?clave=...`
4. Query string `?token=...`

El canal debe existir **y** tener `status = activo`. El canal resuelto queda disponible para el controlador en `$request->attributes->get('channel')`.

**Errores de autenticación (`401`)**

```json
{ "error": "Falta la clave del canal." }
```
```json
{ "error": "Canal no autorizado o inactivo." }
```

> ⚠️ En este plano `{id}` es el **id numérico** del mensaje (no el `msg_id` UUID del plano desarrollador). Es el primer campo de cada línea que devuelve `/pendientes`.

---

### `GET /api/v1/gateway/pendientes` — Mensajes en cola del canal

Devuelve los mensajes en estado `en_cola` asignados a este canal. **La respuesta es texto plano**, no JSON (`Content-Type: text/plain`), con una línea por mensaje en formato *pipe-delimited*:

```
id|numero|mensaje
```

**Ejemplo de respuesta `200 OK`**

```
1042|5512345678|Tu cita es mañana a las 10:00.
1043|5598765432|Tu pedido va en camino.
```

Si no hay mensajes pendientes, el cuerpo viene vacío.

> Los mensajes solo aparecen aquí después de que el comando `messages:dispatch` los haya encolado y asignado a este canal. Ver [Ciclo de vida](#5-ciclo-de-vida-de-un-mensaje).

---

### `GET|POST /api/v1/gateway/messages/{id}/enviado` — Confirmar envío

El gateway confirma que el SMS salió. Acepta GET y POST (el firmware ESP32 usa GET).

**Respuesta `200 OK`**

```json
{
  "message": "Mensaje 9b1f4a6e-... marcado como enviado.",
  "status": "enviado"
}
```

**`404`** si el mensaje no existe o no pertenece a este canal:

```json
{ "error": "Mensaje no encontrado para este canal." }
```

**`422`** si la transición no es válida (por ejemplo, el mensaje ya fue cancelado):

```json
{ "error": "Transición no permitida: cancelado → enviado" }
```

---

### `GET|POST /api/v1/gateway/messages/{id}/error` — Reportar falla

El gateway reporta que no pudo enviar el SMS.

| Parámetro | Descripción |
|---|---|
| `error` | Descripción de la falla. Se guarda en `error_message` |
| `mensaje` | Alternativa a `error` (se usa si `error` no viene) |

Si no se envía ninguno, se guarda el texto `"Error reportado por el canal"`.

```
POST /api/v1/gateway/messages/1042/error
X-CHANNEL-KEY: ...
Content-Type: application/json

{ "error": "Sin señal GSM" }
```

**Respuesta `200 OK`**

```json
{
  "message": "Mensaje 9b1f4a6e-... marcado con error.",
  "status": "error"
}
```

Mismos `404` y `422` que el endpoint anterior. Un mensaje en `error` puede reintentarse volviéndolo a `por_enviar` o `en_cola`.

---

## 4. Rutas legacy

> ⚠️ **Obsoletas.** Existen únicamente por compatibilidad con el firmware ESP32 ya desplegado en campo. **No las uses en integraciones nuevas**: usa el [plano desarrollador](#2-plano-desarrollador--apiv1) o el [plano gateway](#3-plano-gateway--apiv1gateway).

### Autenticación por token global

Middleware `api.token` (`App\Http\Middleware\ApiTokenCheck`). Un único token compartido, definido en la variable de entorno `MESSAGE_API_TOKEN`, aceptado por:

1. Header `X-API-TOKEN`
2. Query string `?token=...`
3. Query string `?X-API-TOKEN=...`

**Error `401`**

```json
{ "error": "Acceso no autorizado. Token API inválido o faltante." }
```

Diferencias importantes frente al plano gateway:

- El token es **global**, no por canal.
- Las respuestas **no se filtran por canal ni por cliente**: cualquier portador del token ve todos los mensajes despachables del sistema.
- El middleware lee `env('MESSAGE_API_TOKEN')` en tiempo de ejecución, por lo que **deja de funcionar si se cachea la configuración** (`php artisan config:cache`).

---

### `GET /api/pendientes`

Texto plano en el mismo formato `id|numero|mensaje`, pero con el scope `sendable()`: incluye los mensajes `en_cola` **y también** los `por_enviar` / `programado` cuya hora ya venció aunque el dispatcher todavía no los haya encolado. **Sin filtro de canal ni de cliente.**

---

### `POST /api/mensaje/crear`

Validación inline: `mensaje` (requerido, string) y `numero` (requerido, string). No acepta programación ni nombre, y no asigna cliente ni canal.

**Respuesta `201 Created`**

```json
{
  "message": "Mensaje creado correctamente",
  "msg_id": "9b1f4a6e-...",
  "data": { "id": 1042, "mensaje": "...", "numero": "...", "estatus": 0, "status": "por_enviar", "...": "..." }
}
```

`data` es el modelo `Message` completo, con todas sus columnas.

---

### `GET|POST /api/mensaje/{id}/procesado`

Marca el mensaje como `enviado`. **Es idempotente**: si el mensaje ya estaba en un estado final, la transición ilegal se ignora silenciosamente y la respuesta sigue siendo `200`.

```json
{ "message": "Mensaje con ID 1042 marcado como procesado." }
```

**`404`** si el id no existe:

```json
{ "error": "Mensaje no encontrado." }
```

---

### `GET /api/mensajes/status/{msg_id}`

Consulta por UUID, **sin filtro de cliente**. Devuelve el estado en los dos formatos:

```json
{
  "msg_id": "9b1f4a6e-...",
  "estatus": 1,
  "status": "enviado",
  "mensaje": "Tu cita es mañana a las 10:00.",
  "numero": "5512345678"
}
```

| Campo | Significado |
|---|---|
| `estatus` | Booleano legacy: `0` = pendiente, `1` = enviado |
| `status` | Estado de la máquina de 6 estados |

**`404`** con `{ "error": "Mensaje no encontrado" }`.

---

## 5. Ciclo de vida de un mensaje

### Estados

Definidos en `App\Enums\MessageStatus`.

| Valor | Etiqueta | Significado |
|---|---|---|
| `programado` | Programado | Creado con fecha/hora futura; espera a que venza |
| `por_enviar` | Por enviar | Listo para que el dispatcher le asigne canal |
| `en_cola` | En cola | Asignado a un canal, esperando confirmación de envío |
| `enviado` | Enviado | El canal confirmó la salida del SMS — **estado final** |
| `cancelado` | Cancelado | Cancelado por el usuario — **estado final** |
| `error` | Error | Falla de envío; admite reintento |

### Transiciones permitidas

Una transición no permitida lanza `DomainException` y la API responde `422`.

| Desde | Hacia |
|---|---|
| `programado` | `por_enviar`, `en_cola`, `enviado`, `cancelado`, `error` |
| `por_enviar` | `en_cola`, `enviado`, `cancelado`, `error` |
| `en_cola` | `enviado`, `cancelado`, `error` |
| `error` | `por_enviar`, `en_cola`, `cancelado` *(reintento)* |
| `enviado` | *(ninguna — final)* |
| `cancelado` | *(ninguna — final)* |

### Flujo normal

```
crear ──┬─ con fecha futura ──> programado ──(llega la hora)──┐
        └─ sin programación ───────────────────────────────> por_enviar
                                                              │
                              messages:dispatch asigna canal  │
                                                              v
                                                           en_cola
                                                              │
                    ┌─── canal pull: GET /gateway/pendientes ─┤
                    │    y confirma con /enviado o /error     │
                    │                                         │
                    └─── canal push: Laravel empuja a 51x.dev │
                         y messages:sync-remote confirma      │
                                                              v
                                                   enviado / error
```

### Tareas programadas

Ambas corren **cada minuto** (`routes/console.php`) con `withoutOverlapping()`, por lo que la granularidad real de la programación es de ~1 minuto:

| Comando | Función |
|---|---|
| `messages:dispatch` | Toma los mensajes vencidos (`por_enviar` / `programado` cuya hora llegó), les asigna canal y los pasa a `en_cola`. Para canales *push*, además entrega el mensaje al proveedor |
| `messages:sync-remote` | Consulta al proveedor remoto el estado de los mensajes `en_cola` que tienen `external_id`, y los pasa a `enviado` / `error` / `cancelado` |

**Resolución del canal** (en `DispatchMessages`), en orden:

1. El canal ya asignado al mensaje (`channel_id`).
2. El canal del cliente, o el predeterminado si el cliente no tiene uno (`Client::effectiveChannel()`).
3. El canal predeterminado global (`Channel::default()`, `is_default = true`).

Si no hay ningún canal **activo** disponible, el mensaje pasa a `error` con `error_message = "No hay canal activo disponible (ni asignado ni predeterminado)."`. Si el driver del canal falla, el error queda registrado en el log y el mensaje también pasa a `error`, sin tumbar el resto del lote.

### Tipos de canal

Definidos en `App\Enums\ChannelType`:

| Valor | Etiqueta | Modo | Autenticación contra nuestra API |
|---|---|---|---|
| `esp32` | ESP32 | pull — el dispositivo jala de `/api/v1/gateway/pendientes` | Columna `clave` del canal |
| `51x` | 51x.dev | push — Laravel empuja al proveedor | No aplica (Laravel es el cliente) |
| `otro` | Otro | pull | Columna `clave` del canal |

Un canal creado sin `clave` recibe automáticamente una aleatoria de 40 caracteres.

### Privacidad de los datos

`mensaje` y `numero` se guardan **cifrados** en la base de datos (`Message::$casts` usa `encrypted`), y el `config` de cada canal se guarda como `encrypted:array`. El cifrado depende de `APP_KEY`: si se pierde o cambia, los mensajes existentes dejan de poder descifrarse.

---

## 6. API externa consumida — 51x.dev

Servicio externo que envía los SMS desde un teléfono Android. Lo consume `App\Services\Channels\Api51xDriver` para los canales con `tipo = 51x`. **Es la única API externa que el sistema llama.**

Spec del proveedor: <https://51x.dev/docs/openapi.yaml>

### Configuración

| Origen | Clave | Descripción |
|---|---|---|
| `config/services.php` | `services.51x.base_url` | URL base por defecto, desde `FIVEONEX_BASE_URL` (default `https://51x.dev`) |
| `config` del canal | `api_key` | Se envía como header `X-API-Key`. **Requerido** |
| `config` del canal | `device_id` | Dispositivo Android que enviará el SMS |
| `config` del canal | `base_url` | Sobrescribe la URL base global para este canal |
| `config` del canal | `pais` | Prefijo E.164 para números sin `+` (default `+52`) |

Cliente HTTP: `Accept: application/json`, timeout de **15 s**, **2 reintentos** con 300 ms de espera (sin lanzar excepción al agotarse).

### `POST {base_url}/api/messages` — Empujar un mensaje

```json
{
  "to": "+525512345678",
  "channel": "sms",
  "body": "Tu cita es mañana a las 10:00.",
  "device_id": "abc123"
}
```

Los campos nulos o vacíos se omiten. El proveedor responde `202` con un `id`, que se guarda en `messages.external_id`; el mensaje permanece `en_cola` hasta que se confirme.

Casos de fallo (todos dejan el mensaje en `error` con el motivo en `error_message`):

- Excepción de red → mensaje de la excepción.
- Respuesta fallida → campo `error` de la respuesta, o `"51x.dev respondió {código}."`.
- Respuesta exitosa sin `id` → `"51x.dev aceptó el mensaje pero no devolvió un id."`.

### `GET {base_url}/api/messages/{external_id}` — Consultar estado

Lo invoca `messages:sync-remote`. Mapeo del campo `status` remoto al estado interno:

| `status` remoto | Estado interno |
|---|---|
| `sent` | `enviado` |
| `failed` | `error` (con el campo `error` remoto en `error_message`) |
| `cancelled` | `cancelado` |
| `scheduled`, `pending`, otro | *(sin cambio — sigue `en_cola`)* |

- `404` del proveedor → el mensaje pasa a `error` con `"El mensaje ya no existe en 51x.dev."`.
- Otro fallo o excepción de red → se registra un `Log::warning` y se reintenta en la siguiente corrida.

### `POST {base_url}/api/messages/{external_id}/cancel` — Cancelar

Devuelve `true` si la respuesta fue exitosa. Si el mensaje no tiene `external_id`, se considera cancelado sin llamar al proveedor.

> Actualmente **ningún endpoint de la API invoca esta llamada**: cancelar por `DELETE /api/v1/messages/{msg_id}` solo cambia el estado local.

### Normalización de números

El proveedor exige formato **E.164**. `Api51xDriver::normalizeRecipient()`:

1. Elimina todo lo que no sea dígito o `+`.
2. Si ya empieza con `+`, lo deja igual.
3. Si no, quita los ceros iniciales y antepone el prefijo `pais` del canal (default `+52`).

Ejemplo: `55 1234 5678` → `+525512345678`.

> La programación **no se delega** al proveedor: el dispatcher retiene el mensaje hasta que vence su fecha/hora y solo entonces lo empuja.

---

## 7. Códigos de error

| Código | Cuándo ocurre |
|---|---|
| `200 OK` | Consulta, cancelación o confirmación exitosa |
| `201 Created` | Mensaje o lote creado |
| `401 Unauthorized` | Token Sanctum ausente/inválido; clave de canal faltante o de un canal inactivo; token legacy incorrecto |
| `404 Not Found` | El mensaje no existe, o no pertenece al cliente/canal que pregunta |
| `422 Unprocessable Entity` | Validación fallida, o transición de estado no permitida |

Los `401` traen mensajes distintos según el plano:

| Plano | Cuerpo |
|---|---|
| Desarrollador | `{ "message": "Unauthenticated." }` |
| Gateway | `{ "error": "Falta la clave del canal." }` / `{ "error": "Canal no autorizado o inactivo." }` |
| Legacy | `{ "error": "Acceso no autorizado. Token API inválido o faltante." }` |

Formato estándar de un `422` de validación:

```json
{
  "message": "The numero field is required.",
  "errors": {
    "numero": ["The numero field is required."]
  }
}
```

> Envía siempre `Accept: application/json` para que Laravel responda JSON en vez de una redirección HTML.

---

## 8. Ejemplos de integración

### cURL

```bash
curl -X POST https://tu-dominio.com/api/v1/messages \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "nombre": "Juan Pérez",
        "numero": "5512345678",
        "mensaje": "Tu cita es mañana a las 10:00."
      }'
```

Consultar el estado:

```bash
curl https://tu-dominio.com/api/v1/messages/9b1f4a6e-... \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json"
```

Cancelar:

```bash
curl -X DELETE https://tu-dominio.com/api/v1/messages/9b1f4a6e-... \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json"
```

### PHP (cURL)

```php
$ch = curl_init('https://tu-dominio.com/api/v1/messages');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer '.$token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'nombre' => 'Juan Pérez',
        'numero' => '5512345678',
        'mensaje' => 'Tu cita es mañana a las 10:00.',
    ]),
]);

$respuesta = json_decode(curl_exec($ch), true);
curl_close($ch);

echo $respuesta['msg_id'];
```

### JavaScript (fetch)

```js
const respuesta = await fetch('https://tu-dominio.com/api/v1/messages', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    nombre: 'Juan Pérez',
    numero: '5512345678',
    mensaje: 'Tu cita es mañana a las 10:00.',
  }),
});

const datos = await respuesta.json();
console.log(datos.msg_id);
```

### Envío masivo con programación

```bash
curl -X POST https://tu-dominio.com/api/v1/messages/bulk \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "fecha_envio": "2026-09-01",
        "hora_envio": "09:00",
        "mensajes": [
          { "numero": "5512345678", "mensaje": "Recordatorio de cita." },
          { "numero": "5598765432", "mensaje": "Tu pedido va en camino.", "hora_envio": "18:00" }
        ]
      }'
```

### Gateway ESP32 (plano v1)

```bash
# 1. Jalar los mensajes en cola del canal
curl "https://tu-dominio.com/api/v1/gateway/pendientes" \
  -H "X-CHANNEL-KEY: CLAVE_DEL_CANAL"
# 1042|5512345678|Tu cita es mañana a las 10:00.

# 2. Confirmar el envío
curl "https://tu-dominio.com/api/v1/gateway/messages/1042/enviado?clave=CLAVE_DEL_CANAL"

# 3. O reportar la falla
curl "https://tu-dominio.com/api/v1/gateway/messages/1042/error?clave=CLAVE_DEL_CANAL&error=Sin+se%C3%B1al+GSM"
```

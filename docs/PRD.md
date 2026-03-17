# PRD - FastSMS (Sistema interno con 1 Arduino)
Version: 1.0 (interno)
Fecha: 2026-03-06
Estado: Draft
Repo: sms_laravel (Laravel 12)

## 1) Contexto y proposito
FastSMS es un sistema interno para centralizar solicitudes de envio de SMS:
- Aplicaciones internas crean mensajes via API (o el admin los crea desde el panel).
- Un unico dispositivo (Arduino UNO + SIM900) actua como "worker": consulta pendientes, envia el SMS y confirma al servidor.
- Un panel administrativo (Filament) permite visualizar y operar la bandeja de mensajes.

## 2) Objetivos
- O1: Recibir solicitudes de SMS de sistemas internos via API.
- O2: Entregar al Arduino una lista simple de mensajes pendientes para envio.
- O3: Registrar la confirmacion de "procesado" desde el Arduino.
- O4: Permitir consultar el estatus por un identificador estable (msg_id).
- O5: Proveer visibilidad operativa en el panel (pendientes, enviados, fechas).

## 3) Alcance (MVP)
Incluye:
- API protegida por token compartido (simple).
- Endpoints:
  - GET /api/pendientes
  - POST /api/mensaje/crear
  - GET /api/mensajes/status/{msg_id}
  - GET|POST /api/mensaje/{id}/procesado
- Panel Filament para listar/editar mensajes.
- Persistencia en DB (segun .env).
- Encriptacion en DB de mensaje y numero (ya en el modelo).

Fuera de alcance:
- Multi-tenant / multiples tokens / roles complejos.
- Multiples workers o balanceo.
- Garantia de "entrega al destinatario" (solo estado operativo del flujo).

## 4) Supuestos
- S1: Hay un solo Arduino activo (no hay concurrencia entre workers).
- S2: El entorno es interno; el token se distribuye de forma segura y no se expone publicamente.
- S3: "Procesado" significa: el Arduino intento envio y notifico al backend (no es confirmacion de entrega a destino).

## 5) Usuarios
- U1: Integrador interno: llama la API para crear mensajes y consultar estatus.
- U2: Operador/Admin: revisa cola y estatus en Filament.
- U3: Arduino (SIM900): consume pendientes y confirma procesado.

## 6) Requisitos funcionales

### 6.1 Seguridad (token)
- RF-SEC-1: Proteger endpoints de API con token compartido.
  - Header: X-API-TOKEN
  - Query: ?token=... o ?X-API-TOKEN=... (compatibilidad SIM900)
- RF-SEC-2: Si token invalido/faltante: 401 JSON
  - { "error": "Acceso no autorizado. Token API invalido o faltante." }
- RF-SEC-3: El token esperado se configura en MESSAGE_API_TOKEN (env).

Nota tecnica (observado en el repo):
- El endpoint /api/mensaje/{id}/procesado esta definido fuera del group con middleware token.
  En interno puede "funcionar", pero se recomienda protegerlo igual para evitar que cualquiera
  marque mensajes como procesados.

### 6.2 Crear mensaje
Endpoint: POST /api/mensaje/crear (protegido por token)
- RF-C-1: Validar:
  - mensaje: requerido string
  - numero: requerido string
- RF-C-2: Crear registro con:
  - estatus = 0 (pendiente)
  - fecha = now()
  - msg_id autogenerado (UUID)
- RF-C-3: Respuesta 201 JSON con msg_id y datos del registro.

Criterios de aceptacion:
- Payload valido -> 201 y msg_id no vacio.
- Payload invalido -> 422 (validacion Laravel).

### 6.3 Obtener pendientes (para Arduino)
Endpoint: GET /api/pendientes (protegido por token)
- RF-P-1: Devolver solo mensajes con estatus = 0.
- RF-P-2: Responder en texto plano (text/plain) con lineas:
  - id|numero|mensaje\n
- RF-P-3: Si no hay pendientes, responder 200 con cuerpo vacio (o sin lineas).

Criterios de aceptacion:
- Con 3 mensajes pendientes, el body contiene 3 lineas correctamente formateadas.

### 6.4 Confirmar procesado
Endpoint: GET|POST /api/mensaje/{id}/procesado
- RF-M-1: Cambiar estatus a 1 para el id.
- RF-M-2: Si no existe el id: 404 JSON { "error": "Mensaje no encontrado." }
- RF-M-3: Si existe: 200 JSON { "message": "Mensaje con ID {id} marcado como procesado." }
- RF-M-4: Idempotencia recomendada: si ya estaba en 1, responder 200 igualmente.

### 6.5 Consultar estatus
Endpoint: GET /api/mensajes/status/{msg_id} (protegido por token)
- RF-S-1: Buscar por msg_id.
- RF-S-2: Si no existe: 404 JSON { "error": "Mensaje no encontrado" }
- RF-S-3: Si existe: 200 JSON con msg_id y estatus.

Recomendacion (privacidad):
- Evitar devolver mensaje y numero en este endpoint, salvo necesidad operativa interna.

### 6.6 Panel (Filament)
- RF-F-1: Listar mensajes con columnas: mensaje, numero, estatus, fecha, timestamps.
- RF-F-2: Busqueda por mensaje y numero.
- RF-F-3: Edicion de estatus (operacion manual).
- RF-F-4: Mostrar msg_id solo lectura al editar.

Deseables:
- Filtros por estatus y rango de fecha.
- Accion "Reintentar" (si se modela un estado de error en v1.1/v2).

## 7) Requisitos no funcionales
- RNF-1: Confiabilidad: el polling del Arduino (ej. cada 10s) no debe saturar el servidor.
- RNF-2: Seguridad basica: token no se loguea ni se expone.
- RNF-3: Trazabilidad minima:
  - registrar creacion y confirmacion (sin contenido sensible en logs).

## 8) Modelo de datos (actual)
Entidad: Message
- mensaje (encrypted)
- numero (encrypted)
- estatus (0 pendiente, 1 procesado)
- fecha (datetime)
- msg_id (UUID string)
- timestamps

## 9) Flujo operativo (E2E)
1) Sistema interno -> POST /api/mensaje/crear (token)
2) Arduino -> GET /api/pendientes?token=...
3) Arduino envia SMS via SIM900
4) Arduino -> GET /api/mensaje/{id}/procesado?token=...
5) Operador consulta en Filament / integrador consulta status por msg_id.

## 10) Metricas internas (minimas)
- M1: Pendientes actuales (count estatus=0)
- M2: Procesados hoy (count estatus=1 en rango de fecha)
- M3: Tiempo medio de cola (creacion -> procesado)

## 11) Riesgos / puntos a corregir
- R1: Ruta web /actualizar-base-datos ejecuta migraciones via HTTP (riesgo si queda expuesta).
- R2: Confirmacion "procesado" sin token permite manipular estatus.
- R3: Sin estado "error", no hay visibilidad de fallos de envio en modem/red.
- R4: Encriptacion impide busquedas avanzadas por contenido/numero (limitacion aceptada).

## 12) Roadmap sugerido (opcional)
- v1: estabilizar flujo API <-> Arduino + panel.
- v1.1: agregar estado 2=error y campos last_error/intentos (si se requiere).
- v1.2: limitar cantidad de pendientes por respuesta y ordenar por antiguedad.

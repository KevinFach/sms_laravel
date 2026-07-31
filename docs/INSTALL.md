# Instalación y configuración — Servidor SMS ESP32

Servidor de mensajes SMS (Laravel 12 + Filament 4) que actúa como cola/API para uno o
varios gateways ESP32. Los gateways **jalan** (pull) los mensajes en cola y confirman el envío.

## Requisitos

- PHP 8.2+
- MySQL / MariaDB (el dispatcher y los scopes usan funciones `TIMESTAMP()`/`NOW()`)
- Composer
- Node.js + npm (para compilar assets)
- Un servidor con cron (para la cola de envíos programados)

## Puesta en marcha

```bash
git clone <repo> && cd sms_laravel
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edita `.env` con los datos de la base de datos y define el token del gateway principal:

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=sms_laravel
DB_USERNAME=root
DB_PASSWORD=

# Clave del canal predeterminado (la reutiliza ChannelSeeder)
MESSAGE_API_TOKEN=una-clave-larga-y-secreta
```

Migra y siembra el canal predeterminado:

```bash
php artisan migrate
php artisan db:seed --class=ChannelSeeder
```

Crea el primer usuario del panel (registro deshabilitado por seguridad):

```bash
php artisan tinker --execute="\App\Models\User::create(['name'=>'Admin','email'=>'admin@tu-dominio.mx','password'=>bcrypt('CAMBIA_ESTO')]);"
```

Panel administrativo: `https://tu-dominio/admin`.

## Cron (cola de envíos)

El dispatcher `messages:dispatch` promueve cada minuto los mensajes vencidos a la cola de su
canal. Agrega el scheduler de Laravel al cron del servidor:

```cron
* * * * * cd /ruta/sms_laravel && php artisan schedule:run >> /dev/null 2>&1
```

## Configuración del gateway ESP32

Cada dispositivo ESP32 es un **canal**. Desde el panel (`Administración → Canales`) crea o edita
un canal y copia su **clave**. En el firmware configura:

- **URL base:** `https://tu-dominio/api/v1/gateway`
- **Autenticación:** header `X-CHANNEL-KEY: <clave-del-canal>` (o `?clave=<clave>` en la URL)

Endpoints que consume el gateway:

| Método | Ruta | Uso |
|---|---|---|
| GET | `/api/v1/gateway/pendientes` | Mensajes en cola del canal, texto plano `id\|numero\|mensaje` |
| POST | `/api/v1/gateway/messages/{id}/enviado` | Confirmar que el SMS salió |
| POST | `/api/v1/gateway/messages/{id}/error` | Reportar falla (campo `error` opcional) |

> Compatibilidad: las rutas legacy (`/api/pendientes`, `/api/mensaje/{id}/procesado`, …) siguen
> activas con el token global `MESSAGE_API_TOKEN` para firmware ya desplegado.

## API para desarrolladores (clientes)

Autenticada con token Sanctum del usuario (`Authorization: Bearer <token>`). El usuario se vincula
a un `Client` desde el panel; sus mensajes quedan aislados a ese cliente y salen por el canal
asignado (o el predeterminado).

| Método | Ruta | Uso |
|---|---|---|
| POST | `/api/v1/messages` | Crear un mensaje (acepta `fecha_envio`/`hora_envio`) |
| POST | `/api/v1/messages/bulk` | Envío masivo (arreglo `mensajes[]`) |
| GET | `/api/v1/messages` | Listado (filtro `?status=`) |
| GET | `/api/v1/messages/{msg_id}` | Estado de un mensaje |
| DELETE | `/api/v1/messages/{msg_id}` | Cancelar (si no ha sido enviado) |

## Estados de un mensaje

`programado` → `por_enviar` → `en_cola` → `enviado`, con ramas `cancelado` y `error`.
Ver `app/Enums/MessageStatus.php`.

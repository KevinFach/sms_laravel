# Guía para Agentes (sms_laravel)

Este repositorio es una aplicación Laravel 12 para gestionar el envio de mensajes (SMS) de forma centralizada.
Incluye:
- Panel de administracion con Filament.
- Autenticacion/ajustes con Jetstream + Fortify + Livewire/Volt.
- API para crear mensajes, consultar pendientes y marcar como procesados.

## Stack y dependencias relevantes
- PHP: definido en `composer.json` como `^8.2`.
- Laravel: `^12` (Artisan reporta 12.x).
- Livewire: 3.x, Volt: 1.x.
- Jetstream: 5.x, Fortify: 1.x.
- Filament: 4.0.
- Testing: Pest 4 + `php artisan test`.
- Frontend: Vite + Tailwind.

## Estructura rapida del dominio
- Modelo principal: `App\Models\Message`.
  - Campos: `mensaje`, `numero`, `estatus`, `fecha`, `msg_id`.
  - `msg_id` se genera automaticamente (UUID) al crear.
  - `mensaje` y `numero` estan con cast `encrypted` (ojo al serializar y al consultar).
- API token middleware: `App\Http\Middleware\ApiTokenCheck`.
  - Lee `X-API-TOKEN` en header o `?token=...` / `?X-API-TOKEN=...`.
  - Valida contra `MESSAGE_API_TOKEN` (env).
- Rutas API: `routes/api.php`.
- Panel: `app/Filament/...`.

## Reglas de edicion y formato
Basado en `.editorconfig`:
- Fin de linea: LF.
- Indentacion: 4 espacios.
- `trim_trailing_whitespace = true` (excepto Markdown).
- Siempre newline final.

Mantener diffs pequenos:
- No reformatear archivos no relacionados.
- Evitar cambios masivos de estilo sin necesidad.

## Comandos (build / lint / test)

### Instalacion
- Backend: `composer install`
- Frontend: `npm install`

### Desarrollo
- Todo junto (server + queue + vite): `composer run dev`
- Solo Laravel: `php artisan serve`
- Solo Vite: `npm run dev`

### Build de assets
- Produccion: `npm run build`

### Formateo / lint (PHP)
Pint esta instalado como dependencia de desarrollo:
- Formatear: `vendor/bin/pint`
- Chequear sin escribir: `vendor/bin/pint --test`

Nota: No hay scripts de ESLint/Prettier configurados en `package.json` (solo `dev` y `build`).

### Tests
Ejecutar todo:
- `composer run test`
- o `php artisan test`

Ejecutar un testsuite:
- `php artisan test --testsuite=Feature`
- `php artisan test --testsuite=Unit`

Ejecutar un solo archivo:
- `php artisan test tests/Feature/Auth/AuthenticationTest.php`
- o `vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php`

Ejecutar un solo test (por filtro):
- `php artisan test --filter=AuthenticationTest`
- `php artisan test --filter=test_user_can_login`
- `vendor/bin/pest --filter "can log in"`

Entorno de tests:
- `phpunit.xml` configura SQLite in-memory (`DB_DATABASE=:memory:`).

## Variables de entorno importantes
- `MESSAGE_API_TOKEN`: requerido para endpoints bajo middleware `api.token`.
  - No aparece en `.env.example`; debe agregarse en `.env` local.

## Guias de estilo (PHP / Laravel)

### Imports (use)
- Mantener imports limpios (sin `use` sin usar).
- Orden recomendado:
  1) Vendor/framework (`Illuminate\...`, `Filament\...`, `Livewire\...`).
  2) App (`App\...`).
- Orden alfabetico dentro de cada grupo.

### Tipos, firmas y retornos
- Agregar type hints en parametros y return types cuando sea razonable.
- Usar `: void` cuando no hay retorno.
- En controladores, retornar `response()->json(...)` con status code explicito.

### Nombres y consistencia
- Clases: PascalCase.
- Metodos/variables: camelCase.
- Mantener el lenguaje existente (mucho codigo usa Espanol: `MensajeController`, `mensaje`, `numero`).

### Validacion y requests
- Para validaciones simples, `Request::validate()` es aceptable.
- Para flujos mas complejos o reusables, crear Form Requests.

### Manejo de errores
- Preferir helpers del framework:
  - `abort(404)` / `abort_if(...)`.
  - `Model::findOrFail()` cuando aplique.
- Para API, mantener el formato actual de error:
  - `{"error": "..."}` con 4xx/5xx.

### Seguridad y datos sensibles
- No commitear `.env`.
- No loguear tokens ni secretos.
- Cuidado al devolver modelos con casts `encrypted`:
  - Preferir construir arrays/DTOs para respuestas API.
  - Evitar exponer campos innecesarios.

### Configuracion
- Evitar introducir nuevos `env()` fuera de archivos de configuracion.
  - Preferir `config('...')` (aunque el middleware actual usa `env('MESSAGE_API_TOKEN')`).

## Guias de estilo (Filament)
- Mantener el patron actual:
  - `Resource` delega en clases de `Schemas/` y `Tables/`.
- Preferir definiciones pequenas y componibles.

## Guias de estilo (Frontend)
- Vite usa inputs: `resources/css/app.css` y `resources/js/app.js`.
- `resources/js/app.js` esta vacio actualmente; agregar JS solo si es necesario.
- `resources/views/welcome.blade.php` usa Tailwind via CDN.
  - No migrar a Vite/Tailwind compilado sin una razon clara.

## Puntos de atencion / riesgos
- Existe una ruta web que ejecuta migraciones via HTTP: `/actualizar-base-datos` en `routes/web.php`.
  - Tratarla como peligrosa fuera de entornos locales; evitar expandir ese patron.

## Reglas de Cursor / Copilot
No se encontraron reglas en:
- `.cursor/rules/`
- `.cursorrules`
- `.github/copilot-instructions.md`

## Antes de entregar un cambio
- Ejecutar `vendor/bin/pint`.
- Ejecutar tests relevantes:
  - Minimo: `php artisan test --filter=<Nombre>` o el archivo afectado.
  - Ideal: `php artisan test`.

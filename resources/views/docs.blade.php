<x-layouts.site title="Documentación de la API — FastSMS">

    <section class="py-16">
        <div class="max-w-6xl mx-auto px-6 grid lg:grid-cols-[220px_1fr] gap-12">

            <!-- ÍNDICE LATERAL -->
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-3">Contenido</p>
                <nav class="flex flex-col gap-2 text-sm border-l border-neutral-200 pl-4">
                    <a href="#introduccion" class="hover:text-green-600">Introducción</a>
                    <a href="#autenticacion" class="hover:text-green-600">Autenticación</a>
                    <a href="#estados" class="hover:text-green-600">Ciclo de vida</a>
                    <a href="#endpoints" class="hover:text-green-600">Endpoints</a>
                    <a href="#crear" class="hover:text-green-600 pl-3 text-neutral-600">Crear mensaje</a>
                    <a href="#bulk" class="hover:text-green-600 pl-3 text-neutral-600">Envío masivo</a>
                    <a href="#listar" class="hover:text-green-600 pl-3 text-neutral-600">Listar mensajes</a>
                    <a href="#consultar" class="hover:text-green-600 pl-3 text-neutral-600">Consultar uno</a>
                    <a href="#cancelar" class="hover:text-green-600 pl-3 text-neutral-600">Cancelar</a>
                    <a href="#programacion" class="hover:text-green-600">Programación</a>
                    <a href="#longitud" class="hover:text-green-600">Longitud y segmentos</a>
                    <a href="#errores" class="hover:text-green-600">Códigos de error</a>
                    <a href="#ejemplos" class="hover:text-green-600">Ejemplos</a>
                </nav>
            </aside>

            <!-- CONTENIDO -->
            <div class="min-w-0">

                <!-- INTRODUCCIÓN -->
                <div id="introduccion" class="scroll-mt-24">
                    <h1 class="text-4xl font-semibold mb-4">Documentación de la API</h1>
                    <p class="text-neutral-700 text-lg mb-6">
                        La API de FastSMS permite que cualquier aplicación cree, programe, consulte y cancele
                        mensajes SMS. Es una API REST: las peticiones y las respuestas usan JSON.
                    </p>

                    <h3 class="text-lg font-semibold mb-2">URL base</h3>
                    <div class="relative mb-6">
                        <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>{{ url('/api/v1') }}</pre>
                        <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                    </div>

                    <div class="border-l-4 border-green-600 bg-neutral-50 p-4 rounded-r-lg text-sm text-neutral-700">
                        El texto de cada mensaje y el número de destino se guardan <strong>cifrados</strong> en la
                        base de datos. Solo se descifran para entregarlos al canal de envío y para responder a tus
                        propias consultas.
                    </div>
                </div>

                <!-- AUTENTICACIÓN -->
                <div id="autenticacion" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Autenticación</h2>
                    <p class="text-neutral-700 mb-6">
                        Todas las peticiones se autentican con un token personal enviado en la cabecera
                        <code class="bg-neutral-100 px-1.5 py-0.5 rounded text-sm">Authorization</code>.
                    </p>

                    <div class="relative mb-6">
                        <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>Authorization: Bearer TU_TOKEN
Accept: application/json</pre>
                        <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                    </div>

                    <h3 class="text-lg font-semibold mb-2">Cómo obtener tu token</h3>
                    <ol class="list-decimal list-inside text-neutral-700 space-y-1 mb-4">
                        <li>Inicia sesión en tu cuenta de FastSMS.</li>
                        <li>Entra en la pantalla <strong>API Tokens</strong>.</li>
                        <li>Crea un token, ponle un nombre y <strong>cópialo en ese momento</strong>: solo se muestra una vez.</li>
                    </ol>
                    <div class="mb-8">
                        @auth
                            <a href="{{ route('api-tokens.index') }}" class="inline-block px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700">Ir a mis API Tokens</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-block px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700">Inicia sesión para crear un token</a>
                        @endauth
                    </div>

                    <div class="border-l-4 border-amber-500 bg-amber-50 p-4 rounded-r-lg text-sm text-neutral-800 space-y-3">
                        <p>
                            <strong>Envía siempre la cabecera <code class="bg-white px-1.5 py-0.5 rounded">Accept: application/json</code>.</strong>
                            Sin ella, una petición sin token válido no devuelve <code>401</code> sino una redirección
                            <code>302</code> a la página de login, y los errores de validación también redirigen en
                            lugar de responder <code>422</code>. Es la causa más común de «mi cliente HTTP recibe
                            HTML en vez de JSON».
                        </p>
                        <p>
                            <strong>Los tokens no caducan.</strong> Si uno se ve comprometido, revócalo a mano desde
                            la pantalla de API Tokens.
                        </p>
                        <p>
                            <strong>Un token da acceso completo</strong> a todos los endpoints de mensajes de tu
                            cuenta. Trátalo como una contraseña: nunca lo publiques en código de cliente ni en un
                            repositorio.
                        </p>
                    </div>
                </div>

                <!-- ESTADOS -->
                <div id="estados" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Ciclo de vida de un mensaje</h2>
                    <p class="text-neutral-700 mb-6">
                        Cada mensaje avanza por una máquina de estados. El campo
                        <code class="bg-neutral-100 px-1.5 py-0.5 rounded text-sm">status</code> de las respuestas
                        siempre contiene uno de estos seis valores.
                    </p>

                    <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto mb-8">programado --(llega la hora)--&gt; por_enviar --(se asigna canal)--&gt; en_cola --(el canal confirma)--&gt; enviado
     |                             |                              |
     +-----------------------------+------------------------------+--&gt; cancelado / error</pre>

                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm border rounded-lg">
                            <thead class="bg-neutral-100 text-left">
                                <tr>
                                    <th class="p-3 font-semibold">Valor</th>
                                    <th class="p-3 font-semibold">Etiqueta</th>
                                    <th class="p-3 font-semibold">Significado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="p-3"><code>programado</code></td>
                                    <td class="p-3">Programado</td>
                                    <td class="p-3 text-neutral-700">Creado con fecha y hora futuras. Espera a que llegue el momento.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>por_enviar</code></td>
                                    <td class="p-3">Por enviar</td>
                                    <td class="p-3 text-neutral-700">Listo para salir, a la espera de que se le asigne un canal.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>en_cola</code></td>
                                    <td class="p-3">En cola</td>
                                    <td class="p-3 text-neutral-700">Ya entregado a un canal de envío, pendiente de confirmación.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>enviado</code></td>
                                    <td class="p-3">Enviado</td>
                                    <td class="p-3 text-neutral-700">El canal confirmó el envío. <strong>Estado final.</strong></td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>cancelado</code></td>
                                    <td class="p-3">Cancelado</td>
                                    <td class="p-3 text-neutral-700">Cancelado antes de salir. <strong>Estado final.</strong></td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>error</code></td>
                                    <td class="p-3">Error</td>
                                    <td class="p-3 text-neutral-700">Falló el envío; el motivo va en el campo <code>error</code>. Admite reintento.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-l-4 border-amber-500 bg-amber-50 p-4 rounded-r-lg text-sm text-neutral-800">
                        <code>enviado</code> significa que el canal confirmó haber enviado el SMS, <strong>no</strong>
                        que el destinatario lo haya recibido. <code>enviado</code> y <code>cancelado</code> son
                        estados finales: no se puede transicionar desde ellos.
                    </div>
                </div>

                <!-- ENDPOINTS -->
                <div id="endpoints" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Endpoints</h2>
                    <p class="text-neutral-700 mb-6">
                        Todas las rutas cuelgan de <code class="bg-neutral-100 px-1.5 py-0.5 rounded text-sm">{{ url('/api/v1') }}</code>
                        y requieren autenticación.
                    </p>

                    <div class="overflow-x-auto mb-10">
                        <table class="w-full text-sm border rounded-lg">
                            <thead class="bg-neutral-100 text-left">
                                <tr>
                                    <th class="p-3 font-semibold">Método</th>
                                    <th class="p-3 font-semibold">Ruta</th>
                                    <th class="p-3 font-semibold">Descripción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">POST</span></td>
                                    <td class="p-3"><a href="#crear" class="hover:text-green-600"><code>/messages</code></a></td>
                                    <td class="p-3 text-neutral-700">Crear un mensaje</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">POST</span></td>
                                    <td class="p-3"><a href="#bulk" class="hover:text-green-600"><code>/messages/bulk</code></a></td>
                                    <td class="p-3 text-neutral-700">Crear muchos mensajes de una vez</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">GET</span></td>
                                    <td class="p-3"><a href="#listar" class="hover:text-green-600"><code>/messages</code></a></td>
                                    <td class="p-3 text-neutral-700">Listar tus mensajes (paginado)</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">GET</span></td>
                                    <td class="p-3"><a href="#consultar" class="hover:text-green-600"><code>/messages/{msg_id}</code></a></td>
                                    <td class="p-3 text-neutral-700">Consultar un mensaje</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">DELETE</span></td>
                                    <td class="p-3"><a href="#cancelar" class="hover:text-green-600"><code>/messages/{msg_id}</code></a></td>
                                    <td class="p-3 text-neutral-700">Cancelar un mensaje no enviado</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- CREAR -->
                    <div id="crear" class="scroll-mt-24 border rounded-xl p-6 mb-8 bg-white shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">POST</span>
                            <code class="text-lg">/api/v1/messages</code>
                        </div>
                        <p class="text-neutral-700 mb-6">Crea un mensaje. Si no indicas fecha y hora, se envía cuanto antes.</p>

                        <h4 class="font-semibold mb-3">Cuerpo de la petición</h4>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border rounded-lg">
                                <thead class="bg-neutral-100 text-left">
                                    <tr>
                                        <th class="p-3 font-semibold">Campo</th>
                                        <th class="p-3 font-semibold">Tipo</th>
                                        <th class="p-3 font-semibold">Reglas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr>
                                        <td class="p-3"><code>mensaje</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3 text-neutral-700"><strong class="text-green-700">Obligatorio.</strong> Texto del SMS.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3"><code>numero</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3 text-neutral-700"><strong class="text-green-700">Obligatorio.</strong> Máximo 20 caracteres.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3"><code>nombre</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3 text-neutral-700">Opcional. Máximo 255 caracteres. Referencia interna del destinatario.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3"><code>fecha_envio</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3 text-neutral-700">Opcional. Formato <code>AAAA-MM-DD</code>.</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3"><code>hora_envio</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3 text-neutral-700">Opcional. Formato <code>HH:MM</code> o <code>HH:MM:SS</code>.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold mb-3">Petición</h4>
                                <div class="relative">
                                    <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>{
  "nombre": "Ana López",
  "numero": "5551234567",
  "mensaje": "Tu cita es mañana a las 10:00"
}</pre>
                                    <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">Respuesta <span class="text-green-700">201 Created</span></h4>
                                <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "message": "Mensaje creado correctamente",
  "msg_id": "9b1c7d4e-...-3f2a",
  "status": "por_enviar"
}</pre>
                            </div>
                        </div>

                        <p class="text-sm text-neutral-600 mt-4">
                            El <code>status</code> inicial es <code>programado</code> si la fecha y hora indicadas
                            son futuras, y <code>por_enviar</code> en caso contrario. Guarda el <code>msg_id</code>:
                            es el identificador con el que consultarás o cancelarás el mensaje.
                        </p>
                    </div>

                    <!-- BULK -->
                    <div id="bulk" class="scroll-mt-24 border rounded-xl p-6 mb-8 bg-white shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">POST</span>
                            <code class="text-lg">/api/v1/messages/bulk</code>
                        </div>
                        <p class="text-neutral-700 mb-6">
                            Crea varios mensajes en una sola llamada. El array <code>mensajes</code> es obligatorio y
                            debe tener al menos un elemento; cada elemento acepta los mismos campos que
                            <a href="#crear" class="text-green-700 hover:underline">crear un mensaje</a>.
                        </p>

                        <div class="border-l-4 border-green-600 bg-neutral-50 p-4 rounded-r-lg text-sm text-neutral-700 mb-6">
                            Puedes poner <code>fecha_envio</code> y <code>hora_envio</code> en la raíz para programar
                            todo el lote de golpe. Si un elemento trae los suyos propios, <strong>los del elemento
                            tienen prioridad</strong> sobre los globales.
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold mb-3">Petición</h4>
                                <div class="relative">
                                    <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>{
  "fecha_envio": "2026-09-01",
  "hora_envio": "09:00",
  "mensajes": [
    {
      "nombre": "Ana",
      "numero": "5551234567",
      "mensaje": "Recordatorio de cita"
    },
    {
      "numero": "5559876543",
      "mensaje": "Tu paquete va en camino",
      "hora_envio": "18:30"
    }
  ]
}</pre>
                                    <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">Respuesta <span class="text-green-700">201 Created</span></h4>
                                <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "message": "2 mensajes creados",
  "data": [
    {
      "msg_id": "9b1c7d4e-...-3f2a",
      "numero": "5551234567",
      "status": "programado"
    },
    {
      "msg_id": "7a2e9f10-...-b5c8",
      "numero": "5559876543",
      "status": "programado"
    }
  ]
}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- LISTAR -->
                    <div id="listar" class="scroll-mt-24 border rounded-xl p-6 mb-8 bg-white shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">GET</span>
                            <code class="text-lg">/api/v1/messages</code>
                        </div>
                        <p class="text-neutral-700 mb-6">
                            Devuelve tus mensajes, del más reciente al más antiguo, en páginas de <strong>50</strong>.
                        </p>

                        <h4 class="font-semibold mb-3">Parámetros de consulta</h4>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border rounded-lg">
                                <thead class="bg-neutral-100 text-left">
                                    <tr>
                                        <th class="p-3 font-semibold">Parámetro</th>
                                        <th class="p-3 font-semibold">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr>
                                        <td class="p-3"><code>status</code></td>
                                        <td class="p-3 text-neutral-700">
                                            Filtra por estado. Uno de <code>programado</code>, <code>por_enviar</code>,
                                            <code>en_cola</code>, <code>enviado</code>, <code>cancelado</code>,
                                            <code>error</code>. Un valor desconocido no da error: devuelve una página vacía.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3"><code>page</code></td>
                                        <td class="p-3 text-neutral-700">Número de página. Por defecto <code>1</code>.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="relative mb-6">
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>GET /api/v1/messages?status=enviado&amp;page=2</pre>
                            <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                        </div>

                        <h4 class="font-semibold mb-3">Respuesta <span class="text-green-700">200 OK</span></h4>
                        <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "current_page": 1,
  "data": [ ... objetos mensaje ... ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 3,
  "next_page_url": "...",
  "path": "...",
  "per_page": 50,
  "prev_page_url": null,
  "to": 50,
  "total": 118
}</pre>
                    </div>

                    <!-- CONSULTAR -->
                    <div id="consultar" class="scroll-mt-24 border rounded-xl p-6 mb-8 bg-white shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">GET</span>
                            <code class="text-lg">/api/v1/messages/{msg_id}</code>
                        </div>
                        <p class="text-neutral-700 mb-6">
                            Consulta un mensaje por su <code>msg_id</code> (el UUID que devolvió la creación,
                            <em>no</em> un id numérico).
                        </p>

                        <h4 class="font-semibold mb-3">Respuesta <span class="text-green-700">200 OK</span> — el objeto mensaje</h4>
                        <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto mb-6">{
  "msg_id": "9b1c7d4e-...-3f2a",
  "nombre": "Ana López",
  "numero": "5551234567",
  "mensaje": "Tu cita es mañana a las 10:00",
  "status": "enviado",
  "status_label": "Enviado",
  "fecha_envio": "2026-09-01",
  "hora_envio": "09:00:00",
  "sent_at": "2026-09-01 09:00:37",
  "error": null
}</pre>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border rounded-lg">
                                <thead class="bg-neutral-100 text-left">
                                    <tr>
                                        <th class="p-3 font-semibold">Campo</th>
                                        <th class="p-3 font-semibold">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr><td class="p-3"><code>msg_id</code></td><td class="p-3 text-neutral-700">Identificador público del mensaje (UUID).</td></tr>
                                    <tr><td class="p-3"><code>nombre</code></td><td class="p-3 text-neutral-700">Referencia que enviaste, o <code>null</code>.</td></tr>
                                    <tr><td class="p-3"><code>numero</code></td><td class="p-3 text-neutral-700">Número de destino.</td></tr>
                                    <tr><td class="p-3"><code>mensaje</code></td><td class="p-3 text-neutral-700">Texto del SMS.</td></tr>
                                    <tr><td class="p-3"><code>status</code></td><td class="p-3 text-neutral-700">Estado actual (ver <a href="#estados" class="text-green-700 hover:underline">ciclo de vida</a>).</td></tr>
                                    <tr><td class="p-3"><code>status_label</code></td><td class="p-3 text-neutral-700">El mismo estado, legible para mostrar al usuario.</td></tr>
                                    <tr><td class="p-3"><code>fecha_envio</code></td><td class="p-3 text-neutral-700"><code>AAAA-MM-DD</code> o <code>null</code>.</td></tr>
                                    <tr><td class="p-3"><code>hora_envio</code></td><td class="p-3 text-neutral-700"><code>HH:MM:SS</code> o <code>null</code>.</td></tr>
                                    <tr><td class="p-3"><code>sent_at</code></td><td class="p-3 text-neutral-700">Momento del envío confirmado, o <code>null</code> si aún no salió.</td></tr>
                                    <tr><td class="p-3"><code>error</code></td><td class="p-3 text-neutral-700">Motivo del fallo cuando <code>status</code> es <code>error</code>; si no, <code>null</code>.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CANCELAR -->
                    <div id="cancelar" class="scroll-mt-24 border rounded-xl p-6 mb-8 bg-white shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">DELETE</span>
                            <code class="text-lg">/api/v1/messages/{msg_id}</code>
                        </div>
                        <p class="text-neutral-700 mb-6">
                            Cancela un mensaje que todavía no ha salido. No borra el registro: lo pasa al estado
                            <code>cancelado</code>.
                        </p>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold mb-3">Respuesta <span class="text-green-700">200 OK</span></h4>
                                <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "message": "Mensaje cancelado",
  "status": "cancelado"
}</pre>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-3">Respuesta <span class="text-red-700">422</span> — ya no se puede</h4>
                                <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "error": "No se puede cancelar un mensaje ya enviado o finalizado."
}</pre>
                            </div>
                        </div>

                        <p class="text-sm text-neutral-600 mt-4">
                            Solo se pueden cancelar mensajes en estado <code>programado</code>,
                            <code>por_enviar</code>, <code>en_cola</code> o <code>error</code>. Un mensaje ya
                            <code>enviado</code> o <code>cancelado</code> devuelve <code>422</code>.
                        </p>
                    </div>
                </div>

                <!-- PROGRAMACIÓN -->
                <div id="programacion" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Programación de envíos</h2>
                    <p class="text-neutral-700 mb-4">
                        Para programar un mensaje, envía <code>fecha_envio</code> (<code>AAAA-MM-DD</code>) y
                        <code>hora_envio</code> (<code>HH:MM</code> o <code>HH:MM:SS</code>). Si los omites, el
                        mensaje entra en la cola de inmediato.
                    </p>
                    <div class="border-l-4 border-amber-500 bg-amber-50 p-4 rounded-r-lg text-sm text-neutral-800 space-y-3">
                        <p>
                            <strong>La salida tiene una granularidad de aproximadamente un minuto.</strong> Un proceso
                            programado revisa la cola cada minuto, asigna canal y pasa los mensajes a
                            <code>en_cola</code>. No esperes un envío instantáneo al milisegundo.
                        </p>
                        <p>
                            <strong>Un mensaje recién creado nunca aparece como <code>enviado</code>.</strong> El estado
                            <code>enviado</code> solo llega cuando el canal físico confirma el envío, lo que ocurre
                            segundos o minutos después. Consulta el <code>msg_id</code> más tarde para ver el
                            resultado final.
                        </p>
                    </div>
                </div>

                <!-- LONGITUD -->
                <div id="longitud" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Longitud del mensaje y segmentos</h2>
                    <p class="text-neutral-700 mb-6">
                        La API <strong>no limita</strong> la longitud del campo <code>mensaje</code>, pero la red SMS
                        sí: un mensaje largo se parte en varios segmentos y cada segmento se cobra por separado.
                        Conviene que lo controles desde tu aplicación.
                    </p>

                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm border rounded-lg">
                            <thead class="bg-neutral-100 text-left">
                                <tr>
                                    <th class="p-3 font-semibold">Codificación</th>
                                    <th class="p-3 font-semibold">Un segmento</th>
                                    <th class="p-3 font-semibold">Por segmento al concatenar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="p-3">GSM-7 (texto básico, sin acentos ni emoji)</td>
                                    <td class="p-3">160 caracteres</td>
                                    <td class="p-3">153 caracteres</td>
                                </tr>
                                <tr>
                                    <td class="p-3">Unicode (acentos, ñ, emoji)</td>
                                    <td class="p-3">70 caracteres</td>
                                    <td class="p-3">67 caracteres</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-sm text-neutral-600">
                        Un solo carácter acentuado o un emoji cambia todo el mensaje a Unicode y reduce la capacidad
                        de 160 a 70 caracteres. Como referencia, el panel de FastSMS corta en 5 segmentos:
                        765 caracteres en GSM-7, 335 en Unicode.
                    </p>
                </div>

                <!-- ERRORES -->
                <div id="errores" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Códigos de error</h2>

                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm border rounded-lg">
                            <thead class="bg-neutral-100 text-left">
                                <tr>
                                    <th class="p-3 font-semibold">Código</th>
                                    <th class="p-3 font-semibold">Cuándo ocurre</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr>
                                    <td class="p-3"><code>401</code></td>
                                    <td class="p-3 text-neutral-700">Falta el token, es inválido o fue revocado.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>404</code></td>
                                    <td class="p-3 text-neutral-700">El <code>msg_id</code> no existe o no pertenece a tu cuenta.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>422</code></td>
                                    <td class="p-3 text-neutral-700">El cuerpo no pasó la validación, o intentaste cancelar un mensaje ya finalizado.</td>
                                </tr>
                                <tr>
                                    <td class="p-3"><code>302</code></td>
                                    <td class="p-3 text-neutral-700">
                                        No es un error de la API: olvidaste la cabecera
                                        <code>Accept: application/json</code> y el servidor te redirigió al login.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">Error de validación (<code>422</code>)</h4>
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "message": "The numero field is required.",
  "errors": {
    "numero": ["The numero field is required."]
  }
}</pre>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">No encontrado (<code>404</code>)</h4>
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">{
  "error": "Mensaje no encontrado"
}</pre>
                        </div>
                    </div>
                </div>

                <!-- EJEMPLOS -->
                <div id="ejemplos" class="scroll-mt-24 mt-16">
                    <h2 class="text-3xl font-semibold mb-4">Ejemplos de integración</h2>
                    <p class="text-neutral-700 mb-6">El mismo envío, en tres lenguajes.</p>

                    <div data-tabs>
                        <div class="flex gap-2 mb-4" role="tablist">
                            <button type="button" data-tab="curl" class="px-4 py-2 rounded-lg text-sm border bg-green-600 text-white border-green-600">cURL</button>
                            <button type="button" data-tab="php" class="px-4 py-2 rounded-lg text-sm border bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100">PHP</button>
                            <button type="button" data-tab="js" class="px-4 py-2 rounded-lg text-sm border bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100">JavaScript</button>
                        </div>

                        <div data-pane="curl" class="relative">
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>curl -X POST {{ url('/api/v1/messages') }} \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Ana",
    "numero": "5551234567",
    "mensaje": "Tu cita es manana a las 10:00"
  }'</pre>
                            <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                        </div>

                        <div data-pane="php" class="relative hidden">
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>&lt;?php

$payload = json_encode([
    'nombre'  =&gt; 'Ana',
    'numero'  =&gt; '5551234567',
    'mensaje' =&gt; 'Tu cita es manana a las 10:00',
]);

$ch = curl_init('{{ url('/api/v1/messages') }}');
curl_setopt_array($ch, [
    CURLOPT_POST           =&gt; true,
    CURLOPT_POSTFIELDS     =&gt; $payload,
    CURLOPT_RETURNTRANSFER =&gt; true,
    CURLOPT_HTTPHEADER     =&gt; [
        'Authorization: Bearer TU_TOKEN',
        'Accept: application/json',
        'Content-Type: application/json',
    ],
]);

$respuesta = json_decode(curl_exec($ch), true);
curl_close($ch);

echo $respuesta['msg_id'];</pre>
                            <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                        </div>

                        <div data-pane="js" class="relative hidden">
                            <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto" data-code>const respuesta = await fetch('{{ url('/api/v1/messages') }}', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer TU_TOKEN',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    nombre: 'Ana',
    numero: '5551234567',
    mensaje: 'Tu cita es manana a las 10:00',
  }),
});

const datos = await respuesta.json();
console.log(datos.msg_id);</pre>
                            <button type="button" data-copy class="absolute top-2 right-2 text-xs px-2 py-1 rounded bg-neutral-800 text-neutral-300 hover:bg-neutral-700">Copiar</button>
                        </div>
                    </div>

                    <div class="mt-10 border rounded-xl p-6 bg-neutral-50 text-center">
                        <h3 class="text-xl font-semibold mb-2">¿Todo listo?</h3>
                        <p class="text-neutral-700 mb-4">Crea tu token y envía tu primer mensaje.</p>
                        @auth
                            <a href="{{ route('api-tokens.index') }}" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">Crear un token</a>
                        @else
                            <div class="flex gap-3 justify-center">
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">Registrarse</a>
                                @endif
                                <a href="{{ route('login') }}" class="px-6 py-3 border border-neutral-300 rounded-lg hover:bg-white">Iniciar sesión</a>
                            </div>
                        @endauth
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script>
        document.addEventListener('click', function (event) {
            const boton = event.target.closest('[data-copy]');
            if (! boton) {
                return;
            }

            const bloque = boton.parentElement.querySelector('[data-code]');
            if (! bloque) {
                return;
            }

            navigator.clipboard.writeText(bloque.textContent).then(function () {
                const original = boton.textContent;
                boton.textContent = 'Copiado';
                setTimeout(function () {
                    boton.textContent = original;
                }, 1500);
            });
        });

        document.querySelectorAll('[data-tabs]').forEach(function (grupo) {
            const activa = 'bg-green-600 text-white border-green-600';
            const inactiva = 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100';

            grupo.querySelectorAll('[data-tab]').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    grupo.querySelectorAll('[data-tab]').forEach(function (otro) {
                        const esActivo = otro === boton;
                        otro.className = 'px-4 py-2 rounded-lg text-sm border ' + (esActivo ? activa : inactiva);
                    });

                    grupo.querySelectorAll('[data-pane]').forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.dataset.pane !== boton.dataset.tab);
                    });
                });
            });
        });
    </script>

</x-layouts.site>

<x-layouts.site title="Mensajería API">

    <!-- HERO -->
    <section class="pt-20 pb-24 bg-neutral-100" id="hero">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <h1 class="text-4xl font-semibold leading-tight mb-4">Conecta tu App con un servicio de envío de mensajes confiable</h1>
                <p class="text-neutral-700 text-lg mb-6">Nuestra API permite que cualquier aplicación envíe mensajes SMS con reportes de entrega, confirmaciones y más.</p>
                <div class="flex gap-4">
                    <a href="{{ route('docs') }}" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-lg text-center">Ver la API</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-6 py-3 border border-neutral-300 rounded-lg hover:bg-white text-lg text-center">Registrarse</a>
                    @endif
                </div>
            </div>
            <div>
                <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" class="w-80 mx-auto" alt="SMS Icon" />
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="py-24" id="features">
        <div class="max-w-6xl mx-auto px-6">
            <h3 class="text-3xl font-semibold text-center mb-12">¿Qué ofrece nuestro servicio?</h3>

            <div class="grid md:grid-cols-3 gap-10">
                <div class="p-6 border rounded-xl bg-white shadow-sm text-center">
                    <h4 class="text-xl font-semibold mb-2">Envío de mensajes</h4>
                    <p class="text-neutral-600">Envía SMS desde cualquier aplicación conectando directo a la API.</p>
                </div>

                <div class="p-6 border rounded-xl bg-white shadow-sm text-center">
                    <h4 class="text-xl font-semibold mb-2">Confirmación de entrega</h4>
                    <p class="text-neutral-600">Tu app recibe confirmación automática cuando el mensaje es procesado.</p>
                </div>

                <div class="p-6 border rounded-xl bg-white shadow-sm text-center">
                    <h4 class="text-xl font-semibold mb-2">Integración sencilla</h4>
                    <p class="text-neutral-600">Rutas simples, JSON claro y documentación fácil de seguir.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- API SECTION -->
    <section class="py-24 bg-neutral-100" id="api">
        <div class="max-w-6xl mx-auto px-6">
            <h3 class="text-3xl font-semibold text-center mb-4">API de Mensajería</h3>
            <p class="text-neutral-700 text-center mb-12">Una API REST sencilla: autentícate con un token y envía tu primer SMS en dos minutos.</p>

            <div class="grid md:grid-cols-2 gap-10">
                <div>
                    <h4 class="text-xl font-semibold mb-4">Enviar un mensaje</h4>
                    <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">POST /api/v1/messages
Authorization: Bearer {tu_token}
Accept: application/json

{
  "numero": "5551234567",
  "mensaje": "Hola desde la API"
}</pre>
                </div>
                <div>
                    <h4 class="text-xl font-semibold mb-4">Consultar su estado</h4>
                    <pre class="bg-black text-green-400 p-4 rounded-lg text-sm overflow-x-auto">GET /api/v1/messages/{msg_id}
Authorization: Bearer {tu_token}

{
  "msg_id": "9b1c...",
  "status": "en_cola",
  "status_label": "En cola"
}</pre>
                </div>
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('docs') }}" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-lg">Ver documentación completa &rarr;</a>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="py-24" id="precios">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <h3 class="text-3xl font-semibold mb-12">Planes de precios</h3>

            <div class="grid md:grid-cols-3 gap-10">
                <div class="border p-8 rounded-xl shadow-sm bg-white">
                    <h4 class="text-xl font-semibold mb-2">Básico</h4>
                    <p class="text-neutral-700 mb-4">Perfecto para pruebas</p>
                    <p class="text-4xl font-semibold mb-4">Gratis</p>
                    <ul class="text-neutral-600 text-sm space-y-2 mb-6">
                        <li>✓ 50 mensajes / mes</li>
                        <li>✓ API completa</li>
                    </ul>
                    <a href="#contacto" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 block text-center">Empezar</a>
                </div>

                <div class="border p-8 rounded-xl shadow-sm bg-white border-green-600 ring-1 ring-green-600">
                    <h4 class="text-xl font-semibold mb-2">Pro</h4>
                    <p class="text-neutral-700 mb-4">Para proyectos reales</p>
                    <p class="text-4xl font-semibold mb-4">$499 <span class="text-neutral-600 text-sm">/mes</span></p>
                    <ul class="text-neutral-600 text-sm space-y-2 mb-6">
                        <li>✓ 2,000 mensajes</li>
                        <li>✓ Confirmación de entrega</li>
                    </ul>
                    <a href="#contacto" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 block text-center">Elegir plan</a>
                </div>

                <div class="border p-8 rounded-xl shadow-sm bg-white">
                    <h4 class="text-xl font-semibold mb-2">Empresarial</h4>
                    <p class="text-neutral-700 mb-4">Volumen alto</p>
                    <p class="text-4xl font-semibold mb-4">Custom</p>
                    <ul class="text-neutral-600 text-sm space-y-2 mb-6">
                        <li>✓ Mensajería ilimitada</li>
                        <li>✓ Integración personalizada</li>
                    </ul>
                    <a href="#contacto" class="px-6 py-3 bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 block text-center">Contactar</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="py-24 bg-neutral-100" id="contacto">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h3 class="text-3xl font-semibold mb-6">Contacto</h3>
            <p class="text-neutral-700 mb-8">¿Quieres integrar nuestra API o necesitas soporte? Envíanos un mensaje.</p>

            <form class="grid gap-4 max-w-lg mx-auto">
                <input type="text" placeholder="Nombre" class="border p-3 rounded-lg w-full" />
                <input type="email" placeholder="Correo" class="border p-3 rounded-lg w-full" />
                <textarea placeholder="Mensaje" class="border p-3 rounded-lg h-32 w-full"></textarea>
                <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">Enviar</button>
            </form>
        </div>
    </section>
</x-layouts.site>

@props(['title' => 'FastSMS'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-neutral-900 font-sans">

    <!-- NAVBAR -->
    <header class="w-full py-4 border-b border-neutral-200 bg-white sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-xl font-semibold">FastSMS</a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ url('/') }}#features" class="hover:text-green-600">Funciones</a>
                <a href="{{ url('/') }}#api" class="hover:text-green-600">API</a>
                <a href="{{ route('docs') }}" class="hover:text-green-600">Docs</a>
                <a href="{{ url('/') }}#precios" class="hover:text-green-600">Precios</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Panel</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-green-600">Log in</a>
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    {{ $slot }}

    <!-- FOOTER -->
    <footer class="py-12 text-center text-neutral-600 border-t bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <p>© {{ date('Y') }} FastSMS — Todos los derechos reservados.</p>
            <div class="mt-4 flex justify-center gap-4 text-xs">
                <a href="#" class="hover:underline">Términos</a>
                <a href="#" class="hover:underline">Privacidad</a>
                <a href="mailto:l22020879@veracruz.tecnm.mx" class="hover:underline">Soporte</a>
            </div>
        </div>
    </footer>

</body>
</html>

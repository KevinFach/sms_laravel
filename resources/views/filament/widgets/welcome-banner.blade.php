<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-12">
            <x-filament::button
                href="{{ route('filament.admin.resources.messages.create') }}"
                tag="a"
                icon="heroicon-m-plus"
                size="lg"
            >
                Crear Mensaje
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.messages.index') }}"
                tag="a"
                icon="heroicon-m-chat-bubble-left-right"
                color="gray"
                size="lg"
            >
                Ver Historial
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
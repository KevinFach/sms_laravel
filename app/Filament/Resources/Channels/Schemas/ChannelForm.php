<?php

namespace App\Filament\Resources\Channels\Schemas;

use App\Enums\ChannelType;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del canal')
                    ->required()
                    ->maxLength(255),

                TextInput::make('clave')
                    ->label('Clave (token del gateway)')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->default(fn (): string => Str::random(40))
                    ->helperText('El gateway ESP32 usa esta clave para autenticarse.')
                    // Los canales 51x.dev no reciben llamadas entrantes: no hay clave que
                    // mostrar. Si el campo queda oculto, el modelo la autogenera al crear.
                    ->visible(fn (Get $get): bool => $get('tipo') !== ChannelType::Api51x->value),

                Select::make('tipo')
                    ->label('Tipo')
                    ->options(ChannelType::options())
                    ->default(ChannelType::Esp32->value)
                    ->required()
                    ->live(),

                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ])
                    ->default('activo')
                    ->required(),

                Toggle::make('is_default')
                    ->label('Canal predeterminado')
                    ->helperText('Se usa cuando un cliente no tiene canal asignado.')
                    ->inline(false),

                Section::make('Credenciales 51x.dev')
                    ->description('Datos de la cuenta de 51x.dev a la que se empujan los mensajes.')
                    ->visible(fn (Get $get): bool => $get('tipo') === ChannelType::Api51x->value)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('config.base_url')
                            ->label('URL base')
                            ->url()
                            ->required()
                            ->default(fn (): string => config('services.51x.base_url')),

                        TextInput::make('config.api_key')
                            ->label('API key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('API key creada en el panel de 51x.dev (header X-API-Key).'),

                        TextInput::make('config.device_id')
                            ->label('Device ID')
                            ->helperText('Se puede omitir si en 51x.dev hay un solo dispositivo registrado.'),

                        TextInput::make('config.pais')
                            ->label('Prefijo de país')
                            ->default('+52')
                            ->helperText('Se antepone a los números que no traen "+".'),
                    ])
                    ->footerActions([
                        Action::make('probarConexion')
                            ->label('Probar conexión')
                            ->icon('heroicon-m-signal')
                            ->action(function (Get $get): void {
                                static::testConnection(
                                    (string) $get('config.base_url'),
                                    (string) $get('config.api_key'),
                                );
                            }),
                    ]),
            ]);
    }

    /**
     * Valida las credenciales contra 51x.dev y lista sus dispositivos, que es de
     * donde sale el `device_id` a configurar.
     */
    protected static function testConnection(string $baseUrl, string $apiKey): void
    {
        $baseUrl = rtrim($baseUrl ?: config('services.51x.base_url'), '/');

        try {
            $health = Http::baseUrl($baseUrl)->acceptJson()->timeout(10)->get('/health');

            if ($health->failed()) {
                Notification::make()
                    ->title('El servicio no responde')
                    ->body("GET {$baseUrl}/health devolvió {$health->status()}.")
                    ->danger()
                    ->send();

                return;
            }

            $devices = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->acceptJson()
                ->timeout(10)
                ->get('/api/devices');
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo conectar con 51x.dev')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($devices->failed()) {
            Notification::make()
                ->title('Credenciales rechazadas')
                ->body("GET {$baseUrl}/api/devices devolvió {$devices->status()}. Revisa la API key.")
                ->danger()
                ->send();

            return;
        }

        $lista = collect($devices->json())
            ->map(fn (array $device): string => sprintf(
                '%s — %s (%s)',
                $device['id'] ?? '?',
                $device['name'] ?? 'sin nombre',
                ($device['connected'] ?? false) ? 'conectado' : 'desconectado',
            ))
            ->all();

        Notification::make()
            ->title('Conexión correcta')
            ->body($lista === []
                ? 'No hay dispositivos registrados en 51x.dev.'
                : 'Dispositivos: '.implode(' · ', $lista))
            ->success()
            ->persistent()
            ->send();
    }
}

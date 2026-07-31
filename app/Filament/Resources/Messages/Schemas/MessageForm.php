<?php

namespace App\Filament\Resources\Messages\Schemas;

use App\Enums\MessageStatus;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageForm
{
    /**
     * Detecta si el texto contiene caracteres fuera del charset GSM-7 básico.
     * Si devuelve true, el mensaje se enviará como Unicode (70 chars/SMS).
     */
    public static function hasUnicodeChars(string $text): bool
    {
        // Charset GSM-7 básico (los caracteres dentro de este rango no necesitan Unicode)
        return (bool) preg_match(
            '/[^\x20-\x7E\n\r\x08\t'
            .'@£\$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ'
            .'¡¿äöñüàÄÖÑÜ§¤]/u',
            $text
        );
    }

    /**
     * Genera el hint de contador de caracteres SMS en tiempo real.
     */
    public static function smsHint(?string $state): string
    {
        if (empty($state)) {
            return '0 / 160 caracteres (GSM-7)';
        }

        $isUnicode = self::hasUnicodeChars($state);
        $singleLimit = $isUnicode ? 70 : 160;
        $multiLimit = $isUnicode ? 67 : 153;
        $charset = $isUnicode ? 'Unicode (UCS-2)' : 'GSM-7';
        $count = mb_strlen($state);

        if ($count <= $singleLimit) {
            $info = $isUnicode ? ' — ⚠ mensaje Unicode' : '';

            return "{$count} / {$singleLimit} caracteres ({$charset}){$info}";
        }

        $segments = (int) ceil($count / $multiLimit);
        $warning = $isUnicode ? ' — ⚠ Unicode' : '';

        return "{$count} caracteres · {$segments} partes SMS ({$charset}){$warning}";
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre del mensaje')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('numero')
                            ->label('Número de teléfono')
                            ->required()
                            ->tel(),

                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'nombre')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin cliente'),

                        Select::make('channel_id')
                            ->label('Canal')
                            ->relationship('channel', 'nombre')
                            ->searchable()
                            ->preload()
                            ->placeholder('Asignar automáticamente'),
                    ]),

                Section::make('Mensaje SMS')
                    ->schema([
                        Textarea::make('mensaje')
                            ->label('Contenido del mensaje')
                            ->required()
                            ->rows(3)
                            ->live()
                            ->hint(fn (?string $state): string => self::smsHint($state))
                            ->hintColor(fn (?string $state): string => self::hasUnicodeChars($state ?? '') ? 'warning' : 'primary')
                            ->rules([
                                function (): Closure {
                                    return function (string $attribute, $value, Closure $fail) {
                                        $isUnicode = self::hasUnicodeChars($value);
                                        // Máximo 5 segmentos SMS
                                        $hardLimit = $isUnicode ? (67 * 5) : (153 * 5);
                                        if (mb_strlen($value) > $hardLimit) {
                                            $fail("El mensaje excede el límite máximo de {$hardLimit} caracteres (".($isUnicode ? 'Unicode' : 'GSM-7').').');
                                        }
                                    };
                                },
                            ]),
                    ]),

                Section::make('Evento')
                    ->columns(2)
                    ->description('Fecha y hora del evento o cita que se está recordando')
                    ->schema([
                        DatePicker::make('fecha_evento')
                            ->label('Fecha del evento')
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        TimePicker::make('hora_evento')
                            ->label('Hora del evento')
                            ->seconds(false),
                    ]),

                Section::make('Programación de envío')
                    ->columns(2)
                    ->description('Cuándo debe enviarse el SMS. Si se deja vacío, se envía de inmediato.')
                    ->schema([
                        DatePicker::make('fecha_envio')
                            ->label('Fecha de envío')
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        TimePicker::make('hora_envio')
                            ->label('Hora de envío')
                            ->seconds(false),
                    ]),

                Section::make('Información del sistema')
                    ->visibleOn('edit')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options(MessageStatus::options())
                            ->helperText('Cambiar el estado manualmente puede saltar la máquina de estados.'),

                        TextInput::make('error_message')
                            ->label('Detalle de error')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('msg_id')
                            ->label('Hash del Mensaje')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('fecha')
                            ->label('Fecha de creación')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

            ]);
    }
}

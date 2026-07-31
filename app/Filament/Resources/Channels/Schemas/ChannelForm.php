<?php

namespace App\Filament\Resources\Channels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
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
                    ->helperText('El gateway ESP32 usa esta clave para autenticarse.'),

                Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'esp32' => 'ESP32',
                        'otro' => 'Otro',
                    ])
                    ->default('esp32')
                    ->required(),

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
            ]);
    }
}

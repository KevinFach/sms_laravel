<?php

namespace App\Filament\Resources\Messages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('mensaje')
                    ->required(),

                TextInput::make('numero')
                    ->required(),

                Toggle::make('estatus')
                    ->required(),

                // Mostrar el hash (solo lectura)
                TextInput::make('msg_id')
                    ->label('Hash del Mensaje')
                    ->disabled()
                    ->visibleOn('edit')     // solo al editar
                    ->dehydrated(false),     // no enviar en form-request

                // Mostrar la fecha (solo lectura)
                DateTimePicker::make('fecha')
                    ->label('Fecha de creación')
                    ->disabled()
                    
                    ->visibleOn('edit')     // solo al editar
                    ->dehydrated(false),     // no enviar en form-request
            ]);
    }
}

<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del cliente')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->maxLength(255),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ])
                    ->default('activo')
                    ->required(),

                Select::make('channel_id')
                    ->label('Canal asignado')
                    ->relationship('channel', 'nombre')
                    ->searchable()
                    ->preload()
                    ->placeholder('Usar canal predeterminado')
                    ->helperText('Si se deja vacío, los mensajes de este cliente salen por el canal predeterminado.'),

                Select::make('user_id')
                    ->label('Usuario del panel')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Sin usuario vinculado')
                    ->helperText('Usuario cuyos tokens de API quedan asociados a este cliente.'),
            ]);
    }
}

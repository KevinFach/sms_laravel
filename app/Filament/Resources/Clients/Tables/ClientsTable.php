<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Enums\MessageStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'activo' ? 'success' : 'gray'),

                TextColumn::make('channel.nombre')
                    ->label('Canal')
                    ->badge()
                    ->placeholder('Predeterminado')
                    ->color('info'),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('—'),

                TextColumn::make('messages_count')
                    ->label('Mensajes')
                    ->counts('messages')
                    ->badge(),

                TextColumn::make('sent_this_month_count')
                    ->label('Enviados (mes)')
                    ->counts([
                        'messages as sent_this_month_count' => fn (Builder $query) => $query
                            ->where('status', MessageStatus::Enviado->value)
                            ->where('sent_at', '>=', now()->startOfMonth()),
                    ])
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

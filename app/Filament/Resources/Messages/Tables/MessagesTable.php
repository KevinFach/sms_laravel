<?php

namespace App\Filament\Resources\Messages\Tables;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Services\Channels\ChannelDriverManager;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('numero')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mensaje')
                    ->label('Mensaje')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fecha_evento')
                    ->label('Fecha Evento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('hora_evento')
                    ->label('Hora Evento'),

                TextColumn::make('fecha_envio')
                    ->label('Envío Programado')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('hora_envio')
                    ->label('Hora Envío'),

                TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->sortable(),

                TextColumn::make('client.nombre')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('channel.nombre')
                    ->label('Canal')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('fecha')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(MessageStatus::options()),

                SelectFilter::make('channel_id')
                    ->label('Canal')
                    ->relationship('channel', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Message $record): bool => ! $record->status->isFinal())
                    ->action(function (Message $record, ChannelDriverManager $drivers): void {
                        // En canales push hay que frenar el envío del lado del proveedor.
                        $frenado = $drivers->forMessage($record)->cancel($record);

                        try {
                            $record->transitionTo(MessageStatus::Cancelado);
                        } catch (\DomainException) {
                            // Estado final: no-op.
                        }

                        if (! $frenado) {
                            Notification::make()
                                ->title('Cancelado localmente')
                                ->body('El proveedor ya había despachado el mensaje, así que el SMS podría salir de todos modos.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

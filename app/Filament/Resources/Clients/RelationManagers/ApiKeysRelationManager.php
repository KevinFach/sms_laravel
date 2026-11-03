<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\Client;
use App\Models\ClientApiKey;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * API keys con las que un sistema externo (p.ej. notifica_ya) actúa en nombre del cliente.
 */
class ApiKeysRelationManager extends RelationManager
{
    protected static string $relationship = 'apiKeys';

    protected static ?string $title = 'API keys';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre'),

                TextColumn::make('prefix')
                    ->label('Key')
                    ->formatStateUsing(fn (string $state): string => $state.'…'),

                TextColumn::make('last_used_at')
                    ->label('Último uso')
                    ->since()
                    ->placeholder('Nunca'),

                TextColumn::make('revoked_at')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (ClientApiKey $record): string => $record->isRevoked() ? 'Revocada' : 'Activa')
                    ->color(fn (string $state): string => $state === 'Activa' ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generar API key')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->helperText('Para identificarla, p.ej. "notifica_ya - Juan Pérez".')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data): void {
                        /** @var Client $client */
                        $client = $this->getOwnerRecord();
                        $plainKey = ClientApiKey::issue($client, $data['name']);

                        Notification::make()
                            ->title('API key generada')
                            ->body("Cópiala ahora, no se volverá a mostrar:\n\n{$plainKey}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revocar')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ClientApiKey $record): bool => ! $record->isRevoked())
                    ->action(fn (ClientApiKey $record) => $record->revoke()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

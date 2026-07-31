<?php

namespace App\Filament\Widgets;

use App\Enums\MessageStatus;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $byStatus = Message::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (MessageStatus $s): int => (int) ($byStatus[$s->value] ?? 0);

        return [
            Stat::make('Total Mensajes', Message::count())
                ->description('Historial completo')
                ->descriptionIcon('heroicon-m-chat-bubble-bottom-center-text')
                ->color('gray'),

            Stat::make('Programados', $count(MessageStatus::Programado))
                ->description('Esperan su fecha/hora')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Por enviar', $count(MessageStatus::PorEnviar))
                ->description('A la espera del canal')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('warning'),

            Stat::make('En cola', $count(MessageStatus::EnCola))
                ->description('Listos en el canal')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Enviados', $count(MessageStatus::Enviado))
                ->description('Confirmados por el canal')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Con error', $count(MessageStatus::Error))
                ->description('Fallas a revisar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

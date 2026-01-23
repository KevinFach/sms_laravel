<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MessageStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Mensajes', \App\Models\Message::count())
                ->description('Historial completo')
                ->descriptionIcon('heroicon-m-chat-bubble-bottom-center-text')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info'),

            Stat::make('Mensajes Pendientes', \App\Models\Message::where('estatus', 0)->count())
                ->description('Por procesar')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->chart([15, 4, 10, 2, 12, 4, 9])
                ->color('warning'),

            Stat::make('Mensajes Enviados', \App\Models\Message::where('estatus', 1)->count())
                ->description('Éxito total')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([1, 5, 2, 8, 3, 11, 20])
                ->color('success'),
        ];
    }
}

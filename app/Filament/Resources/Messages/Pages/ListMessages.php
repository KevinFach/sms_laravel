<?php

namespace App\Filament\Resources\Messages\Pages;

use App\Filament\Resources\Messages\MessageResource;
use App\Exports\MessagesSampleExport;
use App\Imports\MessagesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label('Descargar Ejemplo')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(fn () => Excel::download(new MessagesSampleExport, 'ejemplo_mensajes.xlsx')),

            Action::make('importExcel')
                ->label('Importar Excel')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Archivo Excel')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv']),
                ])
                ->action(function (array $data) {
                    $file = Storage::disk('local')->path($data['attachment']);
                    
                    try {
                        Excel::import(new MessagesImport, $file);
                        
                        Notification::make()
                            ->title('Importación completada')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}

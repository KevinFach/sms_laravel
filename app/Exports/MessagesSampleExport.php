<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MessagesSampleExport implements FromArray, WithColumnFormatting, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'nombre' => 'agenda firma',
                'fecha_evento' => '2026-05-15',
                'hora_evento' => '10:30',
                'fecha_envio' => '2026-05-14',
                'hora_envio' => '09:00',
                'mensaje' => 'Recuerda tu cita manana a las 10:30',
                'numero' => '521234567890',
            ],
            [
                'nombre' => 'agenda firma reco 1',
                'fecha_evento' => '2026-05-15',
                'hora_evento' => '10:30',
                'fecha_envio' => '2026-05-13',
                'hora_envio' => '09:00',
                'mensaje' => 'Recordatorio: tienes una cita el 15 de mayo a las 10:30',
                'numero' => '521098765432',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'nombre',
            'fecha_evento',
            'hora_evento',
            'fecha_envio',
            'hora_envio',
            'mensaje',
            'numero',
        ];
    }

    /**
     * Fuerza las columnas de fecha y hora como texto para evitar
     * que Excel las convierta a seriales numéricos al importar.
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // fecha_evento
            'C' => NumberFormat::FORMAT_TEXT, // hora_evento
            'D' => NumberFormat::FORMAT_TEXT, // fecha_envio
            'E' => NumberFormat::FORMAT_TEXT, // hora_envio
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MessagesSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'mensaje' => 'Hola, este es un mensaje de prueba',
                'numero' => '521234567890',
            ],
            [
                'mensaje' => 'Recuerda tu cita mañana',
                'numero' => '521098765432',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'mensaje',
            'numero',
        ];
    }
}

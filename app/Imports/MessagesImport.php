<?php

namespace App\Imports;

use App\Models\Message;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MessagesImport implements SkipsOnError, ToModel, WithHeadingRow
{
    use SkipsErrors;

    /**
     * Convierte un valor de celda Excel a formato Y-m-d.
     * Maneja seriales numéricos de Excel y strings de fecha.
     */
    private function parseExcelDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Serial numérico de Excel (entero o float >= 1)
        if (is_numeric($value) && (float) $value >= 1) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception) {
                // fallthrough
            }
        }

        // String de fecha
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Convierte un valor de celda Excel a formato H:i:s.
     * Maneja fracciones de día de Excel (0.5 = 12:00:00) y strings de tiempo.
     */
    private function parseExcelTime(mixed $value): ?string
    {
        if (empty($value) && $value !== 0) {
            return null;
        }

        // Fracción de día de Excel (valor entre 0 y 1 exclusivo)
        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $totalSeconds = (int) round((float) $value * 86400);
            $h = intdiv($totalSeconds, 3600);
            $m = intdiv($totalSeconds % 3600, 60);
            $s = $totalSeconds % 60;

            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        // String de tiempo (e.g. "14:00", "14:00:00")
        try {
            return Carbon::parse((string) $value)->format('H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    public function model(array $row): ?Message
    {
        if (empty($row['mensaje']) || empty($row['numero'])) {
            return null;
        }

        return new Message([
            'nombre' => $row['nombre'] ?? null,
            'mensaje' => $row['mensaje'],
            'numero' => $row['numero'],
            'fecha_evento' => $this->parseExcelDate($row['fecha_evento'] ?? null),
            'hora_evento' => $this->parseExcelTime($row['hora_evento'] ?? null),
            'fecha_envio' => $this->parseExcelDate($row['fecha_envio'] ?? null),
            'hora_envio' => $this->parseExcelTime($row['hora_envio'] ?? null),
        ]);
    }
}

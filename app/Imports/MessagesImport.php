<?php

namespace App\Imports;

use App\Models\Message;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MessagesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['mensaje']) || empty($row['numero'])) {
            return null;
        }

        return new Message([
            'mensaje' => $row['mensaje'],
            'numero'  => $row['numero'],
        ]);
    }
}

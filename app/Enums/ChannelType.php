<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de canal de envío.
 *
 * Determina el driver que usa el dispatcher:
 *   Esp32 / Otro → pull  (el dispositivo jala los mensajes de /api/v1/gateway/pendientes)
 *   Api51x       → push  (Laravel empuja el mensaje al API de 51x.dev)
 */
enum ChannelType: string implements HasColor, HasLabel
{
    case Esp32 = 'esp32';
    case Api51x = '51x';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Esp32 => 'ESP32',
            self::Api51x => '51x.dev',
            self::Otro => 'Otro',
        };
    }

    /** Color de badge para Filament. */
    public function color(): string
    {
        return match ($this) {
            self::Esp32 => 'warning',
            self::Api51x => 'info',
            self::Otro => 'gray',
        };
    }

    /** Contrato Filament\Support\Contracts\HasLabel. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** Contrato Filament\Support\Contracts\HasColor. */
    public function getColor(): string
    {
        return $this->color();
    }

    /** El canal recibe los mensajes por push desde Laravel (en vez de jalarlos él mismo). */
    public function isPush(): bool
    {
        return $this === self::Api51x;
    }

    /** El canal se autentica contra nuestra API con la columna `clave`. */
    public function requiresGatewayKey(): bool
    {
        return $this !== self::Api51x;
    }

    /**
     * Opciones etiquetadas para filtros/formularios.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}

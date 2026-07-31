<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Máquina de estados de un mensaje SMS.
 *
 * Flujo normal:
 *   programado ─(llegó la hora)→ por_enviar ─(dispatcher asigna canal)→ en_cola ─(canal confirma)→ enviado
 * Ramas:
 *   cualquier estado no-final → cancelado (usuario)
 *   por_enviar / en_cola → error (falla del canal)
 */
enum MessageStatus: string implements HasColor, HasLabel
{
    case PorEnviar = 'por_enviar';
    case EnCola = 'en_cola';
    case Programado = 'programado';
    case Enviado = 'enviado';
    case Cancelado = 'cancelado';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PorEnviar => 'Por enviar',
            self::EnCola => 'En cola',
            self::Programado => 'Programado',
            self::Enviado => 'Enviado',
            self::Cancelado => 'Cancelado',
            self::Error => 'Error',
        };
    }

    /** Color de badge para Filament. */
    public function color(): string
    {
        return match ($this) {
            self::PorEnviar => 'warning',
            self::EnCola => 'info',
            self::Programado => 'info',
            self::Enviado => 'success',
            self::Cancelado => 'gray',
            self::Error => 'danger',
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

    /**
     * Estados a los que se puede transicionar desde el actual.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Programado => [self::PorEnviar, self::EnCola, self::Enviado, self::Cancelado, self::Error],
            self::PorEnviar => [self::EnCola, self::Enviado, self::Cancelado, self::Error],
            self::EnCola => [self::Enviado, self::Cancelado, self::Error],
            self::Error => [self::PorEnviar, self::EnCola, self::Cancelado], // permite reintento
            self::Enviado => [], // final
            self::Cancelado => [], // final
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Estados aún "en tránsito" (no finales).
     *
     * @return array<int, self>
     */
    public static function pendingStates(): array
    {
        return [self::Programado, self::PorEnviar, self::EnCola];
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

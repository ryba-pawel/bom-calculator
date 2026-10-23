<?php

declare(strict_types=1);

namespace App\Enum;

enum Unit: string
{
    case Piece = 'szt';
    case Meter = 'm';
    case SquareMeter = 'm2';
    case Kilogram = 'kg';
    case Liter = 'l';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'sztuka (szt)',
            self::Meter => 'metr (m)',
            self::SquareMeter => 'metr kwadratowy (m2)',
            self::Kilogram => 'kilogram (kg)',
            self::Liter => 'litr (l)',
        };
    }
}

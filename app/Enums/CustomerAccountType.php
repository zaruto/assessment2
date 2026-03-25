<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CustomerAccountType: string implements HasColor, HasIcon, HasLabel
{
    case Institutional = 'Institutional';
    case Retail = 'Retail';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Institutional => 'info',
            self::Retail => 'success',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Institutional => 'Institutional (Allocated - Bar-Level Tracking)',
            self::Retail => 'Retail (Unallocated - Pool Storage)',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Institutional => Heroicon::BuildingLibrary,
            self::Retail => Heroicon::User,
        };
    }
}

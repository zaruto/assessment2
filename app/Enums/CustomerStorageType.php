<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CustomerStorageType: string implements HasColor, HasIcon, HasLabel
{
    case Allocated = 'Allocated';
    case Unallocated = 'Unallocated';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Allocated => 'success',
            self::Unallocated => 'warning',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Allocated => Heroicon::LockClosed,
            self::Unallocated => Heroicon::LockOpen,
        };
    }
}

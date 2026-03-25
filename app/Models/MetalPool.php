<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalPool extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'metal_id',
        'total_quantity_kg',
        'total_units',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_quantity_kg' => 'decimal:4',
            'total_units' => 'decimal:8',
        ];
    }

    public function metal(): BelongsTo
    {
        return $this->belongsTo(Metal::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnallocatedPosition extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'customer_id',
        'metal_id',
        'units',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'units' => 'decimal:8',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function metal(): BelongsTo
    {
        return $this->belongsTo(Metal::class);
    }
}

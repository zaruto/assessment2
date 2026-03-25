<?php

namespace App\Models;

use Database\Factories\BarFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bar extends Model
{
    /** @use HasFactory<BarFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'deposit_id',
        'withdrawal_id',
        'serial_number',
        'weight_kg',
        'withdrawn_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:4',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }
}

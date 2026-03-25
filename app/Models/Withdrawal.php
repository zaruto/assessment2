<?php

namespace App\Models;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'sequence_number',
        'customer_id',
        'metal_id',
        'storage_type',
        'quantity_kg',
        'price_per_kg_snapshot',
        'value_snapshot',
        'withdrawn_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $withdrawal): void {
            $customer = Customer::query()->findOrFail($withdrawal->customer_id);
            $metal = Metal::query()->findOrFail($withdrawal->metal_id);

            $withdrawal->storage_type = $customer->account_type === CustomerAccountType::Institutional
                ? CustomerStorageType::Allocated
                : CustomerStorageType::Unallocated;
            $withdrawal->withdrawn_at ??= now();
            $withdrawal->price_per_kg_snapshot = $metal->price;
            $withdrawal->value_snapshot = number_format(
                (float) $withdrawal->quantity_kg * (float) $metal->price,
                2,
                '.',
                '',
            );

            if (blank($withdrawal->sequence_number)) {
                $withdrawal->sequence_number = ((int) self::query()->withTrashed()->max('sequence_number')) + 1;
            }

            $withdrawal->reference_number = sprintf('WDR-%04d', $withdrawal->sequence_number);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'storage_type' => CustomerStorageType::class,
            'quantity_kg' => 'decimal:4',
            'price_per_kg_snapshot' => 'decimal:2',
            'value_snapshot' => 'decimal:2',
            'withdrawn_at' => 'datetime',
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

    public function bars(): HasMany
    {
        return $this->hasMany(Bar::class);
    }
}

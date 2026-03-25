<?php

namespace App\Models;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
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
        'deposited_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $deposit): void {
            $customer = Customer::query()->findOrFail($deposit->customer_id);
            $metal = Metal::query()->findOrFail($deposit->metal_id);

            $deposit->storage_type = $customer->account_type === CustomerAccountType::Institutional
                ? CustomerStorageType::Allocated
                : CustomerStorageType::Unallocated;
            $deposit->deposited_at ??= now();
            $deposit->price_per_kg_snapshot = $metal->price;
            $deposit->value_snapshot = number_format(
                (float) $deposit->quantity_kg * (float) $metal->price,
                2,
                '.',
                '',
            );

            if (blank($deposit->sequence_number)) {
                $deposit->sequence_number = ((int) self::query()->withTrashed()->max('sequence_number')) + 1;
            }

            $deposit->reference_number = sprintf('DEP-%04d', $deposit->sequence_number);
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
            'deposited_at' => 'datetime',
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

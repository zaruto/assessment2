<?php

namespace App\Models;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'account_type',
        'storage_type',
        'portfolio_value',
        'joined_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (blank($customer->joined_at)) {
                $customer->joined_at = now();
            }
        });

        static::saving(function (self $customer): void {
            if ($customer->account_type === CustomerAccountType::Institutional) {
                $customer->storage_type = CustomerStorageType::Allocated;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => CustomerAccountType::class,
            'storage_type' => CustomerStorageType::class,
            'portfolio_value' => 'decimal:2',
            'joined_at' => 'datetime',
        ];
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function unallocatedPositions(): HasMany
    {
        return $this->hasMany(UnallocatedPosition::class);
    }
}

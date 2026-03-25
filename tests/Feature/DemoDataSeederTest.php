<?php

use App\Models\Bar;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use App\Models\User;
use App\Models\Withdrawal;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds consistent demo data and remains idempotent', function (): void {
    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(User::query()->where('email', 'admin@admin.com')->count())->toBe(1)
        ->and(Metal::query()->count())->toBe(3)
        ->and(Customer::query()->count())->toBe(10)
        ->and(Deposit::query()->count())->toBe(14)
        ->and(Withdrawal::query()->count())->toBe(7)
        ->and(Bar::query()->count())->toBe(11)
        ->and(Bar::query()->whereNotNull('withdrawal_id')->count())->toBe(3)
        ->and(MetalPool::query()->count())->toBe(3)
        ->and(UnallocatedPosition::query()->count())->toBe(9);
});

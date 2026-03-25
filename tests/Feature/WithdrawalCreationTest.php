<?php

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Filament\Resources\Withdrawals\WithdrawalResource;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use App\Services\DepositRecorder;
use App\Services\WithdrawalRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('consumes selected bars for institutional withdrawals and updates portfolio', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
        'storage_type' => CustomerStorageType::Allocated->value,
        'portfolio_value' => 0,
    ]);

    $metal = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 100,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-WDR-0001', 'weight_kg' => 1.2000],
            ['serial_number' => 'AU-WDR-0002', 'weight_kg' => 0.8000],
        ],
    ]);

    $bars = Bar::query()
        ->whereIn('serial_number', ['AU-WDR-0001', 'AU-WDR-0002'])
        ->orderBy('serial_number')
        ->get();

    $withdrawal = app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bar_ids' => $bars->pluck('id')->all(),
    ]);

    expect($withdrawal->storage_type)->toBe(CustomerStorageType::Allocated)
        ->and($withdrawal->quantity_kg)->toBe('2.0000')
        ->and($withdrawal->value_snapshot)->toBe('200.00')
        ->and($withdrawal->reference_number)->toBe('WDR-0001')
        ->and($customer->fresh()->portfolio_value)->toBe('0.00')
        ->and($withdrawal->bars)->toHaveCount(2)
        ->and($withdrawal->bars->every(fn (Bar $bar): bool => $bar->withdrawal_id === $withdrawal->id))->toBeTrue();
});

it('uses quantity for retail unallocated withdrawals and decreases portfolio', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
        'portfolio_value' => 0,
    ]);

    $metal = Metal::factory()->create([
        'name' => 'Silver',
        'code' => 'AG',
        'price' => 50,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 10,
    ]);

    $withdrawal = app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 2.5,
    ]);

    $pool = MetalPool::query()->where('metal_id', $metal->id)->first();
    $position = UnallocatedPosition::query()
        ->where('customer_id', $customer->id)
        ->where('metal_id', $metal->id)
        ->first();

    expect($withdrawal->storage_type)->toBe(CustomerStorageType::Unallocated)
        ->and($withdrawal->quantity_kg)->toBe('2.5000')
        ->and($withdrawal->value_snapshot)->toBe('125.00')
        ->and($customer->fresh()->portfolio_value)->toBe('375.00')
        ->and($pool)->not->toBeNull()
        ->and($position)->not->toBeNull()
        ->and($pool?->total_quantity_kg)->toBe('7.5000')
        ->and($pool?->total_units)->toBe('7.50000000')
        ->and($position?->units)->toBe('7.50000000');
});

it('requires bar selection for allocated withdrawals', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);
    $metal = Metal::factory()->create();

    expect(fn (): mixed => app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bar_ids' => [],
    ]))->toThrow(ValidationException::class);
});

it('rejects unavailable allocated bars for withdrawal', function (): void {
    $institutionalCustomer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);
    $otherCustomer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);
    $metal = Metal::factory()->create();

    app(DepositRecorder::class)->record([
        'customer_id' => $otherCustomer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-OTHER-1', 'weight_kg' => 1],
        ],
    ]);

    $foreignBarId = Bar::query()->where('serial_number', 'AU-OTHER-1')->value('id');

    expect(fn (): mixed => app(WithdrawalRecorder::class)->record([
        'customer_id' => $institutionalCustomer->id,
        'metal_id' => $metal->id,
        'bar_ids' => [$foreignBarId],
    ]))->toThrow(ValidationException::class);
});

it('prevents unallocated withdrawals beyond available balance', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);
    $metal = Metal::factory()->create();

    app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 1.5,
    ]);

    expect(fn (): mixed => app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 2.0,
    ]))->toThrow(ValidationException::class);
});

it('increments withdrawal reference numbers sequentially', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);
    $metal = Metal::factory()->create();

    app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 5,
    ]);

    $first = app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 1,
    ]);

    $second = app(WithdrawalRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 1,
    ]);

    expect($first->reference_number)->toBe('WDR-0001')
        ->and($second->reference_number)->toBe('WDR-0002');
});

it('keeps withdrawals resource create-only with no edit/create page routes', function (): void {
    $pages = WithdrawalResource::getPages();

    expect($pages)->toHaveKey('index')
        ->not->toHaveKey('create')
        ->not->toHaveKey('edit');
});

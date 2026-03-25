<?php

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\MetalPool;
use App\Models\UnallocatedPosition;
use App\Services\DepositRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates allocated bars for institutional deposits and updates portfolio', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
        'storage_type' => CustomerStorageType::Allocated->value,
        'portfolio_value' => 1000,
    ]);

    $metal = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 100,
    ]);

    $deposit = app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-2024-00010', 'weight_kg' => 1.2000],
            ['serial_number' => 'AU-2024-00011', 'weight_kg' => 1.3000],
        ],
    ]);

    expect($deposit->storage_type)->toBe(CustomerStorageType::Allocated)
        ->and($deposit->value_snapshot)->toBe('250.00')
        ->and($deposit->price_per_kg_snapshot)->toBe('100.00')
        ->and($deposit->quantity_kg)->toBe('2.5000')
        ->and($deposit->bars()->count())->toBe(2)
        ->and($deposit->bars()->first()->weight_kg)->toBe('1.2000')
        ->and($deposit->reference_number)->toBe('DEP-0001')
        ->and($customer->fresh()->portfolio_value)->toBe('1250.00');
});

it('creates unallocated retail deposit without bars', function (): void {
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

    $deposit = app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 10,
        'bars' => [
            ['serial_number' => 'SHOULD-BE-IGNORED', 'weight_kg' => 10],
        ],
    ]);

    $pool = MetalPool::query()->where('metal_id', $metal->id)->first();
    $position = UnallocatedPosition::query()
        ->where('customer_id', $customer->id)
        ->where('metal_id', $metal->id)
        ->first();

    expect($deposit->storage_type)->toBe(CustomerStorageType::Unallocated)
        ->and($deposit->bars()->count())->toBe(0)
        ->and($customer->fresh()->portfolio_value)->toBe('500.00')
        ->and($pool)->not->toBeNull()
        ->and($position)->not->toBeNull()
        ->and($pool?->total_quantity_kg)->toBe('10.0000')
        ->and($pool?->total_units)->toBe('10.00000000')
        ->and($position?->units)->toBe('10.00000000');
});

it('requires bar serials for allocated deposits', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);

    $metal = Metal::factory()->create();

    expect(fn (): mixed => app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [],
    ]))->toThrow(ValidationException::class);
});

it('rejects duplicate serials in one allocated payload', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);

    $metal = Metal::factory()->create();

    expect(fn (): mixed => app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-1', 'weight_kg' => 0.5],
            ['serial_number' => 'AU-1', 'weight_kg' => 0.5],
        ],
    ]))->toThrow(ValidationException::class);
});

it('rejects globally existing bar serials', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
    ]);

    $metal = Metal::factory()->create();

    app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-EXISTS-1', 'weight_kg' => 1],
        ],
    ]);

    expect(fn (): mixed => app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'bars' => [
            ['serial_number' => 'AU-EXISTS-1', 'weight_kg' => 1],
        ],
    ]))->toThrow(ValidationException::class);
});

it('requires positive deposit quantity', function (): void {
    $customer = Customer::factory()->create();
    $metal = Metal::factory()->create();

    expect(fn (): mixed => app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 0,
    ]))->toThrow(ValidationException::class);
});

it('increments deposit reference numbers sequentially', function (): void {
    $customer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Retail->value,
    ]);
    $metal = Metal::factory()->create();

    $first = app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 1,
    ]);

    $second = app(DepositRecorder::class)->record([
        'customer_id' => $customer->id,
        'metal_id' => $metal->id,
        'quantity_kg' => 2,
    ]);

    expect($first->reference_number)->toBe('DEP-0001')
        ->and($second->reference_number)->toBe('DEP-0002');
});

it('keeps deposits resource create-only with no edit/create page routes', function (): void {
    $pages = DepositResource::getPages();

    expect($pages)->toHaveKey('index')
        ->not->toHaveKey('create')
        ->not->toHaveKey('edit');
});

<?php

use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Metal;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds paginated legger entries for a customer in reverse chronological order', function (): void {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $metal = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
    ]);

    Deposit::factory()
        ->for($customer)
        ->for($metal)
        ->create([
            'reference_number' => 'DEP-0099',
            'quantity_kg' => 2.5000,
            'value_snapshot' => 250000,
            'deposited_at' => now()->subDay(),
        ]);

    Withdrawal::factory()
        ->for($customer)
        ->for($metal)
        ->create([
            'reference_number' => 'WDR-0100',
            'quantity_kg' => 1.0000,
            'value_snapshot' => 100000,
            'withdrawn_at' => now(),
        ]);

    Deposit::factory()
        ->for($otherCustomer)
        ->for($metal)
        ->create([
            'reference_number' => 'DEP-OTHER',
            'deposited_at' => now()->addDay(),
        ]);

    $ledger = CustomersTable::getCustomerLegger($customer, 1);
    $firstEntry = $ledger->items()[0];

    expect($ledger->total())->toBe(2)
        ->and($ledger->perPage())->toBe(1)
        ->and($ledger->count())->toBe(1)
        ->and($firstEntry->entry_type)->toBe('Withdrawal')
        ->and($firstEntry->reference_number)->toStartWith('WDR-')
        ->and($firstEntry->metal_code)->toBe('AU');
});

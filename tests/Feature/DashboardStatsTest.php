<?php

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Filament\Widgets\DashboardOverview;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Metal;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows key custody stats on the dashboard for authenticated users', function (): void {
    $this->actingAs(User::factory()->create());

    $metal = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 100,
    ]);

    $institutionalCustomer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Institutional->value,
        'storage_type' => CustomerStorageType::Allocated->value,
    ]);
    $retailCustomer = Customer::factory()->create([
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);

    $allocatedDeposit = Deposit::factory()->create([
        'customer_id' => $institutionalCustomer->id,
        'metal_id' => $metal->id,
        'storage_type' => CustomerStorageType::Allocated->value,
        'quantity_kg' => 2,
        'deposited_at' => now()->subDay(),
    ]);
    Bar::factory()->create([
        'deposit_id' => $allocatedDeposit->id,
        'serial_number' => 'AU-0001-00001',
        'weight_kg' => 2,
    ]);

    Deposit::factory()->create([
        'customer_id' => $retailCustomer->id,
        'metal_id' => $metal->id,
        'storage_type' => CustomerStorageType::Unallocated->value,
        'quantity_kg' => 3,
        'deposited_at' => now()->subDay(),
    ]);
    Withdrawal::factory()->create([
        'customer_id' => $retailCustomer->id,
        'metal_id' => $metal->id,
        'storage_type' => CustomerStorageType::Unallocated->value,
        'quantity_kg' => 1,
        'withdrawn_at' => now()->subHours(6),
    ]);

    $response = $this->get(route('filament.admin.pages.dashboard'));

    $response
        ->assertOk()
        ->assertSeeLivewire(DashboardOverview::class);

    Livewire::test(DashboardOverview::class)
        ->assertSee('Total Customers')
        ->assertSee('Assets in Custody (kg)')
        ->assertSee('Live Asset Value')
        ->assertSee('Allocated Bars in Custody')
        ->assertSee('Deposits (30 Days)')
        ->assertSee('Withdrawals (30 Days)')
        ->assertSee('2')
        ->assertSee('4.00')
        ->assertSee('$400.00')
        ->assertSee('1');
});

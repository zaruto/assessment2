<?php

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Bar;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\User;
use App\Services\DepositRecorder;
use App\Services\WithdrawalRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for inventory page', function (): void {
    $this->get('/inventory')
        ->assertRedirect('/login');
});

it('allows authenticated users to access inventory page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('filament.admin.pages.inventory'))
        ->assertOk()
        ->assertSee('Inventory');
});

it('shows only active allocated bars and correct allocated totals', function (): void {
    $user = User::factory()->create();

    $institutionalCustomer = Customer::factory()->create([
        'name' => 'Sovereign Wealth Trust',
        'account_type' => CustomerAccountType::Institutional->value,
        'storage_type' => CustomerStorageType::Allocated->value,
    ]);

    $gold = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 1000,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $institutionalCustomer->id,
        'metal_id' => $gold->id,
        'bars' => [
            ['serial_number' => 'BAR-WITHDRAWN', 'weight_kg' => 2.0],
            ['serial_number' => 'BAR-KEEP', 'weight_kg' => 3.0],
        ],
    ]);

    $withdrawnBar = Bar::query()->where('serial_number', 'BAR-WITHDRAWN')->firstOrFail();

    app(WithdrawalRecorder::class)->record([
        'customer_id' => $institutionalCustomer->id,
        'metal_id' => $gold->id,
        'bar_ids' => [$withdrawnBar->id],
    ]);

    $this->actingAs($user)
        ->get('/inventory')
        ->assertOk()
        ->assertSee('Allocated Storage')
        ->assertSee('BAR-KEEP')
        ->assertDontSee('BAR-WITHDRAWN')
        ->assertSee('3.00 kg')
        ->assertSee('MVR 3,000');
});

it('uses net unallocated holdings for shares and totals', function (): void {
    $user = User::factory()->create();

    $sarah = Customer::factory()->create([
        'name' => 'Sarah Mitchell',
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);

    $james = Customer::factory()->create([
        'name' => 'James Chen',
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);

    $zeroPositionCustomer = Customer::factory()->create([
        'name' => 'Zero Position',
        'account_type' => CustomerAccountType::Retail->value,
        'storage_type' => CustomerStorageType::Unallocated->value,
    ]);

    $gold = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 1000,
    ]);

    $silver = Metal::factory()->create([
        'name' => 'Silver',
        'code' => 'AG',
        'price' => 900,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $sarah->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 6,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $james->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 4,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $sarah->id,
        'metal_id' => $silver->id,
        'quantity_kg' => 10,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $zeroPositionCustomer->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 1,
    ]);

    app(WithdrawalRecorder::class)->record([
        'customer_id' => $james->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 1,
    ]);

    app(WithdrawalRecorder::class)->record([
        'customer_id' => $zeroPositionCustomer->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 1,
    ]);

    $this->actingAs($user)
        ->get('/inventory?tab=unallocated')
        ->assertOk()
        ->assertSee('Unallocated Storage')
        ->assertSee('19.00 kg')
        ->assertSee('MVR 18,000')
        ->assertSee('66.7%')
        ->assertSee('33.3%')
        ->assertSee('100.0%')
        ->assertSee('Sarah Mitchell')
        ->assertSee('James Chen')
        ->assertDontSee('Zero Position');
});

it('shows pagination controls when allocated rows exceed default page size', function (): void {
    $user = User::factory()->create();

    $institutionalCustomer = Customer::factory()->create([
        'name' => 'Large Inventory Customer',
        'account_type' => CustomerAccountType::Institutional->value,
        'storage_type' => CustomerStorageType::Allocated->value,
    ]);

    $gold = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 1000,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $institutionalCustomer->id,
        'metal_id' => $gold->id,
        'bars' => collect(range(1, 11))
            ->map(fn (int $index): array => [
                'serial_number' => 'PAGINATION-BAR-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'weight_kg' => 1.0,
            ])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.inventory'))
        ->assertOk()
        ->assertSee('Next');
});

it('shows only top three owners in unallocated ownership breakdown', function (): void {
    $user = User::factory()->create();

    $owners = collect([
        ['name' => 'Owner Alpha', 'quantity_kg' => 40],
        ['name' => 'Owner Beta', 'quantity_kg' => 30],
        ['name' => 'Owner Gamma', 'quantity_kg' => 20],
        ['name' => 'Owner Delta', 'quantity_kg' => 10],
    ])->map(function (array $owner): Customer {
        return Customer::factory()->create([
            'name' => $owner['name'],
            'account_type' => CustomerAccountType::Retail->value,
            'storage_type' => CustomerStorageType::Unallocated->value,
        ]);
    })->values();

    $gold = Metal::factory()->create([
        'name' => 'Gold',
        'code' => 'AU',
        'price' => 1000,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $owners[0]->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 40,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $owners[1]->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 30,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $owners[2]->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 20,
    ]);

    app(DepositRecorder::class)->record([
        'customer_id' => $owners[3]->id,
        'metal_id' => $gold->id,
        'quantity_kg' => 10,
    ]);

    $response = $this->actingAs($user)->get('/inventory?tab=unallocated');

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('Ownership Breakdown');

    preg_match('/<article[^>]*>.*?Gold Pool.*?Ownership Breakdown(.*?)<\/article>/si', $html, $matches);

    expect($matches)->not->toBeEmpty();
    expect($matches[1])->toContain('Owner Alpha');
    expect($matches[1])->toContain('Owner Beta');
    expect($matches[1])->toContain('Owner Gamma');
    expect($matches[1])->not->toContain('Owner Delta');
});

<?php

namespace App\Filament\Pages;

use App\Services\InventorySnapshotService;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class Inventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $slug = 'inventory';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = 'Inventory';

    protected ?string $subheading = 'View all metals in custody across both storage types';

    protected string $view = 'filament.pages.inventory';

    #[Url(as: 'tab')]
    public string $activeStorageTab = 'allocated';

    /**
     * @var array{
     *     totals: array{bars: int, weight_kg: float, value: float},
     *     rows: array<int, array{
     *         serial_number: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         owner_name: string,
     *         deposit_reference: string,
     *         weight_kg: float,
     *         value: float,
     *         date_added: CarbonInterface|null
     *     }>
     * }
     */
    public array $allocatedSnapshot = [
        'totals' => ['bars' => 0, 'weight_kg' => 0.0, 'value' => 0.0],
        'rows' => [],
    ];

    /**
     * @var array{
     *     totals: array{holdings_kg: float, value: float, active_positions: int},
     *     pools: array<int, array{
     *         metal_id: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         total_kg: float,
     *         total_value: float,
     *         owners: array<int, array{
     *             customer_id: string,
     *             customer_name: string,
     *             quantity_kg: float,
     *             pool_share_percentage: float,
     *             value: float
     *         }>
     *     }>,
     *     positions: array<int, array{
     *         customer_id: string,
     *         customer_name: string,
     *         metal_id: string,
     *         metal_name: string,
     *         metal_code: string,
     *         metal_color: string,
     *         quantity_kg: float,
     *         pool_share_percentage: float,
     *         value: float
     *     }>
     * }
     */
    public array $unallocatedSnapshot = [
        'totals' => ['holdings_kg' => 0.0, 'value' => 0.0, 'active_positions' => 0],
        'pools' => [],
        'positions' => [],
    ];

    public function mount(InventorySnapshotService $inventorySnapshotService): void
    {
        if (! in_array($this->activeStorageTab, ['allocated', 'unallocated'], true)) {
            $this->activeStorageTab = 'allocated';
        }

        $this->allocatedSnapshot = $inventorySnapshotService->allocatedSnapshot();
        $this->unallocatedSnapshot = $inventorySnapshotService->unallocatedSnapshot();
    }

    public function setActiveStorageTab(string $tab): void
    {
        if (! in_array($tab, ['allocated', 'unallocated'], true)) {
            return;
        }

        $this->activeStorageTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage, array $filters): LengthAwarePaginator {
                $allRows = $this->tableRows($filters);
                $records = $allRows
                    ->forPage($page, $recordsPerPage)
                    ->values();

                return new LengthAwarePaginator(
                    $records,
                    total: $allRows->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns($this->tableColumns())
            ->filters($this->tableFilters())
            ->searchable()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading(
                $this->activeStorageTab === 'allocated'
                    ? 'No active allocated bars found.'
                    : 'No active unallocated positions found.'
            )
            ->recordActions([])
            ->toolbarActions([]);
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function tableColumns(): array
    {
        if ($this->activeStorageTab === 'allocated') {
            return [
                TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->state(fn (array $record): string => (string) $record['serial_number']),

                TextColumn::make('metal_name')
                    ->label('Metal')
                    ->state(fn (array $record): string => (string) $record['metal_name']),

                TextColumn::make('owner_name')
                    ->label('Owner')
                    ->state(fn (array $record): string => (string) $record['owner_name']),

                TextColumn::make('deposit_reference')
                    ->label('Deposit #')
                    ->searchable()
                    ->state(fn (array $record): string => (string) $record['deposit_reference']),

                TextColumn::make('weight_kg')
                    ->label('Weight')
                    ->alignRight()
                    ->state(fn (array $record): string => number_format((float) $record['weight_kg'], 2).' kg'),

                TextColumn::make('value')
                    ->label('Value')
                    ->badge()
                    ->alignRight()
                    ->state(fn (array $record): string => number_format((float) $record['value'], 0)),

                TextColumn::make('date_added')
                    ->label('Date Added')
                    ->state(
                        fn (array $record): string => $record['date_added'] instanceof CarbonInterface
                            ? $record['date_added']->format('M d, Y')
                            : '—'
                    ),
            ];
        }

        return [
            TextColumn::make('customer_name')
                ->label('Customer')
                ->state(fn (array $record): string => (string) $record['customer_name']),

            TextColumn::make('metal_name')
                ->label('Metal')
                ->state(fn (array $record): string => (string) $record['metal_name']),

            TextColumn::make('quantity_kg')
                ->label('Quantity')
                ->alignRight()
                ->state(fn (array $record): string => number_format((float) $record['quantity_kg'], 2).' kg'),

            TextColumn::make('pool_share_percentage')
                ->label('Pool Share')
                ->badge()
                ->alignRight()
                ->state(fn (array $record): string => number_format((float) $record['pool_share_percentage'], 1).'%'),

            TextColumn::make('value')
                ->label('Value')
                ->alignRight()
                ->badge()
                ->sortable()
                ->state(fn (array $record): string => number_format((float) $record['value'], 0)),
        ];
    }

    /**
     * @return array<int, SelectFilter>
     */
    protected function tableFilters(): array
    {
        return [
            SelectFilter::make('allocated_metal')
                ->label('Metal')
                ->visible(fn (): bool => $this->activeStorageTab === 'allocated')
                ->options($this->allocatedMetalFilterOptions()),
            SelectFilter::make('allocated_owner')
                ->label('Owner')
                ->visible(fn (): bool => $this->activeStorageTab === 'allocated')
                ->options($this->allocatedOwnerFilterOptions()),
            SelectFilter::make('unallocated_metal')
                ->label('Metal')
                ->visible(fn (): bool => $this->activeStorageTab === 'unallocated')
                ->options($this->unallocatedMetalFilterOptions()),
            SelectFilter::make('unallocated_customer')
                ->label('Customer')
                ->visible(fn (): bool => $this->activeStorageTab === 'unallocated')
                ->options($this->unallocatedCustomerFilterOptions()),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function tableRows(array $filters): Collection
    {
        if ($this->activeStorageTab === 'allocated') {
            /** @var Collection<int, array<string, mixed>> $rows */
            $rows = collect($this->allocatedSnapshot['rows'])
                ->map(function (array $row): array {
                    $row['id'] = 'allocated-'.$row['serial_number'];

                    return $row;
                });

            $selectedMetal = data_get($filters, 'allocated_metal.value');
            $selectedOwner = data_get($filters, 'allocated_owner.value');

            if (filled($selectedMetal)) {
                $rows = $rows->where('metal_code', $selectedMetal);
            }

            if (filled($selectedOwner)) {
                $rows = $rows->where('owner_name', $selectedOwner);
            }

            return $rows;
        }

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = collect($this->unallocatedSnapshot['positions'])
            ->map(function (array $row): array {
                $row['id'] = 'unallocated-'.$row['customer_id'].'-'.$row['metal_id'];

                return $row;
            });

        $selectedMetal = data_get($filters, 'unallocated_metal.value');
        $selectedCustomer = data_get($filters, 'unallocated_customer.value');

        if (filled($selectedMetal)) {
            $rows = $rows->where('metal_code', $selectedMetal);
        }

        if (filled($selectedCustomer)) {
            $rows = $rows->where('customer_name', $selectedCustomer);
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    protected function allocatedMetalFilterOptions(): array
    {
        return collect($this->allocatedSnapshot['rows'])
            ->mapWithKeys(fn (array $row): array => [(string) $row['metal_code'] => (string) $row['metal_name']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function allocatedOwnerFilterOptions(): array
    {
        return collect($this->allocatedSnapshot['rows'])
            ->pluck('owner_name')
            ->unique()
            ->sort()
            ->mapWithKeys(fn (mixed $owner): array => [(string) $owner => (string) $owner])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function unallocatedMetalFilterOptions(): array
    {
        return collect($this->unallocatedSnapshot['positions'])
            ->mapWithKeys(fn (array $row): array => [(string) $row['metal_code'] => (string) $row['metal_name']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function unallocatedCustomerFilterOptions(): array
    {
        return collect($this->unallocatedSnapshot['positions'])
            ->pluck('customer_name')
            ->unique()
            ->sort()
            ->mapWithKeys(fn (mixed $customer): array => [(string) $customer => (string) $customer])
            ->all();
    }
}

<x-filament-panels::page>
    @php
        $isAllocatedTab = $activeStorageTab === 'allocated';
        $allocated = $allocatedSnapshot;
        $unallocated = $unallocatedSnapshot;

        $dotClass = static function (string $color): string {
            return match ($color) {
                'amber' => 'bg-amber-500',
                'slate' => 'bg-slate-400',
                'gray' => 'bg-gray-500',
                default => 'bg-zinc-400',
            };
        };
    @endphp

    <div class="space-y-8">
        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
            <button
                type="button"
                wire:click="setActiveStorageTab('allocated')"
                @class([
                    'rounded-md px-4 py-2 text-sm font-medium transition',
                    'bg-white text-gray-950 shadow-sm dark:bg-gray-700 dark:text-white' => $isAllocatedTab,
                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => ! $isAllocatedTab,
                ])
            >
                Allocated Bars
            </button>

            <button
                type="button"
                wire:click="setActiveStorageTab('unallocated')"
                @class([
                    'rounded-md px-4 py-2 text-sm font-medium transition',
                    'bg-white text-gray-950 shadow-sm dark:bg-gray-700 dark:text-white' => ! $isAllocatedTab,
                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $isAllocatedTab,
                ])
            >
                Unallocated Pools
            </button>
        </div>

        @if ($isAllocatedTab)
            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Allocated Storage</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Individual bars tracked by serial number for institutional clients.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Bars</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $allocated['totals']['bars'] }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Weight</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ number_format((float) $allocated['totals']['weight_kg'], 2) }} kg
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Value</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            MVR {{ number_format((float) $allocated['totals']['value'], 0) }}
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    {{ $this->table }}
                </div>
            </section>
        @else
            <section class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Unallocated Storage</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Pooled holdings where clients own a percentage share.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Pool Holdings</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ number_format((float) $unallocated['totals']['holdings_kg'], 2) }} kg
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Pool Value</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            MVR {{ number_format((float) $unallocated['totals']['value'], 0) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active Positions</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $unallocated['totals']['active_positions'] }}
                        </p>
                    </div>
                </div>

                @if (count($unallocated['pools']) != 0)
                    <div class="grid gap-4 lg:grid-cols-3">
                        @foreach ($unallocated['pools'] as $pool)
                            <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full {{ $dotClass($pool['metal_color']) }}"></span>
                                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $pool['metal_name'] }} Pool</h3>
                                </div>

                                <p class="mt-6 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format((float) $pool['total_kg'], 2) }} kg</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">MVR {{ number_format((float) $pool['total_value'], 0) }}</p>

                                <div class="mt-6 space-y-4">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ownership Breakdown</p>

                                    @foreach (collect($pool['owners'])->sortByDesc('pool_share_percentage')->take(3) as $owner)
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-200">
                                                <span>{{ $owner['customer_name'] }}</span>
                                                <span>{{ number_format((float) $owner['pool_share_percentage'], 1) }}%</span>
                                            </div>

                                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                                <div
                                                    class="h-full {{ $dotClass($pool['metal_color']) }}"
                                                    style="width: {{ number_format((float) $owner['pool_share_percentage'], 1, '.', '') }}%"
                                                ></div>
                                            </div>

                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ number_format((float) $owner['quantity_kg'], 2) }} kg</span>
                                                <span>MVR {{ number_format((float) $owner['value'], 0) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    {{ $this->table }}
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>

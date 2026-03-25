<div class="space-y-4">
    <div class="text-sm text-gray-600 dark:text-gray-300">
        {{ $customer->name }} ledger history ({{ $ledger->total() }} entries)
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Metal</th>
                    <th class="px-4 py-3 text-right">Quantity</th>
                    <th class="px-4 py-3 text-right">Value</th>
                    <th class="px-4 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse ($ledger as $entry)
                    <tr>
                        <td class="px-4 py-3">{{ $entry->entry_type }}</td>
                        <td class="px-4 py-3">{{ $entry->reference_number }}</td>
                        <td class="px-4 py-3">{{ $entry->metal_name }} ({{ $entry->metal_code }})</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $entry->quantity_kg, 4) }} kg</td>
                        <td class="px-4 py-3 text-right">MVR {{ number_format((float) $entry->value_snapshot, 2) }}</td>
                        <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($entry->recorded_at)->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No ledger entries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $ledger->onEachSide(1)->links() }}
    </div>
</div>

<x-filament::page>
    <div class="overflow-x-auto rounded-lg border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Key</th>
                    <th class="px-3 py-2 text-left">Title</th>
                    <th class="px-3 py-2 text-left">Category</th>
                    <th class="px-3 py-2 text-left">Service</th>
                    <th class="px-3 py-2 text-left">Enabled</th>
                    <th class="px-3 py-2 text-left">Price</th>
                    <th class="px-3 py-2 text-left">SLA</th>
                    <th class="px-3 py-2 text-left">Checkout</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($scenarios as $scenario)
                    <tr>
                        <td class="px-3 py-2">{{ $scenario['key'] }}</td>
                        <td class="px-3 py-2">{{ $scenario['public_title'] ?? $scenario['title'] }}</td>
                        <td class="px-3 py-2">{{ $scenario['category_slug'] }}</td>
                        <td class="px-3 py-2">{{ $scenario['service_type'] }}</td>
                        <td class="px-3 py-2">{{ $scenario['enabled'] ? 'yes' : 'no' }}</td>
                        <td class="px-3 py-2">{{ $scenario['base_price'] }} {{ $scenario['currency'] }}</td>
                        <td class="px-3 py-2">{{ $scenario['sla_minutes'] }}m</td>
                        <td class="px-3 py-2">
                            <a class="text-primary-600" href="{{ url('/checkout/'.$scenario['key']) }}" target="_blank">open</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>


@props(['data', 'filters', 'sorting'])

<x-prints.layout title="Vehicles List">
    <x-slot name="filters">
        <x-prints.filters :filters="$filters ?? []" :sorting="$sorting ?? []" />
    </x-slot>

    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $vehicle)
                <tr>
                    <td>{{ $vehicle->vehicle ?? ($vehicle['vehicle'] ?? '') }}</td>
                    <td>{{ $vehicle->disabled ?? ($vehicle['disabled'] ?? false) ? 'Disabled' : 'Enabled' }}</td>
                    <td>
                        @if (isset($vehicle->created_at))
                            {{ \Carbon\Carbon::parse($vehicle->created_at)->format('M d, Y h:i A') }}
                        @elseif(isset($vehicle['created_at']))
                            {{ \Carbon\Carbon::parse($vehicle['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No vehicles found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <p>Total Records: {{ count($data) }}</p>
    </x-slot>
</x-prints.layout>

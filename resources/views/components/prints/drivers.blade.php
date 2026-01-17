@props(['data', 'filters', 'sorting'])

<x-prints.layout title="Drivers List">
    <x-slot name="filters">
        <x-prints.filters :filters="$filters ?? []" :sorting="$sorting ?? []" />
    </x-slot>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $driver)
                <tr>
                    <td>{{ trim(implode(' ', array_filter([$driver->first_name ?? ($driver['first_name'] ?? ''), $driver->middle_name ?? ($driver['middle_name'] ?? ''), $driver->last_name ?? ($driver['last_name'] ?? '')]))) }}</td>
                    <td>{{ $driver->disabled ?? ($driver['disabled'] ?? false) ? 'Disabled' : 'Enabled' }}</td>
                    <td>
                        @if (isset($driver->created_at))
                            {{ \Carbon\Carbon::parse($driver->created_at)->format('M d, Y h:i A') }}
                        @elseif(isset($driver['created_at']))
                            {{ \Carbon\Carbon::parse($driver['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No drivers found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <p>Total Records: {{ count($data) }}</p>
    </x-slot>
</x-prints.layout>

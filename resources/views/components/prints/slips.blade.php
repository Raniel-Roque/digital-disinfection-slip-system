@props(['data', 'filters', 'sorting'])

<x-prints.layout title="Disinfection Slips List" landscape="true" fontSize="10px" tableFontSize="9px">
    <x-slot name="filters">
        <x-prints.filters :filters="$filters ?? []" :sorting="$sorting ?? []" />
    </x-slot>

    <table>
        <thead>
            <tr>
                <th>Slip ID</th>
                <th>Vehicle</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Driver</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Hatchery Guard</th>
                <th>Received Guard</th>
                <th>Created Date</th>
                <th>Completed Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $slip)
                <tr>
                    <td>{{ $slip->slip_id ?? ($slip['slip_id'] ?? '') }}</td>
                    <td>{{ $slip->vehicle ?? ($slip['vehicle'] ?? '') }}</td>
                    <td>{{ $slip->origin ?? ($slip['origin'] ?? '') }}</td>
                    <td>{{ $slip->destination ?? ($slip['destination'] ?? '') }}</td>
                    <td>{{ $slip->driver ?? ($slip['driver'] ?? '') }}</td>
                    <td>{{ $slip->reason ?? ($slip['reason'] ?? 'N/A') }}</td>
                    <td>
                        @php
                            $statuses = ['Pending', 'Disinfecting', 'In-Transit', 'Completed', 'Incomplete'];
                            $statusIndex = $slip->status ?? ($slip['status'] ?? 0);
                            echo $statuses[$statusIndex] ?? 'Unknown';
                        @endphp
                    </td>
                    <td>{{ $slip->hatchery_guard ?? ($slip['hatchery_guard'] ?? '') }}</td>
                    <td>{{ $slip->received_guard ?? ($slip['received_guard'] ?? '') }}</td>
                    <td>
                        @if (isset($slip->created_at))
                            {{ \Carbon\Carbon::parse($slip->created_at)->format('M d, Y h:i A') }}
                        @elseif(isset($slip['created_at']))
                            {{ \Carbon\Carbon::parse($slip['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if (isset($slip->completed_at) && $slip->completed_at)
                            {{ \Carbon\Carbon::parse($slip->completed_at)->format('M d, Y h:i A') }}
                        @elseif(isset($slip['completed_at']) && $slip['completed_at'])
                            {{ \Carbon\Carbon::parse($slip['completed_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">No disinfection slips found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <p>Total Records: {{ count($data) }}</p>
    </x-slot>
</x-prints.layout>

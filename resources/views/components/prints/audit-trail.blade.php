@props(['data', 'filters', 'sorting'])

@php
    $modelTypes = [
        'App\\Models\\DisinfectionSlip' => 'Disinfection Slip',
        'App\\Models\\User' => 'User',
        'App\\Models\\Driver' => 'Driver',
        'App\\Models\\Location' => 'Location',
        'App\\Models\\Vehicle' => 'Vehicle',
        'App\\Models\\Setting' => 'Setting',
        'App\\Models\\Issue' => 'Issue',
    ];
@endphp

<x-prints.layout title="Audit Trail List">
    <x-slot name="filters">
        @if (!empty($filters) || !empty($sorting))
            <div style="margin-top: 15px; text-align: left; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px;">
                @if (!empty($filters['search']))
                    <p><strong>Search:</strong> {{ $filters['search'] }}</p>
                @endif

                @if (!empty($filters['action']))
                    <p><strong>Actions:</strong> {{ implode(', ', array_map('ucfirst', $filters['action'])) }}</p>
                @endif

                @if (!empty($filters['model_type']))
                    <p><strong>Model Types:</strong> {{ implode(', ', array_map(function($type) use ($modelTypes) {
                        return $modelTypes[$type] ?? $type;
                    }, $filters['model_type'])) }}</p>
                @endif

                @if (!empty($filters['user_type']))
                    @php
                        $userTypes = [
                            0 => 'Guard',
                            1 => 'Admin',
                            'super_guard' => 'Super Guard'
                        ];
                    @endphp
                    <p><strong>User Types:</strong> {{ implode(', ', array_map(function($type) use ($userTypes) {
                        return $userTypes[$type] ?? 'Unknown';
                    }, $filters['user_type'])) }}</p>
                @endif

                @if (!empty($filters['created_from']))
                    <p><strong>Created From:</strong> {{ \Carbon\Carbon::parse($filters['created_from'])->format('M d, Y') }}</p>
                @endif

                @if (!empty($filters['created_to']))
                    <p><strong>Created To:</strong> {{ \Carbon\Carbon::parse($filters['created_to'])->format('M d, Y') }}</p>
                @endif

                @if (!empty($sorting))
                    <p><strong>Sorted By:</strong>
                        @foreach ($sorting as $column => $direction)
                            {{ ucfirst(str_replace('_', ' ', $column)) }} ({{ strtoupper($direction) }})@if (!$loop->last), @endif
                        @endforeach
                    </p>
                @endif
            </div>
        @endif
    </x-slot>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; white-space: nowrap;">ID</th>
                <th style="width: 11%; white-space: nowrap;">Date & Time</th>
                <th style="width: 11%;">User</th>
                <th style="width: 8%;">User Type</th>
                <th style="width: 8%;">Action</th>
                <th style="width: 11%;">Model Type</th>
                <th style="width: 33%; word-wrap: break-word; word-break: break-word; white-space: normal;">Description</th>
                <th style="width: 13%;">IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $log)
                <tr>
                    <td>{{ $log['id'] ?? 'N/A' }}</td>
                    <td>
                        @if (isset($log['created_at']))
                            {{ \Carbon\Carbon::parse($log['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $log['user_name'] ?? 'N/A' }}</td>
                    <td>{{ $log['user_type'] ?? 'N/A' }}</td>
                    <td>{{ ucfirst($log['action'] ?? 'N/A') }}</td>
                    <td>{{ $modelTypes[$log['model_type']] ?? ($log['model_type'] ?? 'N/A') }}</td>
                    <td>{{ $log['description'] ?? 'N/A' }}</td>
                    <td>{{ $log['ip_address'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No audit trail logs found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <p>Total Records: {{ count($data) }}</p>
    </x-slot>
</x-prints.layout>

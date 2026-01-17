@props(['data', 'filters', 'sorting'])

<x-prints.layout title="Admins List">
    <x-slot name="filters">
        <x-prints.filters :filters="$filters ?? []" :sorting="$sorting ?? []" />
    </x-slot>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $user)
                <tr>
                    <td>{{ trim(implode(' ', array_filter([$user->first_name ?? ($user['first_name'] ?? ''), $user->middle_name ?? ($user['middle_name'] ?? ''), $user->last_name ?? ($user['last_name'] ?? '')]))) }}</td>
                    <td>{{ $user->username ?? ($user['username'] ?? '') }}</td>
                    <td>{{ $user->disabled ?? ($user['disabled'] ?? false) ? 'Disabled' : 'Enabled' }}</td>
                    <td>
                        @if (isset($user->created_at))
                            {{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y h:i A') }}
                        @elseif(isset($user['created_at']))
                            {{ \Carbon\Carbon::parse($user['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No admins found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot name="footer">
        <p>Total Records: {{ count($data) }}</p>
    </x-slot>
</x-prints.layout>

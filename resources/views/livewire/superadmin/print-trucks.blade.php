<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disinfection Slips Report - {{ date('Y-m-d') }}</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                margin: 1cm;
                landscape: true;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .print-actions {
            margin-bottom: 20px;
            text-align: right;
        }
        .btn {
            padding: 10px 20px;
            margin-left: 10px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-print {
            background-color: #4CAF50;
            color: white;
        }
        .btn-close {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="no-print print-actions">
        <button class="btn btn-print hover:cursor-pointer" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-close hover:cursor-pointer" onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h1>Disinfection Slips Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        
        @if (!empty($filters) || !empty($sorting))
            <div style="margin-top: 15px; text-align: left; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px;">
                @if (!empty($filters['search']))
                    <p><strong>Search:</strong> {{ $filters['search'] }}</p>
                @endif
                
                @if (isset($filters['status']) && $filters['status'] !== null)
                    @php
                        $statuses = ['Ongoing', 'Disinfecting', 'Completed'];
                        $status = $statuses[$filters['status']] ?? 'Unknown';
                    @endphp
                    <p><strong>Status:</strong> {{ $status }}</p>
                @endif
                
                @if (!empty($filters['origin']) && is_array($filters['origin']))
                    @php
                        $originNames = \App\Models\Location::whereIn('id', $filters['origin'])->pluck('location_name')->toArray();
                    @endphp
                    <p><strong>Origin:</strong> {{ implode(', ', $originNames) }}</p>
                @endif
                
                @if (!empty($filters['destination']) && is_array($filters['destination']))
                    @php
                        $destinationNames = \App\Models\Location::whereIn('id', $filters['destination'])->pluck('location_name')->toArray();
                    @endphp
                    <p><strong>Destination:</strong> {{ implode(', ', $destinationNames) }}</p>
                @endif
                
                @if (!empty($filters['driver']) && is_array($filters['driver']))
                    @php
                        $driverNames = \App\Models\Driver::whereIn('id', $filters['driver'])->get()->map(function($d) {
                            return trim(implode(' ', array_filter([$d->first_name, $d->middle_name, $d->last_name])));
                        })->toArray();
                    @endphp
                    <p><strong>Driver:</strong> {{ implode(', ', $driverNames) }}</p>
                @endif
                
                @if (!empty($filters['plate_number']) && is_array($filters['plate_number']))
                    @php
                        $plateNumbers = \App\Models\Truck::whereIn('id', $filters['plate_number'])->pluck('plate_number')->toArray();
                    @endphp
                    <p><strong>Plate Number:</strong> {{ implode(', ', $plateNumbers) }}</p>
                @endif
                
                @if (!empty($filters['hatchery_guard']) && is_array($filters['hatchery_guard']))
                    @php
                        $guardNames = \App\Models\User::whereIn('id', $filters['hatchery_guard'])->get()->map(function($g) {
                            return trim(implode(' ', array_filter([$g->first_name, $g->middle_name, $g->last_name])));
                        })->toArray();
                    @endphp
                    <p><strong>Hatchery Guard:</strong> {{ implode(', ', $guardNames) }}</p>
                @endif
                
                @if (!empty($filters['received_guard']) && is_array($filters['received_guard']))
                    @php
                        $guardNames = \App\Models\User::whereIn('id', $filters['received_guard'])->get()->map(function($g) {
                            return trim(implode(' ', array_filter([$g->first_name, $g->middle_name, $g->last_name])));
                        })->toArray();
                    @endphp
                    <p><strong>Received Guard:</strong> {{ implode(', ', $guardNames) }}</p>
                @endif
                
                @if (!empty($filters['created_from']))
                    <p><strong>Created From:</strong> {{ \Carbon\Carbon::parse($filters['created_from'])->format('M d, Y') }}</p>
                @endif
                
                @if (!empty($filters['created_to']))
                    <p><strong>Created To:</strong> {{ \Carbon\Carbon::parse($filters['created_to'])->format('M d, Y') }}</p>
                @endif
                
                @if (!empty($sorting))
                    <p><strong>Sorted By:</strong> 
                        @if (isset($sorting['sort_by']))
                            {{ ucfirst(str_replace('_', ' ', $sorting['sort_by'])) }} ({{ strtoupper($sorting['sort_direction'] ?? 'asc') }})
                        @else
                            @foreach ($sorting as $column => $direction)
                                {{ ucfirst(str_replace('_', ' ', $column)) }} ({{ strtoupper($direction) }})@if (!$loop->last), @endif
                            @endforeach
                        @endif
                    </p>
                @endif
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Slip ID</th>
                <th>Plate Number</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Driver</th>
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
                    <td>{{ $slip->plate_number ?? ($slip['plate_number'] ?? '') }}</td>
                    <td>{{ $slip->origin ?? ($slip['origin'] ?? '') }}</td>
                    <td>{{ $slip->destination ?? ($slip['destination'] ?? '') }}</td>
                    <td>{{ $slip->driver ?? ($slip['driver'] ?? '') }}</td>
                    <td>
                        @php
                            $statuses = ['Ongoing', 'Disinfecting', 'Completed'];
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
                    <td colspan="10" style="text-align: center;">No disinfection slips found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ count($data) }}</p>
        <p>Digital Disinfection Slip System</p>
    </div>
</body>
</html>


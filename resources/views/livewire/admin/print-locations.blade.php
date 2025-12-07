<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations Report - {{ date('Y-m-d') }}</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                margin: 1cm;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
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
        <button class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h1>Locations Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>

        @if (!empty($filters) || !empty($sorting))
            <div
                style="margin-top: 15px; text-align: left; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px;">
                @if (!empty($filters['search']))
                    <p><strong>Search:</strong> {{ $filters['search'] }}</p>
                @endif

                @if (isset($filters['status']) && $filters['status'] !== null)
                    <p><strong>Status:</strong> {{ $filters['status'] == 0 ? 'Enabled' : 'Disabled' }}</p>
                @endif

                @if (!empty($filters['created_from']))
                    <p><strong>Created From:</strong>
                        {{ \Carbon\Carbon::parse($filters['created_from'])->format('M d, Y') }}</p>
                @endif

                @if (!empty($filters['created_to']))
                    <p><strong>Created To:</strong>
                        {{ \Carbon\Carbon::parse($filters['created_to'])->format('M d, Y') }}</p>
                @endif

                @if (!empty($sorting))
                    <p><strong>Sorted By:</strong>
                        @foreach ($sorting as $column => $direction)
                            {{ ucfirst(str_replace('_', ' ', $column)) }} ({{ strtoupper($direction) }})@if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </p>
                @endif
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Location Name</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $location)
                <tr>
                    <td>{{ $location->location_name ?? ($location['location_name'] ?? '') }}</td>
                    <td>{{ $location->disabled ?? ($location['disabled'] ?? false) ? 'Disabled' : 'Enabled' }}</td>
                    <td>
                        @if (isset($location->created_at))
                            {{ \Carbon\Carbon::parse($location->created_at)->format('M d, Y h:i A') }}
                        @elseif(isset($location['created_at']))
                            {{ \Carbon\Carbon::parse($location['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No locations found</td>
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

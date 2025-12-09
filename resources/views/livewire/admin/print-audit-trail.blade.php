<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail Report - {{ date('Y-m-d') }}</title>
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
        <button class="btn btn-print hover:cursor-pointer" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-close hover:cursor-pointer" onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h1>Audit Trail Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>

        @if (!empty($filters) || !empty($sorting))
            <div
                style="margin-top: 15px; text-align: left; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px;">
                @if (!empty($filters['search']))
                    <p><strong>Search:</strong> {{ $filters['search'] }}</p>
                @endif

                @if (!empty($filters['action']))
                    <p><strong>Actions:</strong> {{ implode(', ', array_map('ucfirst', $filters['action'])) }}</p>
                @endif

                @if (!empty($filters['model_type']))
                    <p><strong>Model Types:</strong> {{ implode(', ', $filters['model_type']) }}</p>
                @endif

                @if (!empty($filters['user_type']))
                    <p><strong>User Type:</strong> {{ $filters['user_type'] }}</p>
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
                <th>Date & Time</th>
                <th>User</th>
                <th>User Type</th>
                <th>Action</th>
                <th>Model Type</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $log)
                <tr>
                    <td>
                        @if (isset($log['created_at']))
                            {{ \Carbon\Carbon::parse($log['created_at'])->format('M d, Y h:i A') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $log['user_name'] ?? 'N/A' }}</td>
                    <td>
                        @php
                            $userTypes = [
                                0 => 'User',
                                1 => 'Admin',
                                2 => 'Superadmin'
                            ];
                        @endphp
                        {{ $userTypes[$log['user_type']] ?? 'N/A' }}
                    </td>
                    <td>{{ ucfirst($log['action'] ?? 'N/A') }}</td>
                    <td>
                        @php
                            $modelTypes = [
                                'App\\Models\\DisinfectionSlip' => 'Disinfection Slip',
                                'App\\Models\\User' => 'User',
                                'App\\Models\\Driver' => 'Driver',
                                'App\\Models\\Truck' => 'Truck',
                                'App\\Models\\Location' => 'Location',
                            ];
                        @endphp
                        {{ $modelTypes[$log['model_type']] ?? ($log['model_type'] ?? 'N/A') }}
                    </td>
                    <td>{{ $log['description'] ?? 'N/A' }}</td>
                    <td>{{ $log['ip_address'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No audit trail logs found</td>
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


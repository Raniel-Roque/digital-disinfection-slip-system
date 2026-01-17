@props([
    'title' => 'Print Document',
    'landscape' => false,
    'fontSize' => '12px',
    'tableFontSize' => null,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ date('Y-m-d') }}</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                margin: 1cm;
                @if($landscape)
                size: A4 landscape;
                @endif
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: {{ $fontSize }};
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
        .header-logo {
            max-height: 60px;
            width: auto;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
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
        @if($tableFontSize)
        table th, table td {
            font-size: {{ $tableFontSize }};
            padding: 6px;
        }
        @endif
    </style>
</head>
<body>
    <div class="no-print print-actions">
        <button class="btn btn-print hover:cursor-pointer" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-close hover:cursor-pointer" onclick="window.close()">Close</button>
    </div>

    @php
        $defaultLogo = \App\Models\Setting::where('setting_name', 'default_location_logo')->value('value') ?? 'images/logo/BGC.png';
    @endphp
    <div class="header">
        <img src="{{ asset('storage/' . $defaultLogo) }}" alt="Farm Logo" class="header-logo">
        <h1>{{ $title }}</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        {{ $filters ?? '' }}
    </div>

    {{ $slot }}

    <div class="footer">
        {{ $footer ?? '' }}
        <p>Digital Disinfection Slip System</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disinfection Slip - {{ $slip->slip_id }}</title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                margin: 1cm;
                size: A4;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
            background-color: #fff;
        }

        .slip-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border: 1px solid #ddd;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            position: relative;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }


        .form-field {
            margin-bottom: 18px;
            display: flex;
            align-items: baseline;
        }

        .form-label {
            font-weight: bold;
            min-width: 200px;
            margin-right: 15px;
            font-size: 14px;
        }

        .form-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 22px;
            padding-bottom: 3px;
            font-size: 14px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            flex: 1;
            margin: 0 10px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            min-height: 50px;
            margin-bottom: 20px;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .signature-name {
            font-weight: bold;
            font-size: 12px;
            padding-bottom: 2px;
            display: inline-block;
        }

        .signature-subtext {
            position: absolute;
            bottom: -18px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: normal;
            text-align: center;
            width: 100%;
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

    <div class="slip-container">
        <div class="header">
            <h1>{{ strtoupper($slip->location->location_name ?? 'BROOKSIDE FARM CORPORATION') }}</h1>
            <h2>DISINFECTION SLIP</h2>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 18px;">
            <div class="form-field" style="margin-bottom: 0; flex: 0 0 auto;">
                <div class="form-label" style="margin-right: 5px; min-width: auto;">DATE: </div>
                <div style="border-bottom: 1px solid #000; display: inline-block; padding-bottom: 0;">
                    {{ $slip->created_at->format('m/d/y') }}</div>
            </div>
            <div class="form-field" style="margin-bottom: 0; flex: 0 0 auto;">
                <div class="form-label" style="margin-right: 5px; min-width: auto;">SLIP No: </div>
                <div style="border-bottom: 1px solid #000; display: inline-block; padding-bottom: 0;">
                    {{ $slip->slip_id }}</div>
            </div>
        </div>

        <div class="form-field">
            <div class="form-label">Plate no:</div>
            <div class="form-value">
                @if ($slip->truck)
                    {{ $slip->truck->plate_number }}
                    @if ($slip->truck->trashed())
                        <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                    @endif
                @else
                    <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                @endif
            </div>
        </div>

        <div class="form-field">
            <div class="form-label">Destination:</div>
            <div class="form-value">
                @if ($slip->destination)
                    {{ $slip->destination->location_name }}
                    @if ($slip->destination->trashed())
                        <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                    @endif
                @else
                    <span style="color: #dc2626; font-weight: bold;">[Location] (Deleted)</span>
                @endif
            </div>
        </div>

        <div class="form-field">
            <div class="form-label">Name of Driver:</div>
            <div class="form-value">
                @if ($slip->driver)
                    {{ trim($slip->driver->first_name . ' ' . ($slip->driver->middle_name ?? '') . ' ' . $slip->driver->last_name) }}
                    @if ($slip->driver->trashed())
                        <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                    @endif
                @else
                    <span style="color: #dc2626; font-weight: bold;">[Driver] (Deleted)</span>
                @endif
            </div>
        </div>

        <div class="form-field">
            <div class="form-label">Reason for Disinfection:</div>
            <div class="form-value">{{ $slip->reason_for_disinfection ?? '' }}</div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Hatchery Guard CG/AG:</div>
                <div class="signature-line">
                    @if ($slip->hatcheryGuard)
                        <span class="signature-name">
                            {{ trim($slip->hatcheryGuard->first_name . ' ' . ($slip->hatcheryGuard->middle_name ?? '') . ' ' . $slip->hatcheryGuard->last_name) }}
                            @if ($slip->hatcheryGuard->trashed())
                                <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                            @endif
                        </span>
                    @else
                        <span class="signature-name" style="color: #dc2626; font-weight: bold;">[Guard] (Deleted)</span>
                    @endif
                </div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Received by:</div>
                <div class="signature-line">
                    @if ($slip->receivedGuard)
                        <span class="signature-name">
                            {{ trim($slip->receivedGuard->first_name . ' ' . ($slip->receivedGuard->middle_name ?? '') . ' ' . $slip->receivedGuard->last_name) }}
                            @if ($slip->receivedGuard->trashed())
                                <span style="color: #dc2626; font-weight: bold;">(Deleted)</span>
                            @endif
                        </span>
                    @else
                        <span class="signature-name" style="color: #dc2626; font-weight: bold;">[Guard] (Deleted)</span>
                    @endif
                    <span class="signature-subtext">Guard CG/AG</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

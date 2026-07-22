<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Export Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #6ABC45 0%, #5AA83A 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }

        .info-box {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #6ABC45;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
            color: #555;
        }

        .value {
            color: #333;
        }

        .footer {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-daily {
            background: #E3F2FD;
            color: #1976D2;
        }

        .badge-weekly {
            background: #F3E5F5;
            color: #7B1FA2;
        }

        .badge-monthly {
            background: #FFF3E0;
            color: #E65100;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>OCR Export Report</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ ucfirst($frequency) }} Scheduled Export</p>
    </div>

    <div class="content">
        <div class="info-box">
            <div class="info-row">
                <span class="label">Report Type:</span>
                <span class="value">
                    <span class="badge badge-{{ $frequency }}">{{ ucfirst($frequency) }}</span>
                </span>
            </div>
            <div class="info-row">
                <span class="label">Date Range:</span>
                <span class="value">{{ $dateRange }}</span>
            </div>
            <div class="info-row">
                <span class="label">Categories:</span>
                <span class="value">{{ $categories }}</span>
            </div>
            <div class="info-row">
                <span class="label">Total Documents:</span>
                <span class="value"><strong>{{ $documentCount }}</strong></span>
            </div>
        </div>

        <p>Hello,</p>
        <p>Your scheduled {{ $frequency }} OCR export report is ready. The report contains
            <strong>{{ $documentCount }}</strong> document(s) from the specified categories.
        </p>
        <p>The export file is attached to this email. You can open it with Microsoft Excel or any compatible spreadsheet
            application.</p>

        @if ($documentCount > 0)
            <p style="background: #E8F5E9; padding: 15px; border-radius: 6px; border-left: 4px solid #4CAF50;">
                ✅ <strong>Export completed successfully!</strong><br>
                All data has been processed and exported according to your schedule.
            </p>
        @else
            <p style="background: #FFF3E0; padding: 15px; border-radius: 6px; border-left: 4px solid #FF9800;">
                ℹ️ <strong>No documents found</strong><br>
                There were no documents matching your criteria for this period.
            </p>
        @endif
    </div>

    <div class="footer">
        <p>This is an automated email from your OCR Document AI System.</p>
        <p>© {{ date('Y') }} AkiraDocumentAI. All rights reserved.</p>
    </div>
</body>

</html>

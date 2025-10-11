<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #fff;
        }

        .container { max-width: 800px; margin: 0 auto; }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-info h1 {
            color: #2c5aa0;
            margin: 0;
            font-size: 18px;
        }

        .company-info p {
            margin: 0;
            font-size: 11px;
            color: #555;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            margin: 0;
            color: #2c5aa0;
            font-size: 22px;
        }

        .invoice-number {
            color: #666;
            font-size: 13px;
        }

        /* Billing Info */
        .billing-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .bill-to, .invoice-details {
            width: 48%;
            font-size: 11px;
        }

        .bill-to {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .bill-to h3, .invoice-details h3 {
            margin-bottom: 8px;
            font-size: 13px;
            color: #2c5aa0;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #2c5aa0;
            color: #fff;
            padding: 8px;
            font-size: 11px;
            text-align: left;
        }

        td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            font-size: 11px;
        }

        /* Totals */
        .totals {
            width: 250px;
            margin-left: auto;
            margin-top: 15px;
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .final {
            border-top: 2px solid #2c5aa0;
            font-weight: bold;
            font-size: 13px;
            color: #2c5aa0;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .status-draft { background: #6c757d; color: white; }
        .status-sent { background: #17a2b8; color: white; }
        .status-partially_paid { background: #ffc107; color: #212529; }
        .status-paid { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-overdue { background: #fd7e14; color: white; }

        /* Notes */
        .notes {
            background: #f8f9fa;
            border-left: 3px solid #2c5aa0;
            padding: 10px;
            margin-top: 20px;
            font-size: 11px;
            border-radius: 4px;
        }

        .notes h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #eee;
            margin-top: 30px;
            padding-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <h1>Your Company Name</h1>
            <p>123 Business Street</p>
            <p>City, State 12345</p>
            <p>Email: info@yourcompany.com</p>
        </div>
        <div class="invoice-title">
            <h2>INVOICE</h2>
            <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
        </div>
    </div>

    <!-- Billing Info -->
    <div class="billing-info">
        <div class="bill-to">
            <h3>Bill To:</h3>
            @if($invoice->client)
                <p><strong>{{ $invoice->client->name }}</strong></p>
                @if($invoice->client->company)
                    <p>{{ $invoice->client->company }}</p>
                @endif
                <p>{{ $invoice->client->email }}</p>
                @if($invoice->client->phone)
                    <p>{{ $invoice->client->phone }}</p>
                @endif
            @else
                <p>No client assigned</p>
            @endif
        </div>
        <div class="invoice-details">
            <h3>Details:</h3>
            <p><strong>Invoice Date:</strong> {{ optional($invoice->issue_date)->format('M d, Y') ?? '-' }}</p>
            <p><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('M d, Y') ?? '-' }}</p>
            <p><strong>Status:</strong></p>
            <span class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
            </span>
        </div>
    </div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->title }}</td>
                <td>${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            @if($invoice->amount_paid > 0)
            <tr>
                <td>Amount Paid</td>
                <td>-${{ number_format($invoice->amount_paid, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($invoice->amount, 2) }}</span>
        </div>
        @if($invoice->amount_paid > 0)
        <div class="total-row">
            <span>Paid:</span>
            <span>-${{ number_format($invoice->amount_paid, 2) }}</span>
        </div>
        @endif
        <div class="total-row final">
            <span>Balance Due:</span>
            <span>${{ number_format($invoice->amount - $invoice->amount_paid, 2) }}</span>
        </div>
    </div>

    <!-- Notes -->
    @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your business.</p>
        <p>If you have questions about this invoice, contact us at info@yourcompany.com</p>
    </div>
</div>
</body>
</html>

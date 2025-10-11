<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .company-info { margin-bottom: 30px; }
        .invoice-info { margin-bottom: 30px; }
        .details { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f5f5f5; }
        .total { text-align: right; font-size: 18px; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INVOICE</h1>
            <h2>{{ $invoice->invoice_number }}</h2>
        </div>

        <div class="company-info">
            <h3>Your Company Name</h3>
            <p>Your Company Address</p>
            <p>Phone: Your Phone Number</p>
            <p>Email: Your Email</p>
        </div>

        <div class="invoice-info">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <strong>Bill To:</strong><br>
                        @if($invoice->client)
                            {{ $invoice->client->name }}<br>
                            @if($invoice->client->company){{ $invoice->client->company }}<br>@endif
                            @if($invoice->client->email){{ $invoice->client->email }}<br>@endif
                            @if($invoice->client->phone){{ $invoice->client->phone }}<br>@endif
                        @else
                            No client specified
                        @endif
                    </td>
                    <td width="50%" style="text-align: right;">
                        <strong>Invoice Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br>
                        <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="details">
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
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
                <tfoot>
                    <tr>
                        <td><strong>Balance Due</strong></td>
                        <td><strong>${{ number_format($invoice->amount - $invoice->amount_paid, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us.</p>
        </div>
    </div>
</body>
</html>
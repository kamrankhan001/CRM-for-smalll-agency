<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-details { margin-bottom: 30px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
        </div>

        <div class="invoice-details">
            <p><strong>Title:</strong> {{ $invoice->title }}</p>
            <p><strong>Amount:</strong> ${{ number_format($invoice->amount, 2) }}</p>
            <p><strong>Amount Paid:</strong> ${{ number_format($invoice->amount_paid, 2) }}</p>
            <p><strong>Balance Due:</strong> ${{ number_format($invoice->amount - $invoice->amount_paid, 2) }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
        </div>

        @if($invoice->client)
        <div class="client-info">
            <h3>Client Information</h3>
            <p><strong>Name:</strong> {{ $invoice->client->name }}</p>
            @if($invoice->client->email)
            <p><strong>Email:</strong> {{ $invoice->client->email }}</p>
            @endif
            @if($invoice->client->company)
            <p><strong>Company:</strong> {{ $invoice->client->company }}</p>
            @endif
        </div>
        @endif

        @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <div class="footer">
            <p>This invoice was generated on {{ now()->format('M d, Y') }}</p>
            <p>Please contact us if you have any questions.</p>
        </div>
    </div>
</body>
</html>
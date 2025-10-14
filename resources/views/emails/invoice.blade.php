<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .invoice-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .invoice-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            margin-bottom: 12px;
        }
        
        .detail-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .amount-highlight {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .amount-total {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .amount-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .client-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        
        .section-title::before {
            content: "👤";
            margin-right: 8px;
        }
        
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .notes-section .section-title::before {
            content: "📝";
        }
        
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .company-name {
            color: #374151;
            font-weight: 600;
        }
        
        @media (max-width: 600px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
            <p>Thank you for your business</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Invoice Card -->
            <div class="invoice-card">
                <div class="invoice-header">
                    <div class="invoice-title">{{ $invoice->title }}</div>
                    <div class="status-badge status-{{ $invoice->status }}">
                        {{ str_replace('_', ' ', $invoice->status) }}
                    </div>
                </div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Issue Date</div>
                        <div class="detail-value">{{ $invoice->issue_date->format('M d, Y') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Due Date</div>
                        <div class="detail-value">{{ $invoice->due_date->format('M d, Y') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Amount Paid</div>
                        <div class="detail-value">${{ number_format($invoice->amount_paid, 2) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Balance Due</div>
                        <div class="detail-value">${{ number_format($invoice->amount - $invoice->amount_paid, 2) }}</div>
                    </div>
                </div>
                
                <div class="amount-highlight">
                    <div class="amount-total">${{ number_format($invoice->amount, 2) }}</div>
                    <div class="amount-label">Total Amount</div>
                </div>
            </div>
            
            <!-- Client Information -->
            @if($invoice->client)
            <div class="client-section">
                <div class="section-title">Client Information</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value">{{ $invoice->client->name }}</div>
                    </div>
                    @if($invoice->client->email)
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">{{ $invoice->client->email }}</div>
                    </div>
                    @endif
                    @if($invoice->client->company)
                    <div class="detail-item">
                        <div class="detail-label">Company</div>
                        <div class="detail-value">{{ $invoice->client->company }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            
            <!-- Notes -->
            @if($invoice->notes)
            <div class="notes-section">
                <div class="section-title">Additional Notes</div>
                <p>{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Your invoice PDF is attached to this email for your records.</p>
            <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
            <p>Generated on {{ now()->format('M d, Y \a\t g:i A') }}</p>
            <p class="company-name">{{ config('app.name', 'Your Company') }}</p>
        </div>
    </div>
</body>
</html>
<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class MarkInvoiceAsPaidAction
{
    public function execute(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'amount_paid' => $invoice->amount,
            'paid_at' => now(),
        ]);

        return $invoice;
    }
}

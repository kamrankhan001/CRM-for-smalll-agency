<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;

class UpdateInvoiceAction
{
    public function execute(Invoice $invoice, array $data): Invoice
    {
        if ($data['status'] === 'paid' && $data['amount_paid'] >= $data['amount']) {
            $data['paid_at'] = now();
        }

        $invoice->update($data);

        return $invoice;
    }
}

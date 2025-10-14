<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Models\User;

class CreateInvoiceAction
{
    public function execute(array $data, User $user): Invoice
    {
        $data['created_by'] = $user->id;
        $data['invoice_number'] = $this->generateInvoiceNumber();
        
        return Invoice::create($data);
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return 'INV-'.$year.$month.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class DeleteInvoiceAction
{
    public function execute(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->delete();
        });
    }
}
<?php

namespace App\Actions\Invoice;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Document;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SendInvoiceAction
{
    public function execute(Invoice $invoice): void
    {
        // Check if PDF already exists
        $existingDocument = Document::where('documentable_type', Invoice::class)
            ->where('documentable_id', $invoice->id)
            ->where('type', 'invoice')
            ->first();

        if ($existingDocument && Storage::disk('public')->exists($existingDocument->file_path)) {
            $pdfPath = $existingDocument->file_path;
        } else {
            // Generate new PDF
            $pdf = Pdf::loadView('pdf.invoice', compact('invoice'))->setPaper('a4', 'portrait');
            $pdfContent = $pdf->output();

            $pdfPath = "invoices/{$invoice->invoice_number}.pdf";
            Storage::disk('public')->put($pdfPath, $pdfContent);

            DB::transaction(function () use ($invoice, $pdfPath) {
                Document::updateOrCreate(
                    [
                        'documentable_type' => Invoice::class,
                        'documentable_id' => $invoice->id,
                        'type' => 'invoice',
                    ],
                    [
                        'title' => "Invoice {$invoice->invoice_number}",
                        'file_path' => $pdfPath,
                        'uploaded_by' => auth()->id(),
                    ]
                );
            });
        }

        // Update invoice status if still draft
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        // Dispatch queued email job
        if ($invoice->client && $invoice->client->email) {
            dispatch(new SendInvoiceEmailJob($invoice, storage_path('app/public/'.$pdfPath)));
        }
    }
}

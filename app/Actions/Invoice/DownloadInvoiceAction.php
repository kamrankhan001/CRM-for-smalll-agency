<?php

namespace App\Actions\Invoice;

use App\Models\Document;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInvoiceAction
{
    public function execute(Invoice $invoice): StreamedResponse
    {
        // Check if a PDF already exists for this invoice
        $existingDocument = Document::where('documentable_type', Invoice::class)
            ->where('documentable_id', $invoice->id)
            ->where('type', 'invoice')
            ->first();

        if ($existingDocument && Storage::disk('public')->exists($existingDocument->file_path)) {
            return Storage::disk('public')->download($existingDocument->file_path);
        }

        // Generate a fresh PDF if not found or missing
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ]);

        $pdfPath = "invoices/{$invoice->invoice_number}.pdf";

        // Save the PDF to public storage
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Record it in the documents table
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

        // Return a StreamedResponse instead of Response
        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "invoice-{$invoice->invoice_number}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}

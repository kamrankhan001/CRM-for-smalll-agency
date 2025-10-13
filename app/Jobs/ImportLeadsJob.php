<?php

namespace App\Jobs;

use App\Imports\LeadsImport;
use Illuminate\Bus\Queueable;
use App\Events\LeadImportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImportLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $filePath,
        protected int $userId
    ) {
        $this->cacheKey = "lead_import_{$userId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Run the import with user ID
        $import = new LeadsImport($this->userId); // Pass user ID here
        Excel::import($import, $this->filePath);

        // Gather results
        $result = [
            'imported' => $import->getImportedCount(),
            'errors'   => $import->getErrors(),
            'total'    => $import->getTotalRows(),
        ];

        // Store results temporarily in cache
        Cache::put($this->cacheKey, $result, now()->addMinutes(10));

        event(new LeadImportCompleted($this->userId, $result));

        // clean up uploaded file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }

    /**
     * Handle a failed job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Lead import job failed', [
            'user_id' => $this->userId,
            'file'    => $this->filePath,
            'error'   => $exception->getMessage(),
        ]);

        Cache::forget($this->cacheKey);
    }
}
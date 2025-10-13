<?php

namespace App\Jobs;

use App\Events\ExportCompleted;
use App\Exports\LeadsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExportLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $filePath, protected int $userId)
    {
        $this->cacheKey = "lead_export_{$userId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Trigger export completed event
        event(new ExportCompleted($this->filePath, $this->userId));
    }

    /**
     * Handle a failed job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Lead export job failed', [
            'user_id' => $this->userId,
            'filters' => $this->filePath,
            'error' => $exception->getMessage(),
        ]);

        Cache::forget($this->cacheKey);
    }
}

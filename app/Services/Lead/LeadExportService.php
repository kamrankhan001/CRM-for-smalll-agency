<?php

namespace App\Services\Lead;

use App\Exports\LeadsExport;
use App\Jobs\ExportLeadsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LeadExportService
{
    /**
     * Start the lead export process
     */
    public function startExport(array $filters, int $userId): void
    {
        // Clean up empty filters
        $cleanFilters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        // Create unique filename
        $filename = 'leads-'.now()->format('Y-m-d-His').'-'.Str::random(6).'.xlsx';
        $path = "temp_exports/{$filename}";

        // Queue the export
        (new LeadsExport($cleanFilters, $userId))
            ->queue($path)
            ->chain([
                new ExportLeadsJob($path, $userId),
            ]);

        // Store temp info in cache
        Cache::put("lead_export_{$userId}", $path, now()->addMinutes(10));
    }

    /**
     * Get export file path from cache
     */
    public function getExportFilePath(int $userId): ?string
    {
        return Cache::get("lead_export_{$userId}");
    }

    /**
     * Clear export cache
     */
    public function clearExportCache(int $userId): void
    {
        Cache::forget("lead_export_{$userId}");
    }
}

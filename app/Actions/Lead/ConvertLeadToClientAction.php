<?php

namespace App\Actions\Lead;

use App\Models\Lead;
use App\Models\User;
use App\Services\Lead\LeadConversionService;
use Illuminate\Support\Facades\DB;

class ConvertLeadToClientAction
{
    public function __construct(
        private LeadConversionService $conversionService
    ) {}

    /**
     * Convert lead to client and update lead status to qualified
     */
    public function execute(Lead $lead, User $currentUser): void
    {
        // Validate that lead can be converted
        if (!$this->conversionService->canConvertLead($lead)) {
            throw new \Exception('This lead has already been converted to a client.');
        }

        DB::transaction(function () use ($lead, $currentUser) {
            // Update lead status to qualified
            $this->conversionService->markLeadAsQualified($lead);

            // Create client from lead (inheriting assignment)
            $this->conversionService->convertLeadToClient($lead, $currentUser, true);
        });
    }
}
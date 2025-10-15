<?php

namespace App\Actions\Lead;

use App\Models\Lead;
use App\Models\User;
use App\Models\Client;
use App\Services\Lead\LeadConversionService;
use App\Notifications\LeadConvertedNotification;
use Illuminate\Support\Facades\DB;

class ConvertLeadToClientAction
{
    public function __construct(
        private LeadConversionService $conversionService
    ) {}

    public function execute(Lead $lead, User $currentUser): void
    {
        if (!$this->conversionService->canConvertLead($lead)) {
            throw new \Exception('This lead has already been converted to a client.');
        }

        DB::transaction(function () use ($lead, $currentUser) {
            // Update lead status to qualified
            $this->conversionService->markLeadAsQualified($lead);

            // Create client from lead
            $client = $this->conversionService->convertLeadToClient($lead, $currentUser, true);

            // Send notifications to admins and managers (excluding the user who converted)
            $this->sendNotifications($lead, $client, $currentUser);
        });
    }

    private function sendNotifications(Lead $lead, Client $client, User $convertedBy): void
    {
        // Get users who should receive notifications (admins and managers)
        $recipients = User::where(function ($query) use ($convertedBy) {
                $query->where('role', 'admin')
                      ->orWhere('role', 'manager');
            })
            ->where('id', '!=', $convertedBy->id)
            ->get();

        // Send notification to each recipient
        foreach ($recipients as $recipient) {
            $recipient->notify(new LeadConvertedNotification($lead, $client, $convertedBy));
        }
    }
}
<?php

namespace App\Actions\Lead;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Services\Lead\LeadConversionService;
use Illuminate\Support\Facades\DB;

class UpdateLeadAction
{
    public function __construct(
        private LeadConversionService $conversionService
    ) {}

    /**
     * Update lead and handle conversion to client if status changed to qualified
     */
    public function execute(Lead $lead, array $data, User $currentUser): void
    {
        // Check if status is changing to qualified
        $isConvertingToClient = $data['status'] === 'qualified' && $lead->status !== 'qualified';

        // Check if assignment is changing to a different user
        $oldAssignedTo = $lead->assigned_to;
        $newAssignedTo = $data['assigned_to'] ?? null;

        DB::transaction(function () use ($lead, $data, $isConvertingToClient, $currentUser, $newAssignedTo, $oldAssignedTo) {
            $lead->update($data);

            // Convert lead to client if status changed to qualified
            if ($isConvertingToClient && $this->conversionService->canConvertLead($lead)) {
                $this->conversionService->convertLeadToClient($lead, $currentUser, false);
            }

            // Send assignment notification
            if ($newAssignedTo && $newAssignedTo != $oldAssignedTo && $newAssignedTo != $currentUser->id) {
                $assignedUser = User::find($newAssignedTo);
                $assignedUser->notify(new LeadAssignedNotification($lead));
            }
        });
    }
}

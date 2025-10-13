<?php

namespace App\Actions\Lead;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;

class CreateLeadAction
{
    /**
     * Create a new lead and send notification if assigned to another user
     */
    public function execute(array $data, User $currentUser): Lead
    {
        $data['created_by'] = $currentUser->id;
        
        $lead = Lead::create($data);

        // Send notification if assigned to someone else
        if ($lead->assigned_to && $lead->assigned_to != $currentUser->id) {
            $assignedUser = User::find($lead->assigned_to);
            $assignedUser->notify(new LeadAssignedNotification($lead));
        }

        return $lead;
    }
}
<?php

namespace App\Services\Lead;

use App\Models\Lead;
use App\Models\Client;
use App\Models\User;
use App\Notifications\LeadConvertedNotification;

class LeadConversionService
{
    /**
     * Convert a lead to a client with optional assignment inheritance
     */
    public function convertLeadToClient(Lead $lead, User $currentUser, bool $inheritAssignment = false): Client
    {
        $clientData = [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'lead_id' => $lead->id,
            'created_by' => $currentUser->id,
        ];

        // Set assignment based on the flag
        $clientData['assigned_to'] = $inheritAssignment ? $lead->assigned_to : null;

        $client = Client::create($clientData);

        $this->sendConversionNotifications($lead, $client, $currentUser);

        return $client;
    }

    /**
     * Send notifications for lead conversion
     */
    private function sendConversionNotifications(Lead $lead, Client $client, User $currentUser): void
    {
        // Send notification to admin users
        $adminUsers = User::where('role', 'admin')->get();
        foreach ($adminUsers as $admin) {
            $admin->notify(new LeadConvertedNotification($lead, $client));
        }

        // Notify the assigned user if different from current user
        if ($lead->assignee && $lead->assignee->id != $currentUser->id) {
            $lead->assignee->notify(new LeadConvertedNotification($lead, $client));
        }
    }

    /**
     * Check if a lead can be converted to client
     */
    public function canConvertLead(Lead $lead): bool
    {
        return !$lead->client; // Only convert if not already converted
    }

    /**
     * Update lead status to qualified
     */
    public function markLeadAsQualified(Lead $lead): void
    {
        $lead->update(['status' => 'qualified']);
    }
}
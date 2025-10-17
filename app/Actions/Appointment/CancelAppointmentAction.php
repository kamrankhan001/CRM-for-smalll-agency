<?php

namespace App\Actions\Appointment;

use App\Models\Appointment;
use App\Models\User;
use App\Services\Appointment\AppointmentNotificationService;

class CancelAppointmentAction
{
    public function __construct(
        private AppointmentNotificationService $notificationService
    ) {}

    public function execute(Appointment $appointment, User $currentUser): Appointment
    {
        $appointment->update(['status' => 'cancelled']);
        
        // Send notifications to relevant users (admins, assigned users, project members)
        // The notification service will automatically exclude the current user
        $this->notificationService->sendAppointmentNotifications($appointment, $currentUser, 'updated');
        
        return $appointment;
    }
}
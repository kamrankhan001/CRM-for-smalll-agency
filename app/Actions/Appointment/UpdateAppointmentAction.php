<?php

namespace App\Actions\Appointment;

use App\Concerns\HasMorphTypes;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Appointment\AppointmentNotificationService;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    use HasMorphTypes;

    public function __construct(
        private AppointmentNotificationService $notificationService
    ) {}

    public function execute(Appointment $appointment, array $data, User $currentUser): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $currentUser) {
            $data['appointable_type'] = $this->mapMorphType($data['appointable_type']);

            $appointment->update($data);
            
            $this->notificationService->sendAppointmentNotifications($appointment, $currentUser, 'updated');
            
            return $appointment;
        });
    }
}
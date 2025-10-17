<?php

namespace App\Actions\Appointment;

use App\Concerns\HasMorphTypes;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Appointment\AppointmentNotificationService;
use Illuminate\Support\Facades\DB;

class CreateAppointmentAction
{
    use HasMorphTypes;

    public function __construct(
        private AppointmentNotificationService $notificationService
    ) {}

    public function execute(array $data, User $currentUser): Appointment
    {
        return DB::transaction(function () use ($data, $currentUser) {
            $data['appointable_type'] = $this->mapMorphType($data['appointable_type']);
            $data['created_by'] = $currentUser->id;

            $appointment = Appointment::create($data);

            $this->notificationService->sendAppointmentNotifications($appointment, $currentUser, 'created');

            return $appointment;
        });
    }
}

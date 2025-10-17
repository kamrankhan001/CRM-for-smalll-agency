<?php

namespace App\Services\Appointment;

use App\Concerns\HasMorphTypes;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentQueryService
{
    use HasMorphTypes;

    public function getFilteredAppointments(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Appointment::with(['creator', 'appointable']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        // Apply role-based restrictions for members
        if ($currentUser->role === 'member') {
            $query->where('created_by', $currentUser->id);
        }

        return $query->latest()->paginate(10);
    }

    public function transformAppointmentsForResponse(LengthAwarePaginator $appointments): array
    {
        $transformedAppointments = $appointments->through(fn ($appointment) => [
            'id' => $appointment->id,
            'title' => $appointment->title,
            'date' => $appointment->date->toDateString(),
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'status' => $appointment->status,
            'appointable' => $appointment->appointable ? [
                'id' => $appointment->appointable->id,
                'type' => $this->getShortMorphType($appointment->appointable_type),
                'name' => $appointment->appointable->name ?? 'N/A',
            ] : null,
            'creator' => $appointment->creator ? [
                'id' => $appointment->creator->id,
                'name' => $appointment->creator->name,
            ] : null,
        ]);

        return [
            'data' => $transformedAppointments->items(),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
                'from' => $appointments->firstItem(),
                'to' => $appointments->lastItem(),
                'prev_page_url' => $appointments->previousPageUrl(),
                'next_page_url' => $appointments->nextPageUrl(),
            ],
            'links' => $appointments->linkCollection()->toArray(),
        ];
    }

    public function getAppointmentWithRelations(Appointment $appointment): array
    {
        $appointment->load(['creator', 'appointable', 'activities.causer']);

        return [
            'appointment' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'appointable_type' => $this->getShortMorphType($appointment->appointable_type),
                'appointable_id' => $appointment->appointable_id,
                'date' => $appointment->date->toDateString(),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status,
                'appointable' => $appointment->appointable ? [
                    'id' => $appointment->appointable->id,
                    'type' => $this->getShortMorphType($appointment->appointable_type),
                    'name' => $appointment->appointable->name ?? 'N/A',
                ] : null,
                'creator' => $appointment->creator ? [
                    'id' => $appointment->creator->id,
                    'name' => $appointment->creator->name,
                ] : null,
                'created_at' => $appointment->created_at,
                'updated_at' => $appointment->updated_at,
            ],
            'activities' => $appointment->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'created_at' => $activity->created_at,
                ];
            }),
        ];
    }
}
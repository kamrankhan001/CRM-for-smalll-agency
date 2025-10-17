<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityQueryService
{
    public function getFilteredActivities(array $filters, User $currentUser): LengthAwarePaginator
    {
        $query = Activity::with(['causer']);

        // Apply role-based restrictions
        if ($currentUser->role === 'manager') {
            // Managers cannot see admin activities
            $query->whereHas('causer', fn ($q) => $q->where('role', '!=', 'admin'));
        } elseif ($currentUser->role === 'member') {
            // Members can only see their own activities
            $query->where('causer_id', $currentUser->id);
        }

        // Apply user filter
        if (! empty($filters['user'])) {
            $query->whereHas('causer', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['user']}%");
            });
        }

        // Apply model filter
        if (! empty($filters['model'])) {
            $query->where('subject_type', 'like', "%{$filters['model']}%");
        }

        // Apply action filter
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(10);
    }

    public function transformActivitiesForResponse(LengthAwarePaginator $activities): array
    {
        $transformedActivities = $activities->through(fn ($activity) => [
            'id' => $activity->id,
            'description' => $activity->description,
            'model_type' => class_basename($activity->subject_type),
            'action' => $activity->action,
            'created_at' => $activity->created_at->toDateTimeString(),
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
                'role' => $activity->causer->role,
            ] : null,
        ]);

        return [
            'data' => $transformedActivities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
                'prev_page_url' => $activities->previousPageUrl(),
                'next_page_url' => $activities->nextPageUrl(),
            ],
            'links' => $activities->linkCollection()->toArray(),
        ];
    }

    public function getActivityWithRelations(Activity $activity): array
    {
        $activity->load(['causer', 'subject']);

        return [
            'activity' => [
                'id' => $activity->id,
                'description' => $activity->description,
                'action' => $activity->action,
                'changes' => $activity->changes,
                'created_at' => $activity->created_at->toISOString(),
                'updated_at' => $activity->updated_at->toISOString(),
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'role' => $activity->causer->role,
                    'email' => $activity->causer->email,
                ] : null,
                'subject' => $activity->subject ? [
                    'id' => $activity->subject->id,
                    'name' => $activity->subject->name ?? $activity->subject->title ?? null,
                    'type' => class_basename($activity->subject_type),
                ] : null,
            ],
        ];
    }

    public function getAvailableUsers(): array
    {
        return User::select('id', 'name')->get()->toArray();
    }
}

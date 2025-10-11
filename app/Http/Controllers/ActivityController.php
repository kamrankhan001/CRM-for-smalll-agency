<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', Activity::class);

        $activities = Activity::with(['causer'])
            ->when($user->role === 'manager', function ($query) {
                // Managers cannot see admin activities
                $query->whereHas('causer', fn ($q) => $q->where('role', '!=', 'admin'));
            })
            ->when($user->role === 'member', function ($query) use ($user) {
                // Members can only see their own activities
                $query->where('causer_id', $user->id);
            })
            ->when($request->filled('user'), function ($query) use ($request) {
                $query->whereHas('causer', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->user}%");
                });
            })
            ->when($request->filled('model'), function ($query) use ($request) {
                $query->where('subject_type', 'like', "%{$request->model}%");
            })
            ->when($request->filled('action'), function ($query) use ($request) {
                $query->where('action', $request->action);
            })
            ->when($request->filled('date_from'), function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->filled('date_to'), function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->paginate(10)
            ->through(fn ($activity) => [
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

        $users = User::select('id', 'name')->get();

        return Inertia::render('activities/Index', [
            'activities' => [
                'data' => $activities->items(),
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
            ],
            'filters' => $request->only(['user', 'model', 'action', 'date_from', 'date_to']),
            'users' => $users,
        ]);
    }

    /**
     * Display the specified activity.
     */
    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        $activity->load(['causer', 'subject']);

        return Inertia::render('activities/Show', [
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
        ]);
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return back()->with('success', 'Activity deleted successfully.');
    }
}

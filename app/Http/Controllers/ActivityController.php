<?php

namespace App\Http\Controllers;

use App\Actions\Activity\DeleteActivityAction;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Services\Activity\ActivityQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ActivityQueryService $activityQueryService,
        private readonly DeleteActivityAction $deleteActivityAction,
    ) {}

    /**
     * Display a listing of the activities with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Activity::class);

        $filters = request()->only(['user', 'model', 'action', 'date_from', 'date_to']);
        $activities = $this->activityQueryService->getFilteredActivities($filters, request()->user());
        $transformedActivities = $this->activityQueryService->transformActivitiesForResponse($activities);

        return Inertia::render('activities/Index', [
            'activities' => $transformedActivities,
            'filters' => $filters,
            'users' => $this->activityQueryService->getAvailableUsers(),
        ]);
    }

    /**
     * Display the specified activity with all related data
     */
    public function show(Activity $activity): Response
    {
        $this->authorize('view', $activity);

        $activityData = $this->activityQueryService->getActivityWithRelations($activity);

        return Inertia::render('activities/Show', $activityData);
    }

    /**
     * Remove the specified activity from storage
     */
    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        try {
            $this->deleteActivityAction->execute($activity);

            return back()->with('success', 'Activity deleted successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete activity: '.$e->getMessage());
        }
    }
}
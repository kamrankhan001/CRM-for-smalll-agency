<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ProjectQueryService $projectQueryService,
        private CreateProjectAction $createProjectAction,
        private UpdateProjectAction $updateProjectAction,
        private DeleteProjectAction $deleteProjectAction
    ) {}

    /**
     * Display a listing of the projects with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Project::class);

        $filters = request()->only(['search', 'status', 'client_id', 'lead_id', 'created_by', 'date_from', 'date_to']);
        $projects = $this->projectQueryService->getFilteredProjects($filters, auth()->user());
        $transformedProjects = $this->projectQueryService->transformProjectsForResponse($projects);

        $clients = Client::select('id', 'name')->get();
        $leads = Lead::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        return Inertia::render('projects/Index', [
            'projects' => $transformedProjects,
            'filters' => $filters,
            'clients' => $clients,
            'leads' => $leads,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new project
     */
    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/Create', [
            'clients' => Client::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created project in storage
     */
    public function store(CreateProjectRequest $request): RedirectResponse
    {
        try {
            $this->createProjectAction->execute($request->validated(), $request->user());

            return redirect()->route('projects.index')
                ->with('success', 'Project created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create project: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified project with all related data
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        $projectData = $this->projectQueryService->getProjectWithRelations($project);

        return Inertia::render('projects/Show', $projectData);
    }

    /**
     * Show the form for editing the specified project
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'start_date' => $project->start_date?->toDateString(),
                'end_date' => $project->end_date?->toDateString(),
                'client_id' => $project->client_id,
                'lead_id' => $project->lead_id,
                'members' => $project->members->map(fn ($member) => ['id' => $member->id]),
                'creator' => $project->creator ? [
                    'id' => $project->creator->id,
                    'name' => $project->creator->name,
                ] : null,
                'client' => $project->client ? [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                ] : null,
                'lead' => $project->lead ? [
                    'id' => $project->lead->id,
                    'name' => $project->lead->name,
                ] : null,
                'created_by' => $project->created_by,
                'created_at' => $project->created_at->toDateString(),
                'updated_at' => $project->updated_at->toDateString(),
            ],
            'clients' => Client::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified project in storage
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {

        try {
            $this->updateProjectAction->execute($project, $request->validated(), $request->user());

            return redirect()->route('projects.index')
                ->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update project: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified project from storage
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        try {
            $this->deleteProjectAction->execute($project);

            return redirect()->route('projects.index')
                ->with('success', 'Project deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete project: '.$e->getMessage());
        }
    }
}

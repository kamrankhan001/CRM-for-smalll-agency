<?php

namespace App\Http\Controllers;

use App\Concerns\HasMorphTypes;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentUpdated;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    use AuthorizesRequests, HasMorphTypes;

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::with(['creator', 'appointable'])
            ->when($request->search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status)
            )
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', $request->date_from)
            )
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', $request->date_to)
            )
            ->when($request->user()->role === 'member', fn ($query) => $query->where('created_by', $request->user()->id)
            )
            ->latest()
            ->paginate(10)
            ->through(fn ($appointment) => [
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

        return Inertia::render('appointments/Index', [
            'appointments' => [
                'data' => $appointments->items(),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'total' => $appointments->total(),
                ],
            ],
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new appointment.
     */
    public function create(): Response
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('appointments/Create', [
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'morphTypes' => $this->getMorphTypeOptions(),
        ]);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointable_type' => 'required|string|in:lead,client,project',
            'appointable_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $validated['appointable_type'] = $this->mapMorphType($validated['appointable_type']);
        $validated['created_by'] = $request->user()->id;

        try {
            DB::transaction(function () use ($validated, $request) {
                $appointment = Appointment::create($validated);

                $relatedUsers = User::where('role', '!=', 'admin')
                    ->where('id', '!=', $request->user()->id)
                    ->get();

                foreach ($relatedUsers as $user) {
                    $user->notify(new AppointmentCreated($appointment));
                }
            });

            return redirect()->route('appointments.index')->with('success', 'Appointment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create appointment: '.$e->getMessage());
        }
    }

    /**
     * Display a specific appointment.
     */
    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);
        $appointment->load(['creator', 'appointable', 'activities.causer']);

        $simplifiedType = $this->getShortMorphType($appointment->appointable_type);

        return Inertia::render('appointments/Show', [
            'appointment' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'appointable_type' => $simplifiedType,
                'appointable_id' => $appointment->appointable_id,
                'date' => $appointment->date->toDateString(),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status,
                'appointable' => $appointment->appointable ? [
                    'id' => $appointment->appointable->id,
                    'type' => $simplifiedType,
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
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'morphTypes' => $this->getMorphTypeOptions(),
        ]);
    }

    /**
     * Show the form for editing the appointment.
     */
    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);
        $simplifiedType = $this->getShortMorphType($appointment->appointable_type);

        return Inertia::render('appointments/Edit', [
            'appointment' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'appointable_type' => $simplifiedType,
                'appointable_id' => $appointment->appointable_id,
                'date' => $appointment->date->toDateString(),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status,
            ],
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'morphTypes' => $this->getMorphTypeOptions(),
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointable_type' => 'required|string|in:lead,client,project',
            'appointable_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $validated['appointable_type'] = $this->mapMorphType($validated['appointable_type']);

        try {
            DB::transaction(function () use ($appointment, $validated, $request) {
                $appointment->update($validated);

                $relatedUsers = User::where('role', '!=', 'admin')
                    ->where('id', '!=', $request->user()->id)
                    ->get();

                foreach ($relatedUsers as $user) {
                    $user->notify(new AppointmentUpdated($appointment));
                }
            });

            return redirect()->route('appointments.index', $appointment)->with('success', 'Appointment updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update appointment: '.$e->getMessage());
        }
    }

    /**
     * Cancel the appointment.
     */
    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $appointment->update(['status' => 'cancelled']);
        $appointment->creator->notify(new AppointmentUpdated($appointment));

        return redirect()->back()->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Project;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentUpdated;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::with(['creator', 'appointable'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->date_to);
            })
            ->when($request->user()->role === 'member', function ($query) use ($request) {
                // Members see only appointments they created or related to
                $query->where('created_by', $request->user()->id);
            })
            ->latest()
            ->paginate(10)
            ->through(fn($appointment) => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'date' => $appointment->date->toDateString(),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status,
                'appointable' => $appointment->appointable ? [
                    'id' => $appointment->appointable->id,
                    'type' => class_basename($appointment->appointable_type),
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
        ]);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointable_type' => 'required|string|in:App\\Models\\Lead,App\\Models\\Client,App\\Models\\Project',
            'appointable_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $validated['created_by'] = $request->user()->id;

        try {
            DB::transaction(function () use ($validated, $request) {
                $appointment = Appointment::create($validated);

                // Notify creator and related users
                $relatedUsers = User::where('role', '!=', 'admin')
                    ->where('id', '!=', $request->user()->id)
                    ->get();

                foreach ($relatedUsers as $user) {
                    $user->notify(new AppointmentCreated($appointment));
                }
            });

            return redirect()->route('appointments.index')->with('success', 'Appointment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create appointment: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific appointment.
     */
    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointment->load(['creator', 'appointable']);

        return Inertia::render('appointments/Show', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Show the form for editing the appointment.
     */
    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        return Inertia::render('appointments/Edit', [
            'appointment' => $appointment,
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointable_type' => 'required|string|in:App\\Models\\Lead,App\\Models\\Client,App\\Models\\Project',
            'appointable_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        try {
            DB::transaction(function () use ($appointment, $validated, $request) {
                $appointment->update($validated);

                // Notify relevant users about the update
                $relatedUsers = User::where('role', '!=', 'admin')
                    ->where('id', '!=', $request->user()->id)
                    ->get();

                foreach ($relatedUsers as $user) {
                    $user->notify(new AppointmentUpdated($appointment));
                }
            });

            return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update appointment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the appointment.
     */
    public function cancel(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $appointment->update(['status' => 'cancelled']);

        $appointment->creator->notify(new AppointmentUpdated($appointment));

        return redirect()->back()->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Appointment deleted successfully.');
    }
}

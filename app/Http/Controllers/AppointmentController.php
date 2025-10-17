<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\CreateAppointmentAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\Concerns\HasMorphTypes;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Services\Appointment\AppointmentQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    use AuthorizesRequests, HasMorphTypes;

    public function __construct(
        private AppointmentQueryService $appointmentQueryService,
        private CreateAppointmentAction $createAppointmentAction,
        private UpdateAppointmentAction $updateAppointmentAction,
        private CancelAppointmentAction $cancelAppointmentAction
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = $this->appointmentQueryService->getFilteredAppointments(
            $request->only(['search', 'status', 'date_from', 'date_to']),
            $request->user()
        );

        $transformedAppointments = $this->appointmentQueryService->transformAppointmentsForResponse($appointments);

        return Inertia::render('appointments/Index', [
            'appointments' => $transformedAppointments,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

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

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        try {
            $this->createAppointmentAction->execute($request->validated(), $request->user());

            return redirect()->route('appointments.index')
                ->with('success', 'Appointment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create appointment: '.$e->getMessage());
        }
    }

    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointmentData = $this->appointmentQueryService->getAppointmentWithRelations($appointment);

        return Inertia::render('appointments/Show', array_merge($appointmentData, [
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'morphTypes' => $this->getMorphTypeOptions(),
        ]));
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        return Inertia::render('appointments/Edit', [
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
            ],
            'users' => User::select('id', 'name')->get(),
            'leads' => Lead::select('id', 'name')->get(),
            'clients' => Client::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->get(),
            'morphTypes' => $this->getMorphTypeOptions(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        try {
            $this->updateAppointmentAction->execute($appointment, $request->validated(), $request->user());

            return redirect()->route('appointments.index')
                ->with('success', 'Appointment updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update appointment: '.$e->getMessage());
        }
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        try {
            $this->cancelAppointmentAction->execute($appointment, request()->user());

            return redirect()->back()->with('success', 'Appointment cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel appointment: '.$e->getMessage());
        }
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        try {
            $appointment->delete();

            return redirect()->route('appointments.index')->with('success', 'Appointment deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete appointment: '.$e->getMessage());
        }

    }
}

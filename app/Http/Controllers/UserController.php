<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\User\CreateUserRequest;
use Illuminate\Http\Request;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\User\UserQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserQueryService $userQueryService,
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly DeleteUserAction $deleteUserAction,
    ) {}

    /**
     * Display a listing of the users with filters and pagination
     */
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = request()->only(['search', 'role', 'date_from', 'date_to']);
        $users = $this->userQueryService->getFilteredUsers($filters);
        $transformedUsers = $this->userQueryService->transformUsersForResponse($users);

        return Inertia::render('users/Index', [
            'users' => $transformedUsers,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new user
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create');
    }

    /**
     * Store a newly created user in storage
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        try {
            $this->createUserAction->execute($request->validated());

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create user: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified user with all related data
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $userData = $this->userQueryService->getUserWithRelations($user);

        return Inertia::render('users/Show', $userData);
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/Edit', [
            'user' => $this->userQueryService->getUserForEdit($user),
        ]);
    }

    /**
     * Update the specified user in storage
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->updateUserAction->execute($user, $request->validated());

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update user: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        try {
            $this->deleteUserAction->execute($user);

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete user: '.$e->getMessage());
        }
    }
}
<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Support\Facades\DB;

class UpdateProjectAction
{
    public function execute(Project $project, array $data, User $currentUser): void
    {
        DB::transaction(function () use ($project, $data, $currentUser) {
            // Get old members before update
            $oldMembers = $project->members()->pluck('users.id')->toArray();

            $project->update($data);

            if (isset($data['members'])) {
                $project->members()->sync($data['members']);

                // Find new members only
                $newMembers = array_diff($data['members'], $oldMembers);

                foreach ($newMembers as $memberId) {
                    if ($memberId != $currentUser->id) {
                        $member = User::find($memberId);
                        if ($member && $member->role !== 'admin') {
                            $member->notify(new ProjectAssignedNotification($project, $currentUser));
                        }
                    }
                }
            }
        });
    }
}
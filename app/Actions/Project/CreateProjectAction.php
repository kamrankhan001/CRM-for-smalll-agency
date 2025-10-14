<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    public function execute(array $data, User $currentUser): Project
    {
        return DB::transaction(function () use ($data, $currentUser) {
            $data['created_by'] = $currentUser->id;
            
            $project = Project::create($data);

            if (!empty($data['members'])) {
                $project->members()->sync($data['members']);

                // Notify only assigned members (excluding creator and admin users)
                foreach ($data['members'] as $memberId) {
                    if ($memberId != $currentUser->id) {
                        $member = User::find($memberId);
                        if ($member && $member->role !== 'admin') {
                            $member->notify(new ProjectAssignedNotification($project));
                        }
                    }
                }
            }

            return $project;
        });
    }
}
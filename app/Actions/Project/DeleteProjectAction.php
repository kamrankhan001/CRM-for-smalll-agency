<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DeleteProjectAction
{
    public function execute(Project $project): void
    {
        DB::transaction(function () use ($project) {
            // Detach members before deletion
            $project->members()->detach();

            $project->delete();
        });
    }
}

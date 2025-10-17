<?php

namespace App\Actions\Task;

use App\Concerns\HasMorphTypes;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;

class CreateTaskAction
{
    use HasMorphTypes;

    public function execute(array $data, User $currentUser): void
    {
        $data['taskable_type'] = $this->mapMorphType($data['taskable_type']);
        $data['created_by'] = $currentUser->id;

        $task = Task::create($data);

        if ($task->assigned_to && $task->assigned_to != $currentUser->id) {
            $assignedUser = User::find($task->assigned_to);
            $assignedUser->notify(new TaskAssignedNotification($task, $currentUser));
        }
    }
}

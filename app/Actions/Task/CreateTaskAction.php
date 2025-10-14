<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Concerns\HasTaskableType;

class CreateTaskAction
{
    use HasTaskableType;
    
    public function execute(array $data, User $currentUser): void
    {
        $data['taskable_type'] = $this->mapTaskableType($data['taskable_type']);
        $data['created_by'] = $currentUser->id;

        $task = Task::create($data);

        if ($task->assigned_to && $task->assigned_to != $currentUser->id) {
            $assignedUser = User::find($task->assigned_to);
            $assignedUser->notify(new TaskAssignedNotification($task));
        }
    }
}

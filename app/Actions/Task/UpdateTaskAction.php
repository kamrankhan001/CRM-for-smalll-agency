<?php

namespace App\Actions\Task;

use App\Concerns\HasTaskableType;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;

class UpdateTaskAction
{
    use HasTaskableType;

    public function execute(Task $task, array $data, User $currentUser): void
    {
        $oldAssignedTo = $task->assigned_to;
        $newAssignedTo = $data['assigned_to'] ?? null;

        $data['taskable_type'] = $this->mapTaskableType($data['taskable_type']);

        $task->update($data);

        if ($newAssignedTo && $newAssignedTo != $oldAssignedTo && $newAssignedTo != $currentUser->id) {
            $assignedUser = User::find($newAssignedTo);
            $assignedUser->notify(new TaskAssignedNotification($task));
        }
    }
}

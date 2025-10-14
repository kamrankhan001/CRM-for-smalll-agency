<?php

namespace App\Actions\Task;

use App\Concerns\HasMorphTypes;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;

class UpdateTaskAction
{
    use HasMorphTypes;

    public function execute(Task $task, array $data, User $currentUser): void
    {
        $oldAssignedTo = $task->assigned_to;
        $newAssignedTo = $data['assigned_to'] ?? null;

        $data['taskable_type'] = $this->mapMorphType($data['taskable_type']);

        $task->update($data);

        if ($newAssignedTo && $newAssignedTo != $oldAssignedTo && $newAssignedTo != $currentUser->id) {
            $assignedUser = User::find($newAssignedTo);
            $assignedUser->notify(new TaskAssignedNotification($task));
        }
    }
}

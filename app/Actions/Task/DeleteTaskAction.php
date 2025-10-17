<?php

namespace App\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTaskAction
{
    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();
        });
    }
}

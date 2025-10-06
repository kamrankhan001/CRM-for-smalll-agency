<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Carbon\Carbon;
use App\Notifications\TaskDueSoonNotification;

class NotifyTaskDueSoon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users of tasks due in 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $tomorrow = Carbon::tomorrow();
        $tasks = Task::whereDate('due_date', $tomorrow)->with('assigned_to')->get();

        foreach ($tasks as $task) {
            $task->assigned_to?->notify(new TaskDueSoonNotification($task));
        }

        $this->info('Task due soon notifications sent.');
    }
}

<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task, public User $assignedBy)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Task Assigned')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("A new task has been assigned to you: {$this->task->title} by {$this->assignedBy->name}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please check and complete it before the deadline.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'task_assigned',
            'message' => "New task '{$this->task->title}' has been assigned to you by {$this->assignedBy->name}.",
            'task_id' => $this->task->id,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'url' => "/tasks/{$this->task->id}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'task_assigned',
            'message' => "New task '{$this->task->title}' has been assigned to you by {$this->assignedBy->name}.",
            'task_id' => $this->task->id,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'url' => "/tasks/{$this->task->id}",
            'time' => now()->toDateTimeString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.'.$this->task->assigned_to)];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

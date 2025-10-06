<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Task $task)
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Task Assigned')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("A new task has been assigned to you: {$this->task->title}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please check and complete it before the deadline.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'task_assigned',
            'message' => "New task '{$this->task->title}' has been assigned to you.",
            'task_id' => $this->task->id,
            'url' => "/tasks/{$this->task->id}",
        ];
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

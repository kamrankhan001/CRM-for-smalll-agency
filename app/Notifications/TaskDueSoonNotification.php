<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification implements ShouldQueue
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
            ->subject('Task Due Soon')
            ->line("Your task '{$this->task->title}' is due within 24 hours.")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please ensure it is completed on time.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'task_due_soon',
            'message' => "Task '{$this->task->title}' is due in 24 hours.",
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

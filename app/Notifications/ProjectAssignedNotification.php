<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Project $project)
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
            ->subject('New Project Assigned')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("You have been added as a member to the project: {$this->project->name}.")
            ->action('View Project', url("/projects/{$this->project->id}"))
            ->line('Please check your tasks and collaborate with the team.');
    }

    /**
     * Store notification in the database.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'project_assigned',
            'message' => "You have been added to the project '{$this->project->name}'.",
            'project_id' => $this->project->id,
            'url' => "/projects/{$this->project->id}",
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

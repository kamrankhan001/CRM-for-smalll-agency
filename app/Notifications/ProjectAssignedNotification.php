<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAssignedNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Project $project, public User $assignedBy)
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
            ->subject('New Project Assigned')
            ->greeting('Hello '.$notifiable->name.',')
            ->line("You have been added as a member to the project: {$this->project->name} by {$this->assignedBy->name}.")
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
            'message' => "You have been added to the project '{$this->project->name}' by {$this->assignedBy->name}.",
            'project_id' => $this->project->id,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'url' => "/projects/{$this->project->id}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'project_assigned',
            'message' => "You have been added to the project '{$this->project->name}' by {$this->assignedBy->name}.",
            'project_id' => $this->project->id,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'url' => "/projects/{$this->project->id}",
            'time' => now()->toDateTimeString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.'.$this->project->assigned_to)];
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

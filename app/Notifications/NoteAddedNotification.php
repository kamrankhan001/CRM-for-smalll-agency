<?php

namespace App\Notifications;

use App\Models\Note;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NoteAddedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
     public function __construct(public Note $note, public User $createdBy)
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
        return ['database', 'broadcast'];
    }


    public function toDatabase($notifiable)
    {
        return [
            'type' => 'note_added',
            'message' => "A new note '{$this->note->title}' was added by {$this->createdBy->name}",
             'note_id' => $this->note->id,
            'created_by_id' => $this->createdBy->id,
            'created_by_name' => $this->createdBy->name,
            'url' => "/notes/{$this->note->id}",
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'note_added',
            'message' => "A new note '{$this->note->title}' was added by {$this->createdBy->name}",
            'note_id' => $this->note->id,
            'created_by_id' => $this->createdBy->id,
            'created_by_name' => $this->createdBy->name,
            'url' => "/notes/{$this->note->id}",
            'time' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new Channel('notifications.global')];
    }

    /**
     * Determine which users should receive the notification.
     * Since this goes to all users, we always return true.
     */
    public function shouldSend(object $notifiable): bool
    {
        return true;
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

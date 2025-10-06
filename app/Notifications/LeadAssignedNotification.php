<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lead;


class LeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Lead $lead)
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
            ->subject('New Lead Assigned')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("You have been assigned a new lead: {$this->lead->name}.")
            ->action('View Lead', url("/leads/{$this->lead->id}"))
            ->line('Please follow up as soon as possible.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'lead_assigned',
            'message' => "New lead '{$this->lead->name}' has been assigned to you.",
            'lead_id' => $this->lead->id,
            'url' => "/leads/{$this->lead->id}",
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

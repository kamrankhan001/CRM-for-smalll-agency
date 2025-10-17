<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Appointment $appointment,
        public User $creator
    ) {}

    /**
     * Get the notification's delivery channels.
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
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';

        return (new MailMessage)
            ->subject("New Appointment Scheduled - {$this->appointment->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new appointment has been scheduled by {$this->creator->name}.")
            ->line('**Appointment Details:**')
            ->line("- Title: {$this->appointment->title}")
            ->line("- Date: {$this->appointment->date->format('M d, Y')}")
            ->line("- Time: {$this->appointment->start_time} - {$this->appointment->end_time}")
            ->line("- Related {$appointableType}: {$appointableName}")
            ->line('- Status: '.ucfirst($this->appointment->status))
            ->action('View Appointment', $this->getAppointmentUrl())
            ->line('Thank you for using our application!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';

        return [
            'type' => 'appointment_created',
            'message' => "New appointment '{$this->appointment->title}' scheduled by {$this->creator->name} for {$appointableType}: {$appointableName}",
            'appointment_id' => $this->appointment->id,
            'creator_id' => $this->creator->id,
            'creator_name' => $this->creator->name,
            'appointable_type' => $appointableType,
            'appointable_name' => $appointableName,
            'url' => $this->getAppointmentUrl(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';

        return new BroadcastMessage([
            'type' => 'appointment_created',
            'message' => "New appointment '{$this->appointment->title}' scheduled by {$this->creator->name} for {$appointableType}: {$appointableName}",
            'appointment_id' => $this->appointment->id,
            'creator_name' => $this->creator->name,
            'appointable_type' => $appointableType,
            'appointable_name' => $appointableName,
            'url' => $this->getAppointmentUrl(),
            'time' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.'.$this->appointment->created_by)];
    }

    /**
     * Get the appointable type in readable format.
     */
    protected function getAppointableType(): string
    {
        return match ($this->appointment->appointable_type) {
            'App\\Models\\Lead' => 'Lead',
            'App\\Models\\Client' => 'Client',
            'App\\Models\\Project' => 'Project',
            default => 'Item'
        };
    }

    /**
     * Get the appointment URL.
     */
    protected function getAppointmentUrl(): string
    {
        return url("/appointments/{$this->appointment->id}");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

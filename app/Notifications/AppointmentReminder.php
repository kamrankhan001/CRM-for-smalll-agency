<?php

namespace App\Notifications;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';
        $appointmentDateTime = Carbon::parse($this->appointment->date->format('Y-m-d').' '.$this->appointment->start_time);
        $timeUntilAppointment = now()->diffForHumans($appointmentDateTime, true);

        return (new MailMessage)
            ->subject("⏰ Appointment Starting Soon - {$this->appointment->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your appointment is starting in **{$timeUntilAppointment}**!")
            ->line('**Appointment Details:**')
            ->line("- **Title:** {$this->appointment->title}")
            ->line("- **Time:** {$appointmentDateTime->format('l, F j, Y \\a\\t g:i A')}")
            ->line("- **Duration:** {$this->appointment->start_time} - {$this->appointment->end_time}")
            ->line("- **Related {$appointableType}:** {$appointableName}")
            ->action('View Appointment Details', $this->getAppointmentUrl())
            ->line('Please be prepared for your appointment.')
            ->line('Thank you!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';
        $appointmentDateTime = Carbon::parse($this->appointment->date->format('Y-m-d').' '.$this->appointment->start_time);
        $timeUntilAppointment = now()->diffForHumans($appointmentDateTime, true);

        return [
            'type' => 'appointment_reminder',
            'message' => "⏰ Appointment '{$this->appointment->title}' starts in {$timeUntilAppointment}",
            'appointment_id' => $this->appointment->id,
            'appointable_type' => $appointableType,
            'appointable_name' => $appointableName,
            'url' => $this->getAppointmentUrl(),
            'reminder_time' => now()->toDateTimeString(),
            'appointment_time' => $appointmentDateTime->toDateTimeString(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $appointableType = $this->getAppointableType();
        $appointableName = $this->appointment->appointable->name ?? 'N/A';
        $appointmentDateTime = Carbon::parse($this->appointment->date->format('Y-m-d').' '.$this->appointment->start_time);
        $timeUntilAppointment = now()->diffForHumans($appointmentDateTime, true);

        return new BroadcastMessage([
            'type' => 'appointment_reminder',
            'message' => "⏰ '{$this->appointment->title}' starts in {$timeUntilAppointment}",
            'appointment_id' => $this->appointment->id,
            'appointable_type' => $appointableType,
            'appointable_name' => $appointableName,
            'url' => $this->getAppointmentUrl(),
            'time' => now()->toDateTimeString(),
            'appointment_time' => $appointmentDateTime->toDateTimeString(),
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

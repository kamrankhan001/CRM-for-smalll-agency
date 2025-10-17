<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Services\Appointment\AppointmentNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders 
                            {--minutes=15 : Minutes before appointment to send reminder}
                            {--test : Send test reminders for debugging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders to relevant users X minutes before the appointment';

    public function __construct(
        private AppointmentNotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutesBefore = (int) $this->option('minutes');
        $isTest = $this->option('test');

        $this->info("Starting appointment reminders for appointments in {$minutesBefore} minutes...");

        if ($isTest) {
            $this->sendTestReminders();
            return;
        }

        $this->sendScheduledReminders($minutesBefore);
    }

    /**
     * Send reminders for actual upcoming appointments.
     */
    protected function sendScheduledReminders(int $minutesBefore): void
    {
        $reminderTime = now()->addMinutes($minutesBefore);
        
        // Find appointments that start exactly at the reminder time (within 1 minute window)
        $startWindow = $reminderTime->copy()->subMinute();
        $endWindow = $reminderTime->copy()->addMinute();

        $appointments = Appointment::with(['creator', 'appointable'])
            ->where('status', 'confirmed') // Only send reminders for confirmed appointments
            ->whereDate('date', $reminderTime->toDateString())
            ->whereTime('start_time', '>=', $startWindow->format('H:i:s'))
            ->whereTime('start_time', '<=', $endWindow->format('H:i:s'))
            ->get();

        $this->info("Found {$appointments->count()} appointments starting around {$reminderTime->format('H:i')}.");

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            try {
                // Use the specialized reminder method that includes everyone
                $notifiableUsers = $this->notificationService->getReminderNotifiableUsers($appointment);
                
                foreach ($notifiableUsers as $user) {
                    $user->notify(new AppointmentReminder($appointment));
                    $sentCount++;
                }

                $this->info("Sent reminders for appointment: {$appointment->title} ({$appointment->date} {$appointment->start_time}) - Notified " . count($notifiableUsers) . " users");

            } catch (\Exception $e) {
                $this->error("Failed to send reminder for appointment {$appointment->id}: " . $e->getMessage());
                Log::error("Appointment reminder failed for appointment {$appointment->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully sent {$sentCount} appointment reminders.");
    }

    /**
     * Send test reminders for debugging.
     */
    protected function sendTestReminders(): void
    {
        $this->info("Sending test reminders...");

        // Get a confirmed appointment for testing (preferably one starting soon)
        $appointment = Appointment::with(['creator', 'appointable'])
            ->where('status', 'confirmed')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        if (!$appointment) {
            $this->warn("No confirmed appointments found for testing.");
            return;
        }

        $this->info("Test appointment: {$appointment->title} ({$appointment->date} {$appointment->start_time})");

        try {
            // Use the specialized reminder method that includes everyone
            $notifiableUsers = $this->notificationService->getReminderNotifiableUsers($appointment);
            
            $sentCount = 0;
            foreach ($notifiableUsers as $user) {
                $user->notify(new AppointmentReminder($appointment));
                $sentCount++;
                $this->info("Sent test reminder to: {$user->name} ({$user->email})");
            }

            $this->info("Successfully sent {$sentCount} test reminders to " . count($notifiableUsers) . " users.");

        } catch (\Exception $e) {
            $this->error("Failed to send test reminders: " . $e->getMessage());
        }
    }
}
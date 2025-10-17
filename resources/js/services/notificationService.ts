import type {
    LeadAssignedNotification,
    NoteAddedNotification,
    ProjectAssignedNotification,
    TaskAssignedNotification,
    AppointmentCreatedNotification,
    AppointmentUpdatedNotification,
    AppointmentReminderNotification,
} from '@/types/notifications';
import { toast } from 'vue-sonner';

class NotificationService {
    private handlers: Map<string, (notification: any) => void> = new Map();

    constructor() {
        this.registerHandlers();
    }

    private registerHandlers() {
        // Lead Assigned Handler
        this.handlers.set('lead_assigned', (notification: LeadAssignedNotification) => {
            toast.success(notification.message ?? 'You have a new lead!', {
                description: notification.time ? `Assigned at ${notification.time}` : undefined,
                action: {
                    label: 'View Lead',
                    onClick: () => window.location.href = notification.url ?? `/leads/${notification.lead_id}`,
                },
            });
        });

        // Note Added Handler
        this.handlers.set('note_added', (notification: NoteAddedNotification) => {
            toast.info('New Note Added', {
                description: notification.message,
                action: {
                    label: 'View Note',
                    onClick: () => window.location.href = notification.url ?? `/notes/${notification.note_id}`,
                },
            });
        });

        // Project Assigned Handler
        this.handlers.set('project_assigned', (notification: ProjectAssignedNotification) => {
            toast.success('Added to Project', {
                description: notification.message,
                action: {
                    label: 'View Project',
                    onClick: () => window.location.href = notification.url ?? `/projects/${notification.project_id}`,
                },
            });
        });

        // Task Assigned Handler
        this.handlers.set('task_assigned', (notification: TaskAssignedNotification) => {
            toast.success('New Task Assigned', {
                description: notification.message,
                action: {
                    label: 'View Task',
                    onClick: () => window.location.href = notification.url ?? `/tasks/${notification.task_id}`,
                },
            });
        });

        // Appointment Created Handler
        this.handlers.set('appointment_created', (notification: AppointmentCreatedNotification) => {
            toast.info('📅 New Appointment', {
                description: notification.message,
                action: {
                    label: 'View Appointment',
                    onClick: () => window.location.href = notification.url ?? `/appointments/${notification.appointment_id}`,
                },
            });
        });

        // Appointment Updated Handler
        this.handlers.set('appointment_updated', (notification: AppointmentUpdatedNotification) => {
            toast.warning('📅 Appointment Updated', {
                description: notification.message,
                action: {
                    label: 'View Changes',
                    onClick: () => window.location.href = notification.url ?? `/appointments/${notification.appointment_id}`,
                },
            });
        });

        // Appointment Reminder Handler
        this.handlers.set('appointment_reminder', (notification: AppointmentReminderNotification) => {
            const appointmentTime = new Date(notification.appointment_time);
            const timeUntil = this.getTimeUntil(appointmentTime);
            
            toast.warning('⏰ Appointment Reminder', {
                description: `${notification.message}\nStarts ${timeUntil}`,
                duration: 10000, // Show for 10 seconds
                action: {
                    label: 'View Details',
                    onClick: () => window.location.href = notification.url ?? `/appointments/${notification.appointment_id}`,
                },
            });
        });
    }

    private getTimeUntil(appointmentTime: Date): string {
        const now = new Date();
        const diffMs = appointmentTime.getTime() - now.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        
        if (diffMins < 1) {
            return 'now';
        } else if (diffMins < 60) {
            return `in ${diffMins} minute${diffMins > 1 ? 's' : ''}`;
        } else if (diffHours < 24) {
            return `in ${diffHours} hour${diffHours > 1 ? 's' : ''}`;
        } else {
            const diffDays = Math.floor(diffHours / 24);
            return `in ${diffDays} day${diffDays > 1 ? 's' : ''}`;
        }
    }

    public handleNotification(notification: any) {
        // Extract the simple type from the notification data
        const notificationData = notification;
        
        let simpleType: string | null = null;

        // Check if it's the full class name format
        if (notificationData.type && notificationData.type.includes('\\')) {
            simpleType = this.extractSimpleType(notificationData.type);
        } 
        // Check if it's already a simple type in the data
        else if (notificationData.type) {
            simpleType = notificationData.type;
        }

        if (!simpleType) {
            console.warn('Could not extract notification type from:', notificationData);
            return;
        }

        const handler = this.handlers.get(simpleType);
        if (handler) {
            handler(notificationData);
        } else {
            console.warn(`No handler found for notification type: ${simpleType}`);
        }
    }

    private extractSimpleType(fullType: string): string | null {
        if (!fullType) return null;
        
        // Convert "App\Notifications\LeadAssignedNotification" to "lead_assigned"
        const matches = fullType.match(/(\w+)Notification$/);
        if (matches && matches[1]) {
            // Convert PascalCase to snake_case
            return matches[1]
                .replace(/([A-Z])/g, '_$1')
                .toLowerCase()
                .replace(/^_/, '');
        }
        return null;
    }

    public getChannel(userId: string | number): string {
        return `notifications.${userId}`;
    }

    public getEventClasses(): string[] {
        return [
            'App\\Notifications\\LeadAssignedNotification',
            'App\\Notifications\\NoteAddedNotification', 
            'App\\Notifications\\ProjectAssignedNotification',
            'App\\Notifications\\TaskAssignedNotification',
            'App\\Notifications\\AppointmentCreatedNotification',
            'App\\Notifications\\AppointmentUpdatedNotification',
            'App\\Notifications\\AppointmentReminderNotification'
        ];
    }
}

export const notificationService = new NotificationService();
export interface BaseNotification {
    message: string;
    type: string;
    time?: string;
    url?: string;
}

export interface LeadAssignedNotification extends BaseNotification {
    type: 'lead_assigned';
    lead_id: string | number;
    assigned_by?: string;
}

export interface NoteAddedNotification extends BaseNotification {
    type: 'note_added';
    note_id: string | number;
    title?: string;
    created_by_id?: string | number;
    created_by_name?: string;
}

export interface ProjectAssignedNotification extends BaseNotification {
    type: 'project_assigned';
    project_id: string | number;
    assigned_by_id: string | number;
    assigned_by_name: string;
}

export interface TaskAssignedNotification extends BaseNotification {
    type: 'task_assigned';
    task_id: string | number;
    assigned_by_id: string | number;
    assigned_by_name: string;
}


export interface AppointmentCreatedNotification extends BaseNotification {
    type: 'appointment_created';
    appointment_id: string | number;
    creator_id: string | number;
    creator_name: string;
    appointable_type: string;
    appointable_name: string;
}

export interface AppointmentUpdatedNotification extends BaseNotification {
    type: 'appointment_updated';
    appointment_id: string | number;
    updater_id: string | number;
    updater_name: string;
    appointable_type: string;
    appointable_name: string;
}

export interface AppointmentReminderNotification extends BaseNotification {
    type: 'appointment_reminder';
    appointment_id: string | number;
    appointable_type: string;
    appointable_name: string;
    appointment_time: string;
    reminder_time: string;
}

export type AppNotification = 
    | LeadAssignedNotification 
    | NoteAddedNotification 
    | ProjectAssignedNotification
    | TaskAssignedNotification
    | AppointmentCreatedNotification
    | AppointmentUpdatedNotification
    | AppointmentReminderNotification;

export interface NotificationHandler {
    type: string;
    channel: string;
    handle: (notification: any) => void;
}
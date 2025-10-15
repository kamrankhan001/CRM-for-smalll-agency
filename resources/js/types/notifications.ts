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

export type AppNotification = 
    | LeadAssignedNotification 
    | NoteAddedNotification 
    | ProjectAssignedNotification
    | TaskAssignedNotification;

export interface NotificationHandler {
    type: string;
    channel: string;
    handle: (notification: any) => void;
}
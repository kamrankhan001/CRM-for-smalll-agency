<script setup lang="ts">
import {
    destroy,
    edit,
    index,
    cancel,
} from '@/actions/App/Http/Controllers/AppointmentController';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    Calendar,
    Clock,
    Edit,
    MapPin,
    Trash2,
    User,
    XCircle,
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Appointable {
    id: number;
    name: string;
    type: string;
}

interface Activity {
    id: number;
    description: string;
    causer: User | null;
    created_at: string;
}

interface Appointment {
    id: number;
    title: string;
    description: string | null;
    appointable_type: string;
    appointable_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    status: 'pending' | 'confirmed' | 'cancelled';
    created_by: number;
    creator?: User | null;
    appointable?: Appointable | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    appointment: Appointment;
    activities?: Activity[]; // Make activities optional
}

const props = withDefaults(defineProps<Props>(), {
    activities: () => [] // Default to empty array if not provided
});
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: index.url() },
    { title: props.appointment.title, href: '#' },
];

const showDeleteDialog = ref(false);
const showCancelDialog = ref(false);
const timeRemaining = ref('');

// Permissions computed properties
const canEdit = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    if (user.role === 'admin') return true;
    return props.appointment.created_by === user.id;
});

const canDelete = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    return user.role === 'admin' || props.appointment.created_by === user.id;
});

function getStatusColor(status: string) {
    const colors = {
        pending: 'bg-blue-100 text-blue-800 border-blue-200',
        confirmed: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800 border-gray-200';
}

function getStatusIcon(status: string) {
    const icons = {
        pending: Clock,
        confirmed: Calendar,
        cancelled: XCircle,
    };
    return icons[status as keyof typeof icons] || AlertCircle;
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatTime(timeString: string): string {
    if (!timeString) return '';
    
    try {
        // Handle different time formats: "20:13:00" or "20:13"
        const timeParts = timeString.split(':');
        const hour = parseInt(timeParts[0]);
        const minutes = timeParts[1] || '00';
        
        if (hour === 0) {
            return `12:${minutes} AM`;
        } else if (hour === 12) {
            return `12:${minutes} PM`;
        } else if (hour > 12) {
            return `${hour - 12}:${minutes} PM`;
        } else {
            return `${hour}:${minutes} AM`;
        }
    } catch {
        return timeString;
    }
}

function formatTimeRange(startTime: string, endTime: string): string {
    return `${formatTime(startTime)} - ${formatTime(endTime)}`;
}

function formatDateTime(date: string, time: string) {
    const dateTime = new Date(`${date}T${time}`);
    return dateTime.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function calculateRemainingTime() {
    const now = new Date();
    const start = new Date(`${props.appointment.date}T${props.appointment.start_time}`);
    const diffMs = start.getTime() - now.getTime();

    if (diffMs <= 0) {
        timeRemaining.value = 'Started or passed';
        return;
    }

    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

    if (days > 0) {
        timeRemaining.value = `${days}d ${hours}h ${minutes}m remaining`;
    } else if (hours > 0) {
        timeRemaining.value = `${hours}h ${minutes}m remaining`;
    } else {
        timeRemaining.value = `${minutes}m remaining`;
    }
}

function getDocumentableRoute(appointable: Appointable | null) {
    if (!appointable) return '#';
    const type = appointable.type.toLowerCase();
    return `/${type}s/${appointable.id}`;
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteAppointment() {
    router.delete(destroy.url(props.appointment.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}

function confirmCancel() {
    showCancelDialog.value = true;
}

function cancelAppointment() {
    router.patch(cancel.url(props.appointment.id));
    showCancelDialog.value = false;
}

function cancelCancel() {
    showCancelDialog.value = false;
}

onMounted(() => {
    calculateRemainingTime();
    setInterval(calculateRemainingTime, 60000); // update every minute
});
</script>

<template>
    <Head :title="props.appointment.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Back button and appointment info -->
                    <div class="flex items-center gap-4">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="index.url()">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                        >
                                            <ArrowLeft class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Back to appointments</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3"
                            >
                                <h1
                                    class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                                >
                                    {{ props.appointment.title }}
                                </h1>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :class="
                                            getStatusColor(props.appointment.status)
                                        "
                                        class="capitalize border"
                                    >
                                        <component 
                                            :is="getStatusIcon(props.appointment.status)" 
                                            class="h-3 w-3 mr-1" 
                                        />
                                        {{ props.appointment.status }}
                                    </Badge>
                                    <Badge
                                        v-if="timeRemaining && props.appointment.status !== 'cancelled'"
                                        variant="outline"
                                    >
                                        {{ timeRemaining }}
                                    </Badge>
                                </div>
                            </div>
                            <p
                                class="mt-1 text-sm text-muted-foreground sm:text-base"
                            >
                                Scheduled for {{ formatDate(props.appointment.date) }} at {{ formatTime(props.appointment.start_time) }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
                        <!-- Cancel Appointment Button -->
                        <TooltipProvider v-if="canEdit">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        @click="confirmCancel"
                                        variant="outline"
                                        class="flex h-9 w-9 items-center gap-2 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                    >
                                        <XCircle class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Cancel</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Cancel this appointment</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider v-if="canEdit">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.appointment.id)">
                                        <Button
                                            variant="outline"
                                            class="flex h-9 w-9 items-center gap-2 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Edit class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Edit</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Edit appointment information</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Delete Button -->
                        <TooltipProvider v-if="canDelete">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="destructive"
                                        class="flex h-9 w-9 items-center gap-2 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="confirmDelete"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Delete</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Delete this appointment</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Appointment Info & Description -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Appointment Information Card -->
                    <Card class="border shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Appointment Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Description -->
                            <div class="space-y-1" v-if="props.appointment.description">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Description</Label
                                >
                                <p class="text-sm whitespace-pre-wrap">
                                    {{ props.appointment.description }}
                                </p>
                            </div>

                            <!-- Date & Time Information -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Calendar class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Date</Label
                                        >
                                    </div>
                                    <p class="text-sm font-medium">
                                        {{ formatDate(props.appointment.date) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Clock class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Start Time</Label
                                        >
                                    </div>
                                    <p class="text-sm font-medium">
                                        {{ formatTime(props.appointment.start_time) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Clock class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >End Time</Label
                                        >
                                    </div>
                                    <p class="text-sm font-medium">
                                        {{ formatTime(props.appointment.end_time) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Duration</Label
                                >
                                <p class="text-sm font-medium">
                                    {{ formatTimeRange(props.appointment.start_time, props.appointment.end_time) }}
                                </p>
                            </div>

                            <!-- Status Information -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Current Status</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :class="
                                            getStatusColor(props.appointment.status)
                                        "
                                        class="capitalize border"
                                    >
                                        <component 
                                            :is="getStatusIcon(props.appointment.status)" 
                                            class="h-3 w-3 mr-1" 
                                        />
                                        {{ props.appointment.status }}
                                    </Badge>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            props.appointment.status === 'pending' 
                                                ? 'Awaiting confirmation'
                                                : props.appointment.status === 'confirmed'
                                                ? 'All parties confirmed'
                                                : 'Appointment cancelled'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Linked Entity -->
                            <div
                                v-if="props.appointment.appointable"
                                class="space-y-1"
                            >
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Related To</Label
                                >
                                <div class="flex items-center gap-2">
                                    <MapPin
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Link
                                        :href="
                                            getDocumentableRoute(
                                                props.appointment.appointable,
                                            )
                                        "
                                        class="text-sm text-primary hover:underline font-medium"
                                    >
                                        {{
                                            props.appointment.appointable.name
                                        }}
                                        ({{ props.appointment.appointable.type }})
                                    </Link>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Created By</Label
                                    >
                                    <p class="text-sm font-medium">
                                        {{ props.appointment.creator?.name || 'N/A' }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Created</Label
                                        >
                                    <p class="text-sm font-medium">
                                        {{ formatDateTime(props.appointment.created_at, '00:00') }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                        >
                                    <p class="text-sm font-medium">
                                        {{ formatDateTime(props.appointment.updated_at, '00:00') }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Activity & Statistics -->
                <div class="space-y-6">
                    <!-- Activity Log -->
                    <Card class="border shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="h-5 w-5" />
                                Activity Log
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.activities.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.activities && props.activities.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="activity in props.activities"
                                    :key="activity.id"
                                    class="flex gap-3 border-b pb-4 last:border-b-0 last:pb-0"
                                >
                                    <div
                                        class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-primary"
                                    ></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-foreground">
                                            {{ activity.description }}
                                        </p>
                                        <div
                                            class="mt-1 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                By
                                                {{
                                                    activity.causer?.name ||
                                                    'System'
                                                }}
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    formatDateTime(
                                                        activity.created_at,
                                                        '00:00'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-8 text-center">
                                <Activity
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No activity yet
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Activity will appear here as changes are
                                    made
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Appointment Statistics -->
                    <Card class="border shadow-sm">
                        <CardHeader>
                            <CardTitle>Appointment Statistics</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div
                                                class="cursor-default rounded-lg border bg-muted/5 p-4 text-center"
                                            >
                                                <div
                                                    class="text-2xl font-bold text-primary"
                                                >
                                                    {{
                                                        props.activities ? props.activities.length : 0
                                                    }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Activities
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>
                                                Total activities for this appointment
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div
                                                class="cursor-default rounded-lg border bg-muted/5 p-4 text-center"
                                            >
                                                <div
                                                    class="text-2xl font-bold text-primary"
                                                >
                                                    {{
                                                        props.appointment.status === 'cancelled' ? 'No' : 'Yes'
                                                    }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Active
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Appointment active status</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Appointment Information -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Appointment Info
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Status</span
                                        >
                                        <span
                                            class="text-sm font-medium capitalize"
                                        >
                                            {{ props.appointment.status }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Date</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{
                                                formatDate(props.appointment.date)
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Duration</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{ formatTimeRange(props.appointment.start_time, props.appointment.end_time) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Appointment"
                :description="`Are you sure you want to delete '${props.appointment.title}'? This action cannot be undone.`"
                confirm-text="Delete Appointment"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteAppointment"
                @cancel="cancelDelete"
            />

            <!-- Cancel Appointment Confirmation Dialog -->
            <ConfirmationDialog
                :show="showCancelDialog"
                title="Cancel Appointment"
                :description="`Are you sure you want to cancel '${props.appointment.title}'? This will mark the appointment as cancelled and notify relevant parties.`"
                confirm-text="Cancel Appointment"
                cancel-text="Keep Scheduled"
                variant="destructive"
                @confirm="cancelAppointment"
                @cancel="cancelCancel"
            />
        </div>
    </AppLayout>
</template>
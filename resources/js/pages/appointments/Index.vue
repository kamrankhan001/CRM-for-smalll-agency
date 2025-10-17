<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar } from 'v-calendar';
import 'v-calendar/dist/style.css';
import { computed, ref } from 'vue';

import {
    create,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/AppointmentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Eye, Filter, Pencil, Plus, Search, X } from 'lucide-vue-next';

interface Appointment {
    id: number;
    title: string;
    date: string;
    start_time: string;
    end_time: string;
    status: 'pending' | 'confirmed' | 'cancelled';
    appointable?: {
        id: number;
        type: string;
        name: string;
    } | null;
    creator?: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    appointments: {
        data: Appointment[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
        };
    };
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();
usePage();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '#' }];

// === STATE ===
const selectedDate = ref(new Date());
const mode = ref<'month' | 'week' | 'day'>('month');
const showFilters = ref(false);

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

// === USE BACKEND DATA ===
const appointmentsData = computed(() => props.appointments.data);

// === HELPERS ===
function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
        pending: 'blue',
        confirmed: 'green',
        cancelled: 'red',
    };
    return colors[status] ?? 'gray';
}

function getStatusText(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function getStatusBadgeColor(status: string): string {
    const colors: Record<string, string> = {
        pending: 'bg-blue-100 text-blue-800 border-blue-200',
        confirmed: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200',
    };
    return colors[status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

function formatTime(timeString: string): string {
    if (!timeString) return '';

    try {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const minute = minutes || '00';

        if (hour === 0) {
            return `12:${minute} AM`;
        } else if (hour === 12) {
            return `12:${minute} PM`;
        } else if (hour > 12) {
            return `${hour - 12}:${minute} PM`;
        } else {
            return `${hour}:${minute} AM`;
        }
    } catch {
        return timeString;
    }
}

function formatTimeRange(startTime: string, endTime: string): string {
    return `${formatTime(startTime)} - ${formatTime(endTime)}`;
}

// === CALENDAR ATTRIBUTES ===
const calendarAttributes = computed(() => {
    // Group appointments by date to show one dot per date
    const appointmentsByDate = new Map();

    appointmentsData.value.forEach((appointment) => {
        const date = appointment.date;
        if (!appointmentsByDate.has(date)) {
            appointmentsByDate.set(date, []);
        }
        appointmentsByDate.get(date).push(appointment);
    });

    // Create attributes for each date with appointments
    return Array.from(appointmentsByDate.entries()).map(
        ([date, appointments]) => {
            const hasMultipleStatuses =
                new Set(appointments.map((a: Appointment) => a.status)).size >
                1;
            const dotColor = hasMultipleStatuses
                ? 'purple'
                : getStatusColor(appointments[0].status);

            return {
                key: date,
                dates: new Date(date),
                dot: {
                    color: dotColor,
                    class: 'appointment-dot',
                },
                popover: {
                    label: `${appointments.length} appointment${appointments.length > 1 ? 's' : ''}`,
                    visibility: 'hover',
                },
                customData: appointments,
            };
        },
    ) as any[];
});

// === FILTERING ===
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        status: filters.value.status === null ? '' : filters.value.status,
    };

    router.get(index.url(), backendFilters, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        search: '',
        status: null,
        date_from: '',
        date_to: '',
    };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.status ||
        filters.value.date_from ||
        filters.value.date_to
    );
});

// === EVENT HANDLERS ===
function onDayClick(day: { date: Date }) {
    selectedDate.value = new Date(day.date);
}

function onAppointmentClick(appointment: Appointment, event: Event) {
    event.stopPropagation();
    router.get(show.url(appointment.id));
}

function onEditClick(appointment: Appointment, event: Event) {
    event.stopPropagation();
    router.get(edit.url(appointment.id));
}

// === COMPUTED ===
const appointmentsForSelectedDate = computed(() => {
    const selectedDateStr = selectedDate.value.toISOString().split('T')[0];
    return appointmentsData.value.filter((apt) => apt.date === selectedDateStr);
});

// Stats computed properties
const totalAppointments = computed(() => props.appointments.meta.total);
const pendingCount = computed(
    () => appointmentsData.value.filter((a) => a.status === 'pending').length,
);
const confirmedCount = computed(
    () => appointmentsData.value.filter((a) => a.status === 'confirmed').length,
);
const cancelledCount = computed(
    () => appointmentsData.value.filter((a) => a.status === 'cancelled').length,
);
</script>

<template>
    <Head title="Appointments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col p-6">
            <!-- Header with Stats -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="space-y-1">
                        <h1
                            class="text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            Appointments
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage and schedule appointments
                        </p>
                    </div>

                    <!-- All buttons container -->
                    <div
                        class="flex w-full items-center justify-between gap-2 lg:w-auto lg:justify-normal lg:gap-4"
                    >
                        <!-- Quick Stats - Left -->
                        <div class="flex gap-3">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-600">
                                    {{ totalAppointments }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Total
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-blue-600">
                                    {{ pendingCount }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Pending
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-green-600">
                                    {{ confirmedCount }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Confirmed
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-red-600">
                                    {{ cancelledCount }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Cancelled
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons - Right -->
                        <div class="flex items-center gap-2">
                            <!-- Filter Button -->
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="outline"
                                            @click="showFilters = !showFilters"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Filter class="h-4 w-4" />
                                            <span class="hidden lg:inline">
                                                {{
                                                    showFilters
                                                        ? 'Hide'
                                                        : 'Show'
                                                }}
                                                Filters
                                            </span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>
                                            {{ showFilters ? 'Hide' : 'Show' }}
                                            filters
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>

                            <!-- New Appointment Button -->
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Link
                                            :href="create.url()"
                                            class="shrink-0"
                                        >
                                            <Button
                                                size="sm"
                                                class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            >
                                                <Plus class="h-4 w-4" />
                                                <span class="hidden lg:inline"
                                                    >New Appointment</span
                                                >
                                            </Button>
                                        </Link>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>Create new appointment</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="search"
                                v-model="filters.search"
                                type="text"
                                placeholder="Search by title..."
                                class="pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select v-model="filters.status">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >All Statuses</SelectItem
                                >
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="confirmed"
                                    >Confirmed</SelectItem
                                >
                                <SelectItem value="cancelled"
                                    >Cancelled</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div class="space-y-2">
                        <Label for="date_from">Date From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            class="w-full"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Date To</Label>
                        <Input
                            id="date_to"
                            v-model="filters.date_to"
                            type="date"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="mt-4 flex justify-between">
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <div class="inline-block">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="resetFilters"
                                        :disabled="!hasActiveFilters"
                                        class="flex items-center gap-2"
                                    >
                                        <X class="h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent v-if="!hasActiveFilters">
                                <p>No active filters to clear</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <Button size="sm" @click="applyFilters">
                        Apply Filters
                    </Button>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- Calendar - Takes 2/3 width -->
                <div class="lg:flex-1">
                    <Card class="border shadow-sm">
                        <CardContent class="p-0">
                            <Calendar
                                v-model="selectedDate"
                                :mode="mode"
                                :attributes="calendarAttributes"
                                @dayclick="onDayClick"
                                :expanded="mode === 'month'"
                                class="w-full"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar - Takes 1/3 width -->
                <div class="w-full lg:w-1/3">
                    <!-- Selected Date Appointments -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <span>Appointments for</span>
                                <Badge
                                    variant="outline"
                                    class="text-sm font-medium"
                                >
                                    {{
                                        selectedDate.toLocaleDateString(
                                            'en-US',
                                            {
                                                weekday: 'short',
                                                month: 'short',
                                                day: 'numeric',
                                                year: 'numeric',
                                            },
                                        )
                                    }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    ({{ appointmentsForSelectedDate.length }})
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="appointment in appointmentsForSelectedDate"
                                :key="appointment.id"
                                class="group cursor-pointer rounded-lg border border-gray-200 p-4 transition-all hover:border-blue-200 hover:shadow-md"
                                @click="onAppointmentClick(appointment, $event)"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900 group-hover:text-blue-700"
                                        >
                                            {{ appointment.title }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs font-medium text-gray-600"
                                        >
                                            {{
                                                formatTimeRange(
                                                    appointment.start_time,
                                                    appointment.end_time,
                                                )
                                            }}
                                        </p>
                                        <p
                                            v-if="appointment.appointable"
                                            class="mt-1 text-xs font-medium text-blue-600"
                                        >
                                            {{ appointment.appointable.name }}
                                            ({{ appointment.appointable.type }})
                                        </p>
                                        <p
                                            v-if="appointment.creator"
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            Created by:
                                            {{ appointment.creator.name }}
                                        </p>
                                    </div>
                                    <div class="ml-2 flex items-center gap-1">
                                        <Badge
                                            :class="
                                                getStatusBadgeColor(
                                                    appointment.status,
                                                )
                                            "
                                            class="flex-shrink-0 border text-xs font-medium"
                                        >
                                            {{
                                                getStatusText(
                                                    appointment.status,
                                                )
                                            }}
                                        </Badge>

                                        <!-- Action Buttons -->
                                        <div
                                            class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <!-- Show button -->
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-7 w-7"
                                                            @click="
                                                                onAppointmentClick(
                                                                    appointment,
                                                                    $event,
                                                                )
                                                            "
                                                        >
                                                            <Eye
                                                                class="h-3.5 w-3.5 text-gray-600 hover:text-blue-600"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>View Details</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>

                                            <!-- Edit button -->
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-7 w-7"
                                                            @click="
                                                                onEditClick(
                                                                    appointment,
                                                                    $event,
                                                                )
                                                            "
                                                        >
                                                            <Pencil
                                                                class="h-3.5 w-3.5 text-gray-600 hover:text-green-600"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Edit Appointment</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="appointmentsForSelectedDate.length === 0"
                                class="py-8 text-center"
                            >
                                <div
                                    class="mx-auto max-w-[200px] text-muted-foreground"
                                >
                                    <div
                                        class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100"
                                    >
                                        <Search class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        No appointments
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        Select another date to view appointments
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.appointment-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

/* Calendar styling */
:deep(.vc-container) {
    border: none !important;
    font-family: inherit;
}

:deep(.vc-header) {
    background-color: transparent;
    padding: 1rem;
}

:deep(.vc-title) {
    font-weight: 600;
    color: rgb(17, 24, 39);
}

:deep(.vc-day) {
    min-height: 80px !important;
    position: relative;
}

:deep(.vc-day-content) {
    font-weight: 500;
    color: rgb(17, 24, 39);
}

:deep(.vc-day-content:hover) {
    background-color: rgb(59, 130, 246);
    color: white;
}

:deep(.vc-highlights) {
    border-radius: 6px;
}

:deep(.vc-day-layer) {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    padding-bottom: 4px;
    min-height: 24px;
}

:deep(.vc-dots) {
    display: flex;
    gap: 2px;
    justify-content: center;
}
</style>

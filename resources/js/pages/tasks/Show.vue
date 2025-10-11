<script setup lang="ts">
import {
    complete,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/TaskController';
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
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    Calendar,
    CheckCircle,
    ClipboardList,
    Clock,
    Edit,
    FileText,
    Trash2,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Taskable {
    id: number;
    name: string;
    type: string;
}

interface Note {
    id: number;
    content: string;
    user: User;
    created_at: string;
}

interface Activity {
    id: number;
    description: string;
    causer: User | null;
    created_at: string;
    properties?: any;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: string;
    due_date: string | null;
    taskable_type: string;
    taskable_id: number;
    assigned_to: number | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    assignee: User | null;
    creator: User | null;
    taskable: Taskable | null;
}

interface Props {
    task: Task;
    notes: Note[];
    activities: Activity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: index.url() },
    { title: props.task.title, href: '#' },
];

const showDeleteDialog = ref(false);
const showCompleteDialog = ref(false);

function getStatusColor(status: string) {
    const colors = {
        pending:
            'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        in_progress:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        completed:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return (
        colors[status as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function getPriorityColor(priority: string) {
    const colors = {
        low: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        urgent: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };
    return (
        colors[priority as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function formatDate(date: string | null) {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getDaysRemaining(dueDate: string | null) {
    if (!dueDate) return null;
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

function getDueDateStatus(dueDate: string | null) {
    if (!dueDate) return 'info';
    const daysRemaining = getDaysRemaining(dueDate);
    if (daysRemaining === null) return 'info';
    if (daysRemaining < 0) return 'overdue';
    if (daysRemaining <= 1) return 'urgent';
    if (daysRemaining <= 3) return 'warning';
    return 'info';
}

function getTaskableRoute(taskable: Taskable | null) {
    if (!taskable) return '#';
    const type = taskable.type.toLowerCase();
    return `/${type}s/${taskable.id}`;
}

function confirmComplete() {
    showCompleteDialog.value = true;
}

function markAsComplete() {
    router.put(complete.url(props.task.id));
    showCompleteDialog.value = false;
}

function cancelComplete() {
    showCompleteDialog.value = false;
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteTask() {
    router.delete(destroy.url(props.task.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="props.task.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Back button and task info -->
                    <div class="flex items-center gap-4">
                        <Link :href="index.url()">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-0"
                            >
                                <ArrowLeft class="h-4 w-4" />
                            </Button>
                        </Link>
                        <div class="min-w-0 flex-1">
                            <h1
                                class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                            >
                                {{ props.task.title }}
                            </h1>
                            <p
                                class="text-sm text-muted-foreground sm:text-base"
                            >
                                Task created
                                {{ formatDate(props.task.created_at) }}
                                <span v-if="props.task.taskable" class="ml-2">
                                    • Related to
                                    <Link
                                        :href="
                                            getTaskableRoute(
                                                props.task.taskable,
                                            )
                                        "
                                        class="text-primary hover:underline"
                                    >
                                        {{ props.task.taskable.name }}
                                    </Link>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
                        <!-- Mark as Complete Button -->
                        <TooltipProvider
                            v-if="props.task.status !== 'completed'"
                        >
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="confirmComplete"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Mark Complete</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Mark this task as completed</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.task.id)">
                                        <Button
                                            variant="outline"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
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
                                    <p>Edit task information</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Delete Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="destructive"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
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
                                    <p>Delete this task</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Task Info & Notes -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Task Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
                                Task Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Status & Priority -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Status</Label
                                    >
                                    <Badge
                                        :class="
                                            getStatusColor(props.task.status)
                                        "
                                        class="capitalize"
                                    >
                                        {{
                                            props.task.status.replace('_', ' ')
                                        }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Priority</Label
                                    >
                                    <Badge
                                        :class="
                                            getPriorityColor(
                                                props.task.priority,
                                            )
                                        "
                                        class="capitalize"
                                    >
                                        {{ props.task.priority }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Due Date -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Due Date</Label
                                >
                                <p class="flex items-center gap-2 text-sm">
                                    <Calendar class="h-4 w-4" />
                                    {{ formatDate(props.task.due_date) }}
                                    <span
                                        v-if="
                                            props.task.due_date &&
                                            getDaysRemaining(
                                                props.task.due_date,
                                            ) !== null
                                        "
                                        :class="{
                                            'text-red-600':
                                                getDueDateStatus(
                                                    props.task.due_date,
                                                ) === 'overdue',
                                            'text-orange-600':
                                                getDueDateStatus(
                                                    props.task.due_date,
                                                ) === 'urgent',
                                            'text-yellow-600':
                                                getDueDateStatus(
                                                    props.task.due_date,
                                                ) === 'warning',
                                            'text-muted-foreground':
                                                getDueDateStatus(
                                                    props.task.due_date,
                                                ) === 'info',
                                        }"
                                        class="text-xs font-medium"
                                    >
                                        ({{
                                            getDaysRemaining(
                                                props.task.due_date,
                                            )! < 0
                                                ? 'Overdue'
                                                : `${getDaysRemaining(props.task.due_date)} days left`
                                        }})
                                    </span>
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Description</Label
                                >
                                <p class="text-sm whitespace-pre-wrap">
                                    {{
                                        props.task.description ||
                                        'No description provided'
                                    }}
                                </p>
                            </div>

                            <!-- Assigned User -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Assigned To</Label
                                >
                                <div
                                    v-if="props.task.assignee"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                    >
                                        <span
                                            class="text-sm font-medium text-primary"
                                        >
                                            {{
                                                props.task.assignee.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ props.task.assignee.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ props.task.assignee.email }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    Not assigned
                                </p>
                            </div>

                            <!-- Related Entity -->
                            <div v-if="props.task.taskable" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Related To</Label
                                >
                                <div class="flex items-center gap-2">
                                    <AlertCircle
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Link
                                        :href="
                                            getTaskableRoute(
                                                props.task.taskable,
                                            )
                                        "
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.task.taskable.name }} ({{
                                            props.task.taskable.type
                                        }})
                                    </Link>
                                </div>
                            </div>

                            <!-- Assignment Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Created By</Label
                                    >
                                    <p class="text-sm">
                                        {{ props.task.creator?.name || 'N/A' }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Clock class="h-4 w-4" />
                                        {{ formatDate(props.task.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes Section -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Notes
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.notes.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                v-if="props.notes.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="note in props.notes"
                                    :key="note.id"
                                    class="rounded-lg border bg-muted/5 p-4"
                                >
                                    <p class="text-sm whitespace-pre-wrap">
                                        {{ note.content }}
                                    </p>
                                    <div
                                        class="mt-3 flex items-center justify-between border-t pt-3"
                                    >
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            Added by {{ note.user.name }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(note.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-8 text-center">
                                <FileText
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No notes yet
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Add the first note to track important task
                                    information
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Activity & Statistics -->
                <div class="space-y-6">
                    <!-- Activity Log -->
                    <Card class="border">
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
                                v-if="props.activities.length > 0"
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
                                                    formatDate(
                                                        activity.created_at,
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

                    <!-- Task Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Task Statistics</CardTitle>
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
                                                    {{ props.notes.length }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Notes
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total notes for this task</p>
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
                                                        props.activities.length
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
                                                Total activities for this task
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Timeline Information -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Timeline
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Created</span
                                        >
                                        <span class="text-sm font-medium">{{
                                            formatDate(props.task.created_at)
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Due Date</span
                                        >
                                        <span class="text-sm font-medium">{{
                                            formatDate(props.task.due_date)
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Last Updated</span
                                        >
                                        <span class="text-sm font-medium">{{
                                            formatDate(props.task.updated_at)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Mark Complete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showCompleteDialog"
                title="Mark Task as Complete"
                :description="`Are you sure you want to mark '${props.task.title}' as completed?`"
                confirm-text="Mark Complete"
                cancel-text="Cancel"
                variant="default"
                @confirm="markAsComplete"
                @cancel="cancelComplete"
            />

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Task"
                :description="`Are you sure you want to delete '${props.task.title}'? This action cannot be undone.`"
                confirm-text="Delete Task"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteTask"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

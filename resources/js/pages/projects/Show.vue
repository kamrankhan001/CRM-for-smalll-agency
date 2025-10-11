<script setup lang="ts">
import { create as createInvoice } from '@/actions/App/Http/Controllers/InvoiceController';
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/ProjectController';
import { create as createTask } from '@/actions/App/Http/Controllers/TaskController';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    ArrowLeft,
    Briefcase,
    Building,
    Calendar,
    CheckCircle,
    ClipboardList,
    Clock,
    DollarSign,
    Edit,
    FileText,
    Receipt,
    Trash2,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Client {
    id: number;
    name: string;
    company: string;
}

interface Lead {
    id: number;
    name: string;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    due_date: string | null;
    assigned_to: number | null;
    created_at: string;
}

interface Invoice {
    id: number;
    invoice_number: string;
    amount: number;
    status: string;
    due_date: string | null;
    created_at: string;
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

interface Project {
    id: number;
    name: string;
    description: string | null;
    status: 'planning' | 'in_progress' | 'on_hold' | 'completed';
    start_date: string | null;
    end_date: string | null;
    budget: number | null;
    client_id: number | null;
    lead_id: number | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    client: Client | null;
    lead: Lead | null;
    creator: User | null;
    members: User[];
}

interface Props {
    project: Project;
    tasks: Task[];
    invoices: Invoice[];
    notes: Note[];
    activities: Activity[];
    documents_count: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: index.url() },
    { title: props.project.name, href: '#' },
];

const showDeleteDialog = ref(false);

// FIXED: Use computed for totalInvoiceAmount with proper reduce implementation
const totalInvoiceAmount = computed(() => {
    return props.invoices.reduce(
        (sum: number, invoice: Invoice) => sum + invoice.amount,
        0,
    );
});

function getStatusColor(status: string) {
    const colors = {
        planning:
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        in_progress:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        on_hold:
            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        completed:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return (
        colors[status as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function getTaskStatusColor(status: string) {
    const colors = {
        pending:
            'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        in_progress:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        completed:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
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

function getInvoiceStatusColor(status: string) {
    const colors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        cancelled:
            'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };
    return (
        colors[status as keyof typeof colors] ||
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

function formatCurrency(amount: number | null) {
    if (!amount) return 'Not set';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function getDaysRemaining(endDate: string | null) {
    if (!endDate) return null;
    const today = new Date();
    const end = new Date(endDate);
    const diffTime = end.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

function getProgressStatus(endDate: string | null) {
    if (!endDate) return 'info';
    const daysRemaining = getDaysRemaining(endDate);
    if (daysRemaining === null) return 'info';
    if (daysRemaining < 0) return 'overdue';
    if (daysRemaining <= 7) return 'urgent';
    if (daysRemaining <= 30) return 'warning';
    return 'info';
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteProject() {
    router.delete(destroy.url(props.project.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="props.project.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Back button and project info -->
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
                        <div class="space-y-1">
                            <h1
                                class="text-2xl font-bold tracking-tight sm:text-3xl"
                            >
                                {{ props.project.name }}
                            </h1>
                            <p
                                class="text-sm text-muted-foreground sm:text-base"
                            >
                                Project created
                                {{ formatDate(props.project.created_at) }}
                                <span v-if="props.project.client" class="ml-2"
                                    >• {{ props.project.client.company }}</span
                                >
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
                        <!-- Add Task Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="
                                            createTask.url() +
                                            `?project_id=${props.project.id}`
                                        "
                                    >
                                        <Button
                                            variant="outline"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Add Task</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new task for this project</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Add Invoice Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="
                                            createInvoice.url() +
                                            `?project_id=${props.project.id}`
                                        "
                                    >
                                        <Button
                                            variant="outline"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Receipt class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Add Invoice</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new invoice for this project</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.project.id)">
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
                                    <p>Edit project information</p>
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
                                    <p>Delete this project</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Project Info & Tasks -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Project Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Briefcase class="h-5 w-5" />
                                Project Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Status & Dates -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Status</Label
                                    >
                                    <Badge
                                        :class="
                                            getStatusColor(props.project.status)
                                        "
                                        class="capitalize"
                                    >
                                        {{
                                            props.project.status.replace(
                                                '_',
                                                ' ',
                                            )
                                        }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Deadline</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.project.end_date) }}
                                        <span
                                            v-if="
                                                props.project.end_date &&
                                                getDaysRemaining(
                                                    props.project.end_date,
                                                ) !== null
                                            "
                                            :class="{
                                                'text-red-600':
                                                    getProgressStatus(
                                                        props.project.end_date,
                                                    ) === 'overdue',
                                                'text-orange-600':
                                                    getProgressStatus(
                                                        props.project.end_date,
                                                    ) === 'urgent',
                                                'text-yellow-600':
                                                    getProgressStatus(
                                                        props.project.end_date,
                                                    ) === 'warning',
                                                'text-muted-foreground':
                                                    getProgressStatus(
                                                        props.project.end_date,
                                                    ) === 'info',
                                            }"
                                            class="text-xs font-medium"
                                        >
                                            ({{
                                                getDaysRemaining(
                                                    props.project.end_date,
                                                )! < 0
                                                    ? 'Overdue'
                                                    : `${getDaysRemaining(props.project.end_date)} days left`
                                            }})
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Budget & Timeline -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Budget</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <DollarSign class="h-4 w-4" />
                                        {{
                                            formatCurrency(props.project.budget)
                                        }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Start Date</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Clock class="h-4 w-4" />
                                        {{
                                            formatDate(props.project.start_date)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Description</Label
                                >
                                <p class="text-sm whitespace-pre-wrap">
                                    {{
                                        props.project.description ||
                                        'No description provided'
                                    }}
                                </p>
                            </div>

                            <!-- Linked Client -->
                            <div v-if="props.project.client" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Client</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Building
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Link
                                        :href="`/clients/${props.project.client.id}`"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.project.client.name }} -
                                        {{ props.project.client.company }}
                                    </Link>
                                </div>
                            </div>

                            <!-- Project Members -->
                            <div class="space-y-3">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Team Members</Label
                                >
                                <div
                                    v-if="props.project.members.length > 0"
                                    class="flex flex-wrap gap-2"
                                >
                                    <div
                                        v-for="member in props.project.members"
                                        :key="member.id"
                                        class="flex items-center gap-2 rounded-lg border bg-muted/5 px-3 py-2"
                                    >
                                        <div
                                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <span
                                                class="text-xs font-medium text-primary"
                                            >
                                                {{
                                                    member.name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>
                                        <span class="text-sm">{{
                                            member.name
                                        }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    No team members assigned
                                </p>
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
                                        {{
                                            props.project.creator?.name || 'N/A'
                                        }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">
                                        {{
                                            formatDate(props.project.updated_at)
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tasks List -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CheckCircle class="h-5 w-5" />
                                Recent Tasks
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.tasks.length }}
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Latest tasks for this project
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.tasks.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="task in props.tasks"
                                    :key="task.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium">
                                                {{ task.title }}
                                            </h4>
                                            <div
                                                class="mt-1 flex items-center gap-2"
                                            >
                                                <Badge
                                                    :class="
                                                        getTaskStatusColor(
                                                            task.status,
                                                        )
                                                    "
                                                    class="text-xs capitalize"
                                                >
                                                    {{
                                                        task.status.replace(
                                                            '_',
                                                            ' ',
                                                        )
                                                    }}
                                                </Badge>
                                                <Badge
                                                    :class="
                                                        getPriorityColor(
                                                            task.priority,
                                                        )
                                                    "
                                                    class="text-xs capitalize"
                                                >
                                                    {{ task.priority }}
                                                </Badge>
                                                <span
                                                    v-if="task.due_date"
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Due
                                                    {{
                                                        formatDate(
                                                            task.due_date,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <p
                                                v-if="task.description"
                                                class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                            >
                                                {{ task.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <CheckCircle
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No tasks yet
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Create the first task for this project
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Invoices List -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Receipt class="h-5 w-5" />
                                Recent Invoices
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.invoices.length }}
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Latest invoices for this project
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.invoices.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="invoice in props.invoices"
                                    :key="invoice.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium">
                                                Invoice #{{
                                                    invoice.invoice_number
                                                }}
                                            </h4>
                                            <div
                                                class="mt-1 flex items-center gap-2"
                                            >
                                                <Badge
                                                    :class="
                                                        getInvoiceStatusColor(
                                                            invoice.status,
                                                        )
                                                    "
                                                    class="text-xs capitalize"
                                                >
                                                    {{ invoice.status }}
                                                </Badge>
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Due
                                                    {{
                                                        formatDate(
                                                            invoice.due_date,
                                                        )
                                                    }}
                                                </span>
                                                <!-- FIXED: Added null check for invoice.due_date -->
                                                <span
                                                    v-if="
                                                        invoice.due_date &&
                                                        getDaysRemaining(
                                                            invoice.due_date,
                                                        ) !== null &&
                                                        getDaysRemaining(
                                                            invoice.due_date,
                                                        )! < 0 &&
                                                        invoice.status !==
                                                            'paid'
                                                    "
                                                    class="text-xs font-medium text-red-600"
                                                >
                                                    (Overdue)
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium">
                                                {{
                                                    formatCurrency(
                                                        invoice.amount,
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    formatDate(
                                                        invoice.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <Receipt
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No invoices yet
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Create the first invoice for this project
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes Section -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
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
                                    Add the first note to track important
                                    project information
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

                    <!-- Project Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Project Statistics</CardTitle>
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
                                                    {{ props.tasks.length }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Tasks
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total tasks in this project</p>
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
                                                    {{ props.invoices.length }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Invoices
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>
                                                Total invoices for this project
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
                                                        props.project.members
                                                            .length
                                                    }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Members
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>
                                                Team members working on this
                                                project
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
                                                    {{ props.documents_count }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Documents
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>
                                                Total documents associated with
                                                this project
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Financial Summary -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Financial Summary
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Total Invoiced</span
                                        >
                                        <!-- FIXED: Now using the computed property -->
                                        <span class="text-sm font-medium">{{
                                            formatCurrency(totalInvoiceAmount)
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Project Budget</span
                                        >
                                        <span class="text-sm font-medium">{{
                                            formatCurrency(props.project.budget)
                                        }}</span>
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
                title="Delete Project"
                :description="`Are you sure you want to delete ${props.project.name}? This action cannot be undone and will also delete all associated tasks and invoices.`"
                confirm-text="Delete Project"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteProject"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

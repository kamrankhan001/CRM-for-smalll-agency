<script setup lang="ts">
import {
    convert,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/LeadController';
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
    ArrowLeft,
    Building,
    Calendar,
    CheckCircle,
    ClipboardList,
    Edit,
    FileText,
    Mail,
    Phone,
    Trash2,
    User,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
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
}

interface Task {
    id: number;
    title: string;
    status: string;
}

interface Document {
    id: number;
    name: string;
}

interface Lead {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    source: string | null;
    status: 'new' | 'contacted' | 'qualified' | 'lost';
    score: number | null;
    assigned_to: number | null;
    assignee?: User | null;
    creator?: User | null;
    created_at: string;
    updated_at: string;
    created_by: number;
    notes: Note[];
    activities: Activity[];
    tasks: Task[];
    documents: Document[];
}

interface Props {
    lead: Lead;
    notes: Note[];
    activities: Activity[];
    tasks: Task[];
    documents: Document[];
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leads', href: index.url() },
    { title: props.lead.name, href: '#' },
];

const showDeleteDialog = ref(false);
const showConvertDialog = ref(false);

// Check if lead is converted based on status
const isConverted = ref(props.lead.status === 'qualified');

const canDelete = computed(() => page.props.auth.user.role === 'admin');

const canEdit = (lead: Lead) => {
    const user = page.props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') return true;
    return lead.created_by === user.id || lead.assigned_to === user.id;
};

function getStatusColor(status: string) {
    const colors = {
        new: 'bg-blue-100 text-blue-800',
        contacted: 'bg-yellow-100 text-yellow-800',
        qualified: 'bg-green-100 text-green-800',
        lost: 'bg-red-100 text-red-800',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
}

function getScoreColor(score: number | null) {
    if (!score) return 'bg-gray-100 text-gray-800';
    if (score >= 80) return 'bg-green-100 text-green-800';
    if (score >= 60) return 'bg-blue-100 text-blue-800';
    if (score >= 40) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteLead() {
    router.delete(destroy.url(props.lead.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}

function confirmConvert() {
    showConvertDialog.value = true;
}

function convertToClient() {
    router.post(convert.url(props.lead.id));
    showConvertDialog.value = false;
}

function cancelConvert() {
    showConvertDialog.value = false;
}
</script>

<template>
    <Head :title="props.lead.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Back button and lead info -->
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
                                    <p>Back to leads</p>
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
                                    {{ props.lead.name }}
                                </h1>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :class="
                                            getStatusColor(props.lead.status)
                                        "
                                        class="capitalize"
                                    >
                                        {{ props.lead.status }}
                                    </Badge>
                                    <Badge
                                        v-if="props.lead.score"
                                        :class="getScoreColor(props.lead.score)"
                                    >
                                        Score: {{ props.lead.score }}
                                    </Badge>
                                </div>
                            </div>
                            <p
                                class="mt-1 text-sm text-muted-foreground sm:text-base"
                            >
                                {{
                                    props.lead.company || 'No company specified'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
                        <!-- Convert to Client Button -->
                        <TooltipProvider v-if="!isConverted">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        @click="confirmConvert"
                                        variant="outline"
                                        class="flex h-9 w-9 items-center gap-2 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Convert to Client</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Convert this lead to a client</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider v-if="canEdit(props.lead)">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.lead.id)">
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
                                    <p>Edit lead information</p>
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
                                    <p>Delete this lead</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Lead Info & Notes -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Lead Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Lead Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Contact Information -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Mail class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Email</Label
                                        >
                                    </div>
                                    <p class="text-sm">
                                        <a
                                            v-if="props.lead.email"
                                            :href="`mailto:${props.lead.email}`"
                                            class="text-primary hover:underline"
                                        >
                                            {{ props.lead.email }}
                                        </a>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >No email</span
                                        >
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Phone class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Phone</Label
                                        >
                                    </div>
                                    <p class="text-sm">
                                        <a
                                            v-if="props.lead.phone"
                                            :href="`tel:${props.lead.phone}`"
                                            class="text-primary hover:underline"
                                        >
                                            {{ props.lead.phone }}
                                        </a>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >No phone</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <!-- Company & Source -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Building class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Company</Label
                                        >
                                    </div>
                                    <p class="text-sm">
                                        {{ props.lead.company || 'No company' }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Source</Label
                                    >
                                    <p class="text-sm">
                                        {{
                                            props.lead.source ||
                                            'No source specified'
                                        }}
                                    </p>
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
                                        {{ props.lead.creator?.name || 'N/A' }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Assigned To</Label
                                    >
                                    <p class="text-sm">
                                        {{
                                            props.lead.assignee?.name ||
                                            'Unassigned'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Calendar class="h-4 w-4" />
                                        <Label class="text-sm font-medium"
                                            >Created</Label
                                        >
                                    </div>
                                    <p class="text-sm">
                                        {{ formatDate(props.lead.created_at) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">
                                        {{ formatDate(props.lead.updated_at) }}
                                    </p>
                                </div>
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
                        <CardContent class="space-y-6">
                            <!-- Notes List -->
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
                                    information
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Activity & Related Items -->
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

                    <!-- Related Items -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Related Items</CardTitle>
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
                                            <p>
                                                Total tasks associated with this
                                                lead
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
                                                    {{ props.documents.length }}
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
                                                this lead
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium"
                                        >Client Status</span
                                    >
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Badge
                                                    :class="
                                                        isConverted
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                    "
                                                >
                                                    {{
                                                        isConverted
                                                            ? 'Converted'
                                                            : 'Not Converted'
                                                    }}
                                                </Badge>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>
                                                    {{
                                                        isConverted
                                                            ? 'This lead has been converted to a client'
                                                            : 'This lead has not been converted to a client yet'
                                                    }}
                                                </p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Lead"
                :description="`Are you sure you want to delete ${props.lead.name}? This action cannot be undone.`"
                confirm-text="Delete Lead"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteLead"
                @cancel="cancelDelete"
            />

            <!-- Convert to Client Confirmation Dialog -->
            <ConfirmationDialog
                :show="showConvertDialog"
                title="Convert to Client"
                :description="`Are you sure you want to convert ${props.lead.name} to a client? This will create a new client record and move this lead to the clients section.`"
                confirm-text="Convert to Client"
                cancel-text="Cancel"
                variant="default"
                @confirm="convertToClient"
                @cancel="cancelConvert"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserController';
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
    ArrowLeft,
    Briefcase,
    Calendar,
    CheckCircle,
    Edit,
    FileText,
    Mail,
    Trash2,
    User as UserIcon,
    Users,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    updated_at: string;
}

interface Client {
    id: number;
    name: string;
    company: string;
    status: string;
    created_at: string;
}

interface Lead {
    id: number;
    name: string;
    company: string;
    status: string;
    created_at: string;
}

interface Task {
    id: number;
    title: string;
    status: string;
    priority: string;
    due_date: string | null;
    created_at: string;
}

interface Project {
    id: number;
    name: string;
    status: string;
    client_id: number | null;
    created_at: string;
}

interface Document {
    id: number;
    title: string;
    type: string;
    created_at: string;
}

interface Stats {
    assigned_clients_count: number;
    assigned_leads_count: number;
    tasks_count: number;
    projects_count: number;
    owned_projects_count: number;
    uploaded_documents_count: number;
}

interface Props {
    user: User;
    stats: Stats;
    assigned_clients: Client[];
    assigned_leads: Lead[];
    recent_tasks: Task[];
    recent_projects: Project[];
    owned_projects: Project[];
    recent_documents: Document[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index.url() },
    { title: props.user.name, href: '#' },
];

const showDeleteDialog = ref(false);

function getRoleColor(role: string) {
    const colors = {
        admin: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        manager: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        member: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return (
        colors[role as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function getStatusColor(status: string) {
    const colors = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        lead: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        vip: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        planning: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        on_hold: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    };
    return (
        colors[status as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function getTaskStatusColor(status: string) {
    const colors = {
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
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

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteUser() {
    router.delete(destroy.url(props.user.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="props.user.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="mb-4 flex items-center gap-4">
                    <Link :href="index.url()">
                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1
                            class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            {{ props.user.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            User since {{ formatDate(props.user.created_at) }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.user.id)">
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
                                    <p>Edit user information</p>
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
                                    <p>Delete this user</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - User Info & Assigned Items -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- User Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <UserIcon class="h-5 w-5" />
                                User Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Basic Information -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Role</Label
                                    >
                                    <Badge
                                        :class="getRoleColor(props.user.role)"
                                        class="capitalize"
                                    >
                                        {{ props.user.role }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Email</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Mail class="h-4 w-4" />
                                        {{ props.user.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Member Since</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.user.created_at) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">
                                        {{ formatDate(props.user.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Assigned Clients -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Assigned Clients
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.assigned_clients.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.assigned_clients.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="client in props.assigned_clients"
                                    :key="client.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <Link
                                                :href="`/clients/${client.id}`"
                                                class="text-sm font-medium text-primary hover:underline"
                                            >
                                                {{ client.name }}
                                            </Link>
                                            <p
                                                class="text-xs text-muted-foreground mt-1"
                                            >
                                                {{ client.company }}
                                            </p>
                                        </div>
                                        <Badge
                                            :class="getStatusColor(client.status)"
                                            class="capitalize text-xs"
                                        >
                                            {{ client.status }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <Users
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No assigned clients
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Tasks -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CheckCircle class="h-5 w-5" />
                                Recent Tasks
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.recent_tasks.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.recent_tasks.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="task in props.recent_tasks"
                                    :key="task.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <Link
                                                :href="`/tasks/${task.id}`"
                                                class="text-sm font-medium text-primary hover:underline"
                                            >
                                                {{ task.title }}
                                            </Link>
                                            <div class="flex items-center gap-2 mt-1">
                                                <Badge
                                                    :class="getTaskStatusColor(task.status)"
                                                    class="capitalize text-xs"
                                                >
                                                    {{ task.status.replace('_', ' ') }}
                                                </Badge>
                                                <Badge
                                                    :class="getPriorityColor(task.priority)"
                                                    class="capitalize text-xs"
                                                >
                                                    {{ task.priority }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <CheckCircle
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No assigned tasks
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Statistics & Recent Activity -->
                <div class="space-y-6">
                    <!-- User Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>User Statistics</CardTitle>
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
                                                    {{ props.stats.assigned_clients_count }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Clients
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total assigned clients</p>
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
                                                    {{ props.stats.tasks_count }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Tasks
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total assigned tasks</p>
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
                                                    {{ props.stats.projects_count }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Projects
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total project memberships</p>
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
                                                    {{ props.stats.uploaded_documents_count }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Documents
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total uploaded documents</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Projects -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Briefcase class="h-5 w-5" />
                                Project Memberships
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.recent_projects.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.recent_projects.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="project in props.recent_projects"
                                    :key="project.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <Link
                                                :href="`/projects/${project.id}`"
                                                class="text-sm font-medium text-primary hover:underline"
                                            >
                                                {{ project.name }}
                                            </Link>
                                            <Badge
                                                :class="getStatusColor(project.status)"
                                                class="mt-1 capitalize text-xs"
                                            >
                                                {{ project.status.replace('_', ' ') }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <Briefcase
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No project memberships
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Documents -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Recent Documents
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.recent_documents.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.recent_documents.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="document in props.recent_documents"
                                    :key="document.id"
                                    class="rounded-lg border p-3 transition-colors hover:bg-muted/5"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <Link
                                                :href="`/documents/${document.id}`"
                                                class="text-sm font-medium text-primary hover:underline"
                                            >
                                                {{ document.title }}
                                            </Link>
                                            <p
                                                class="text-xs text-muted-foreground mt-1 capitalize"
                                            >
                                                {{ document.type }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <FileText
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No uploaded documents
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete User"
                :description="`Are you sure you want to delete '${props.user.name}'? This action cannot be undone and all user data will be permanently removed.`"
                confirm-text="Delete User"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteUser"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
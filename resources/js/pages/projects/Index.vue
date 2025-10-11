<script setup lang="ts">
import ActionButtons from '@/components/ActionButtons.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Filter,
    Plus,
    Search,
    X,
    Calendar,
    Target,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    show,
    edit,
    index,
} from '@/actions/App/Http/Controllers/ProjectController';

interface User {
    id: number;
    name: string;
}

interface Client {
    id: number;
    name: string;
}

interface Lead {
    id: number;
    name: string;
}

interface Project {
    id: number;
    name: string;
    description: string | null;
    status: 'planning' | 'in_progress' | 'on_hold' | 'completed';
    start_date: string | null;
    end_date: string | null;
    client?: Client | null;
    lead?: Lead | null;
    creator?: User | null;
    members: User[];
    created_by: number;
    created_at: string;
    updated_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProjects {
    data: Project[];
    links: PaginationLinks[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}

const props = defineProps<{
    projects: PaginatedProjects;
    filters: {
        search?: string;
        status?: string;
        client_id?: string;
        lead_id?: string;
        created_by?: string;
        date_from?: string;
        date_to?: string;
    };
    clients: Client[];
    leads: Lead[];
    users: User[];
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Projects', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const projectToDelete = ref<number | null>(null);

// Permission checks
const canDelete = computed(() => ['admin', 'manager'].includes(props.auth.user.role));

const canEditProject = (project: Project) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') {
        // Managers can edit projects they created or are members of
        return project.created_by === user.id || 
               project.members.some(member => member.id === user.id);
    }
    // Members can only edit projects they are members of
    return project.members.some(member => member.id === user.id);
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    client_id: props.filters.client_id || null,
    lead_id: props.filters.lead_id || null,
    created_by: props.filters.created_by || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function goToEdit(projectId: number) {
    router.get(edit.url(projectId));
}

function goToView(projectId: number) {
    router.get(show.url(projectId));
}

function confirmDelete(projectId: number) {
    projectToDelete.value = projectId;
    showDeleteDialog.value = true;
}

function deleteProject() {
    if (projectToDelete.value) {
        router.delete(destroy.url(projectToDelete.value));
    }
    showDeleteDialog.value = false;
    projectToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    projectToDelete.value = null;
}

const projectBeingDeleted = computed(() =>
    props.projects.data.find((project) => project.id === projectToDelete.value),
);

// Status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'planning':
            return 'secondary';
        case 'in_progress':
            return 'default';
        case 'on_hold':
            return 'outline';
        case 'completed':
            return 'default';
        default:
            return 'secondary';
    }
};

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        status: filters.value.status === null ? '' : filters.value.status,
        client_id: filters.value.client_id === null ? '' : filters.value.client_id,
        lead_id: filters.value.lead_id === null ? '' : filters.value.lead_id,
        created_by: filters.value.created_by === null ? '' : filters.value.created_by,
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
        client_id: null,
        lead_id: null,
        created_by: null,
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
        filters.value.client_id ||
        filters.value.lead_id ||
        filters.value.created_by ||
        filters.value.date_from ||
        filters.value.date_to
    );
});

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.projects.links) return [];
    return props.projects.links.filter(
        (_, index) => index !== 0 && index !== props.projects.links.length - 1,
    );
});

const showingFrom = computed(() => props.projects.meta?.from || 0);
const showingTo = computed(() => props.projects.meta?.to || 0);
const total = computed(() => props.projects.meta?.total || 0);
</script>

<template>
    <Head title="Projects" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Projects
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage your team's projects and collaborations
                        </p>
                    </div>

                    <!-- All buttons container -->
                    <div class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3">
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
                                            {{ showFilters ? 'Hide' : 'Show' }}
                                            Filters
                                        </span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>{{ showFilters ? 'Hide' : 'Show' }} filters</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- New Project Button -->
                        <TooltipProvider v-if="['admin','manager'].includes(auth.user.role)">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="create.url()" class="shrink-0">
                                        <Button
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="hidden lg:inline">New Project</span>
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new project</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="filters.search"
                                type="text"
                                placeholder="Search by project name..."
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
                                <SelectItem :value="null">All Statuses</SelectItem>
                                <SelectItem value="planning">Planning</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="on_hold">On Hold</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Client Filter -->
                    <div class="space-y-2">
                        <Label for="client_id">Client</Label>
                        <Select v-model="filters.client_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All clients" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Clients</SelectItem>
                                <SelectItem
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id.toString()"
                                >
                                    {{ client.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Lead Filter -->
                    <div class="space-y-2">
                        <Label for="lead_id">Lead</Label>
                        <Select v-model="filters.lead_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All leads" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Leads</SelectItem>
                                <SelectItem
                                    v-for="lead in leads"
                                    :key="lead.id"
                                    :value="lead.id.toString()"
                                >
                                    {{ lead.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Created By Filter -->
                    <div class="space-y-2">
                        <Label for="created_by">Created By</Label>
                        <Select v-model="filters.created_by">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Users</SelectItem>
                                <SelectItem
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id.toString()"
                                >
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div class="space-y-2">
                        <Label for="date_from">Start Date From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">End Date To</Label>
                        <Input
                            id="date_to"
                            v-model="filters.date_to"
                            type="date"
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

            <!-- Table -->
            <div class="rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[250px]">Project</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Timeline</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Lead</TableHead>
                            <TableHead>Team Members</TableHead>
                            <TableHead>Created By</TableHead>
                            <TableHead class="w-[120px] text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="project in projects.data" :key="project.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <span class="text-sm font-medium text-primary">
                                            {{ project.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ project.name }}
                                        </div>
                                        <div v-if="project.description" class="max-w-[200px] truncate text-xs text-muted-foreground">
                                            {{ project.description }}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(project.status)" class="capitalize">
                                    {{ project.status.replace('_', ' ') }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3 text-muted-foreground" />
                                        <span class="font-medium">
                                            {{ project.start_date ? new Date(project.start_date).toLocaleDateString() : '-' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Target class="h-3 w-3 text-muted-foreground" />
                                        <span class="text-xs text-muted-foreground">
                                            {{ project.end_date ? new Date(project.end_date).toLocaleDateString() : '-' }}
                                        </span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div v-if="project.client" class="flex items-center gap-2">
                                    <span class="text-sm">{{ project.client.name }}</span>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell>
                                <div v-if="project.lead" class="flex items-center gap-2">
                                    <span class="text-sm">{{ project.lead.name }}</span>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell>
                                <div v-if="project.members.length > 0" class="flex -space-x-2">
                                    <div
                                        v-for="member in project.members.slice(0, 3)"
                                        :key="member.id"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-muted border-2 border-background text-xs font-medium"
                                        :title="member.name"
                                    >
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div
                                        v-if="project.members.length > 3"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-muted border-2 border-background text-xs font-medium"
                                    >
                                        +{{ project.members.length - 3 }}
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ project.creator?.name || '—' }}
                            </TableCell>
                            <TableCell>
                                <ActionButtons
                                    :show-edit="canEditProject(project)"
                                    :show-view="true"
                                    :show-delete="canDelete"
                                    :on-edit="() => goToEdit(project.id)"
                                    :on-view="() => goToView(project.id)"
                                    :on-delete="() => confirmDelete(project.id)"
                                    view-tooltip="View project"
                                    edit-tooltip="Edit project"
                                    delete-tooltip="Delete project"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="projects.data.length === 0">
                            <TableCell colspan="8" class="py-8 text-center text-muted-foreground">
                                No projects found matching your filters.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="border-t bg-muted/30 px-6 py-4">
                <div v-if="projects.meta?.last_page > 1" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="text-sm text-muted-foreground">
                        Showing <span class="font-medium">{{ showingFrom }}</span> to <span class="font-medium">{{ showingTo }}</span> of <span class="font-medium">{{ total }}</span> results
                    </div>
                    <TooltipProvider>
                        <nav class="flex items-center border rounded-md">
                            <!-- Prev Button -->
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="inline-block">
                                        <button
                                            class="px-3 py-2 text-sm text-muted-foreground hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                            :disabled="!projects.links[0].url"
                                            @click="goToPage(projects.links[0].url)"
                                        >
                                            <ChevronLeft class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent v-if="!projects.links[0].url">
                                    <p>You're on the first page</p>
                                </TooltipContent>
                            </Tooltip>

                            <!-- Page Numbers -->
                            <button
                                v-for="link in pageLinks"
                                :key="link.label"
                                class="border-l px-3 py-2 text-sm"
                                :class="[
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted',
                                ]"
                                @click="goToPage(link.url)"
                            >
                                {{ link.label }}
                            </button>

                            <!-- Next Button -->
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="inline-block">
                                        <button
                                            class="border-l px-3 py-2 text-sm text-muted-foreground hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                            :disabled="!projects.links[projects.links.length - 1].url"
                                            @click="goToPage(projects.links[projects.links.length - 1].url)"
                                        >
                                            <ChevronRight class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent v-if="!projects.links[projects.links.length - 1].url">
                                    <p>You're on the last page</p>
                                </TooltipContent>
                            </Tooltip>
                        </nav>
                    </TooltipProvider>
                </div>
                <div v-else class="text-center text-sm text-muted-foreground">
                    {{ total }} project{{ total !== 1 ? 's' : '' }} total
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Project"
                :description="`Are you sure you want to delete '${projectBeingDeleted?.name}'? This action cannot be undone.`"
                confirm-text="Delete Project"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteProject"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
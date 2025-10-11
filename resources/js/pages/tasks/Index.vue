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
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    show,
    edit,
    index,
} from '@/actions/App/Http/Controllers/TaskController';

interface User {
    id: number;
    name: string;
}

interface Taskable {
    id: number;
    name: string;
    type: string;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    due_date: string | null;
    assignee?: User | null;
    creator?: User | null;
    taskable?: Taskable | null;
    created_by?: number;
    assigned_to?: number;
    created_at: string;
    updated_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTasks {
    data: Task[];
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
    tasks: PaginatedTasks;
    filters: {
        search?: string;
        status?: string;
        assigned_to?: string;
        date_from?: string;
        date_to?: string;
    };
    users: User[];
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

console.log(props.tasks);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tasks', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const taskToDelete = ref<number | null>(null);

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');

const canEditTask = (task: Task) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') {
        // Managers can only edit their own or assigned tasks
        return task.created_by === user.id || task.assigned_to === user.id;
    }
    // Members can only edit their own or assigned tasks
    return task.created_by === user.id || task.assigned_to === user.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    assigned_to: props.filters.assigned_to || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function goToEdit(taskId: number) {
    router.get(edit.url(taskId));
}

function goToView(taskId: number) {
    router.get(show.url(taskId));
}

function confirmDelete(taskId: number) {
    taskToDelete.value = taskId;
    showDeleteDialog.value = true;
}

function deleteTask() {
    if (taskToDelete.value) {
        router.delete(destroy.url(taskToDelete.value));
    }
    showDeleteDialog.value = false;
    taskToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    taskToDelete.value = null;
}

const taskBeingDeleted = computed(() =>
    props.tasks.data.find((task) => task.id === taskToDelete.value),
);

// Status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'pending':
            return 'secondary';
        case 'in_progress':
            return 'default';
        case 'completed':
            return 'default';
        default:
            return 'secondary';
    }
};

// Get taskable type badge
const getTaskableTypeVariant = (type: string | undefined) => {
    switch (type?.toLowerCase()) {
        case 'lead':
            return 'default';
        case 'client':
            return 'secondary';
        case 'project':
            return 'default';
        default:
            return 'outline';
    }
};

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        status: filters.value.status === null ? '' : filters.value.status,
        assigned_to:
            filters.value.assigned_to === null ? '' : filters.value.assigned_to,
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
        assigned_to: null,
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
        filters.value.assigned_to ||
        filters.value.date_from ||
        filters.value.date_to
    );
});

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.tasks.links) return [];
    return props.tasks.links.filter(
        (_, index) => index !== 0 && index !== props.tasks.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.tasks.meta || props.tasks.data.length === 0) return 0;
    return (
        props.tasks.meta.from ||
        (props.tasks.meta.current_page - 1) * props.tasks.meta.per_page + 1
    );
});

const showingTo = computed(() => {
    if (!props.tasks.meta || props.tasks.data.length === 0) return 0;
    return (
        props.tasks.meta.to ||
        Math.min(
            props.tasks.meta.current_page * props.tasks.meta.per_page,
            props.tasks.meta.total,
        )
    );
});

const total = computed(() => props.tasks.meta?.total || 0);
</script>

<template>
    <Head title="Tasks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Tasks
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage your team's tasks and assignments
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

                        <!-- Add Task Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="create.url()" class="shrink-0">
                                        <Button
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="hidden lg:inline">Add Task</span>
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new task</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground"
                            />
                            <Input
                                id="search"
                                v-model="filters.search"
                                type="text"
                                placeholder="Search by task title..."
                                class="w-full pl-10"
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
                                <SelectItem value="in_progress"
                                    >In Progress</SelectItem
                                >
                                <SelectItem value="completed"
                                    >Completed</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Assigned To Filter -->
                    <div class="space-y-2">
                        <Label for="assigned_to">Assigned To</Label>
                        <Select v-model="filters.assigned_to">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Users</SelectItem>
                                <SelectItem value="unassigned"
                                    >Unassigned</SelectItem
                                >
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
                        <Label for="date_from">Due Date From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            class="w-full"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Due Date To</Label>
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

            <!-- Table -->
            <div class="rounded-lg border">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[250px]">Task</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead>Assigned To</TableHead>
                                <TableHead>Linked To</TableHead>
                                <TableHead>Last Updated</TableHead>
                                <TableHead class="w-[120px] text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="task in tasks.data" :key="task.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <span
                                                class="text-sm font-medium text-primary"
                                            >
                                                {{
                                                    task.title
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{ task.title }}
                                            </div>
                                            <div
                                                v-if="task.description"
                                                class="max-w-[200px] truncate text-xs text-muted-foreground"
                                            >
                                                {{ task.description }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="getStatusVariant(task.status)"
                                        class="capitalize"
                                    >
                                        {{ task.status.replace('_', ' ') }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium">
                                        {{ task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date' }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ task.assignee?.name ?? 'Unassigned' }}
                                </TableCell>
                                <TableCell>
                                    <div
                                        v-if="task.taskable"
                                        class="flex items-center gap-2"
                                    >
                                        <Badge
                                            :variant="
                                                getTaskableTypeVariant(
                                                    task.taskable.type,
                                                )
                                            "
                                            class="text-xs"
                                        >
                                            {{ task.taskable.type }}
                                        </Badge>
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >{{ task.taskable.name }}</span
                                        >
                                    </div>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium">
                                        {{ new Date(task.updated_at).toLocaleDateString() }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ new Date(task.updated_at).toLocaleTimeString() }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <ActionButtons
                                        :show-edit="canEditTask(task)"
                                        :show-view="true"
                                        :show-delete="canDelete"
                                        :on-edit="() => goToEdit(task.id)"
                                        :on-view="() => goToView(task.id)"
                                        :on-delete="
                                            () => confirmDelete(task.id)
                                        "
                                        edit-tooltip="Edit task"
                                        view-tooltip="View task"
                                        delete-tooltip="Delete task"
                                    />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="tasks.data.length === 0">
                                <TableCell
                                    colspan="7"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No tasks found matching your filters.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Footer (Outside Table) -->
                <div class="border-t bg-muted/30 px-6 py-4">
                    <div
                        v-if="tasks.meta?.last_page > 1"
                        class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                    >
                        <!-- Info -->
                        <div class="text-sm text-muted-foreground">
                            Showing
                            <span class="font-medium">{{ showingFrom }}</span>
                            to
                            <span class="font-medium">{{ showingTo }}</span>
                            of
                            <span class="font-medium">{{ total }}</span>
                            results
                        </div>

                        <!-- Pagination Controls -->
                        <TooltipProvider>
                            <nav class="flex items-center overflow-hidden rounded-md border">
                                <!-- Prev Button -->
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div class="inline-block">
                                            <button
                                                class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                                :disabled="!tasks.links[0].url"
                                                @click="goToPage(tasks.links[0].url)"
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!tasks.links[0].url">
                                        <p>You're on the first page</p>
                                    </TooltipContent>
                                </Tooltip>

                                <!-- Page Numbers -->
                                <button
                                    v-for="link in pageLinks"
                                    :key="link.label"
                                    class="border-l px-3 py-2 text-sm font-medium transition-colors"
                                    @click="goToPage(link.url)"
                                    :class="[
                                        link.active
                                            ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                            : 'text-muted-foreground hover:bg-muted',
                                    ]"
                                >
                                    {{ link.label }}
                                </button>

                                <!-- Next Button -->
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div class="inline-block">
                                            <button
                                                class="border-l px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                                :disabled="!tasks.links[tasks.links.length - 1].url"
                                                @click="goToPage(tasks.links[tasks.links.length - 1].url)"
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!tasks.links[tasks.links.length - 1].url">
                                        <p>You're on the last page</p>
                                    </TooltipContent>
                                </Tooltip>
                            </nav>
                        </TooltipProvider>
                    </div>
                    <div
                        v-else
                        class="text-center text-sm text-muted-foreground"
                    >
                        {{ total }} task{{ total !== 1 ? 's' : '' }} total
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Task"
                :description="`Are you sure you want to delete '${taskBeingDeleted?.title}'? This action cannot be undone.`"
                confirm-text="Delete Task"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteTask"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
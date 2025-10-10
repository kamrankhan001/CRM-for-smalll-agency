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
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Filter,
    Search,
    User,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    destroy,
    index,
    show,
} from '@/actions/App/Http/Controllers/ActivityController';

interface Causer {
    id: number;
    name: string;
    role: string;
}

interface Activity {
    id: number;
    description: string;
    causer: Causer | null;
    model_type: string;
    action: string;
    created_at: string;
    updated_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedActivities {
    data: Activity[];
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

interface Filters {
    user?: string;
    model?: string;
    action?: string;
    date?: string;
}

const props = defineProps<{
    activities: PaginatedActivities;
    filters: Filters;
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Activities', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const activityToDelete = ref<number | null>(null);

// Initialize filters with prop values
const filters = ref({
    user: props.filters?.user ?? '',
    model: props.filters?.model ?? null,
    action: props.filters?.action ?? null,
    date: props.filters?.date ?? '',
});

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');

const canViewActivity = (activity: Activity) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') {
        return activity.causer?.role !== 'admin';
    }
    return activity.causer?.id === user.id;
};

// Action variants for badges
const getActionVariant = (action: string) => {
    switch (action) {
        case 'created':
            return 'default';
        case 'updated':
            return 'secondary';
        case 'deleted':
            return 'destructive';
        case 'assigned':
            return 'outline';
        case 'commented':
            return 'outline';
        default:
            return 'outline';
    }
};

// Role variants for badges
const getRoleVariant = (role: string) => {
    switch (role) {
        case 'admin':
            return 'default';
        case 'manager':
            return 'secondary';
        case 'member':
            return 'outline';
        default:
            return 'outline';
    }
};

// Filter functions
function applyFilters() {
    const filterParams: Record<string, any> = {};

    if (filters.value.user) filterParams.user = filters.value.user;
    if (filters.value.model) filterParams.model = filters.value.model;
    if (filters.value.action) filterParams.action = filters.value.action;
    if (filters.value.date) filterParams.date = filters.value.date;

    router.get(index.url(), filterParams, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        user: '',
        model: null,
        action: null,
        date: '',
    };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        filters.value.user ||
        filters.value.model ||
        filters.value.action ||
        filters.value.date
    );
});

// Delete functions
function confirmDelete(activityId: number) {
    activityToDelete.value = activityId;
    showDeleteDialog.value = true;
}

function deleteActivity() {
    if (activityToDelete.value) {
        router.delete(destroy.url(activityToDelete.value));
    }
    showDeleteDialog.value = false;
    activityToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    activityToDelete.value = null;
}

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.activities.links) return [];
    return props.activities.links.filter(
        (_, index) =>
            index !== 0 && index !== props.activities.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.activities.meta || props.activities.data.length === 0) return 0;
    return props.activities.meta.from || 0;
});

const showingTo = computed(() => {
    if (!props.activities.meta || props.activities.data.length === 0) return 0;
    return props.activities.meta.to || 0;
});

const total = computed(() => props.activities.meta?.total || 0);
</script>

<template>
    <Head title="Activity Log" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="space-y-1">
                        <h1
                            class="text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            Activity Log
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Track all system activities and user actions
                        </p>
                    </div>

                    <!-- All buttons container -->
                    <div
                        class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
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
                                                showFilters ? 'Hide' : 'Show'
                                            }}
                                            Filters
                                        </span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>
                                        {{
                                            showFilters ? 'Hide' : 'Show'
                                        }}
                                        filters
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- User Filter -->
                    <div class="space-y-2">
                        <Label for="user">User</Label>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground"
                            />
                            <Input
                                id="user"
                                v-model="filters.user"
                                type="text"
                                placeholder="Filter by user name..."
                                class="w-full pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Model Filter -->
                    <div class="space-y-2">
                        <Label for="model">Model</Label>
                        <Select v-model="filters.model">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All models" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >All Models</SelectItem
                                >
                                <SelectItem value="App\Models\Lead"
                                    >Lead</SelectItem
                                >
                                <SelectItem value="App\Models\Client"
                                    >Client</SelectItem
                                >
                                <SelectItem value="App\Models\Task"
                                    >Task</SelectItem
                                >
                                <SelectItem value="App\Models\Note"
                                    >Note</SelectItem
                                >
                                <SelectItem value="App\Models\User"
                                    >User</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Action Filter -->
                    <div class="space-y-2">
                        <Label for="action">Action</Label>
                        <Select v-model="filters.action">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All actions" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null"
                                    >All Actions</SelectItem
                                >
                                <SelectItem value="created">Created</SelectItem>
                                <SelectItem value="updated">Updated</SelectItem>
                                <SelectItem value="deleted">Deleted</SelectItem>
                                <SelectItem value="assigned"
                                    >Assigned</SelectItem
                                >
                                <SelectItem value="commented"
                                    >Commented</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date Filter -->
                    <div class="space-y-2">
                        <Label for="date">Date</Label>
                        <Input
                            id="date"
                            v-model="filters.date"
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
                                <TableHead class="w-[120px]">Model</TableHead>
                                <TableHead class="w-[100px]">Action</TableHead>
                                <TableHead class="w-[180px]">User</TableHead>
                                <TableHead class="w-[150px]">Date</TableHead>
                                <TableHead class="w-[120px] text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="activity in activities.data"
                                :key="activity.id"
                            >
                                <TableCell class="font-medium">
                                    {{ activity.model_type }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            getActionVariant(activity.action)
                                        "
                                        class="capitalize"
                                    >
                                        {{ activity.action }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div
                                        v-if="activity.causer"
                                        class="flex flex-col gap-1"
                                    >
                                        <div class="flex items-center gap-2">
                                            <User
                                                class="h-3 w-3 text-muted-foreground"
                                            />
                                            <span class="font-medium">{{
                                                activity.causer.name
                                            }}</span>
                                        </div>
                                        <Badge
                                            :variant="
                                                getRoleVariant(
                                                    activity.causer.role,
                                                )
                                            "
                                            class="text-xs capitalize"
                                        >
                                            {{ activity.causer.role }}
                                        </Badge>
                                    </div>
                                    <span
                                        v-else
                                        class="text-sm text-muted-foreground"
                                        >System</span
                                    >
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium">
                                        {{
                                            new Date(
                                                activity.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                activity.created_at,
                                            ).toLocaleTimeString()
                                        }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-right">
                                    <ActionButtons
                                        :show-view="canViewActivity(activity)"
                                        :show-delete="canDelete"
                                        :on-view="
                                            () =>
                                                router.get(
                                                    show.url(activity.id),
                                                )
                                        "
                                        :on-delete="
                                            () => confirmDelete(activity.id)
                                        "
                                        view-tooltip="View activity details"
                                        delete-tooltip="Delete activity"
                                        view-icon="eye"
                                    />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="activities.data.length === 0">
                                <TableCell
                                    colspan="5"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No activities found matching your filters.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Footer -->
                <div class="border-t bg-muted/30 px-6 py-4">
                    <div
                        v-if="activities.meta?.last_page > 1"
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
                            <nav
                                class="flex items-center overflow-hidden rounded-md border"
                            >
                                <!-- Prev Button -->
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div class="inline-block">
                                            <button
                                                class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                                :disabled="
                                                    !activities.links[0]?.url
                                                "
                                                @click="
                                                    goToPage(
                                                        activities.links[0]
                                                            ?.url,
                                                    )
                                                "
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        v-if="!activities.links[0]?.url"
                                    >
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
                                                :disabled="
                                                    !activities.links[
                                                        activities.links
                                                            .length - 1
                                                    ]?.url
                                                "
                                                @click="
                                                    goToPage(
                                                        activities.links[
                                                            activities.links
                                                                .length - 1
                                                        ]?.url,
                                                    )
                                                "
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        v-if="
                                            !activities.links[
                                                activities.links.length - 1
                                            ]?.url
                                        "
                                    >
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
                        {{ total }} activity{{ total !== 1 ? 'ies' : '' }} total
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Activity"
                :description="`Are you sure you want to delete this activity record? This action cannot be undone.`"
                confirm-text="Delete Activity"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteActivity"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

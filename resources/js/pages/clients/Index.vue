<script setup lang="ts">
import ActionButtons from '@/components/ActionButtons.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
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
    edit,
    index,
} from '@/actions/App/Http/Controllers/ClientController';

interface User {
    id: number;
    name: string;
}

interface Lead {
    id: number;
    name: string;
}

interface Client {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    address: string | null;
    lead?: Lead | null;
    assignee?: User | null;
    creator?: User | null;
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

interface PaginatedClients {
    data: Client[];
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
    clients: PaginatedClients;
    filters: {
        search?: string;
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Clients', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const clientToDelete = ref<number | null>(null);

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');

const canEditClient = (client: Client) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') return true;
    // Members can only edit their own or assigned clients
    return client.created_by === user.id || client.assigned_to === user.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    assigned_to: props.filters.assigned_to || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function goToEdit(clientId: number) {
    router.get(edit.url(clientId));
}

function confirmDelete(clientId: number) {
    clientToDelete.value = clientId;
    showDeleteDialog.value = true;
}

function deleteClient() {
    if (clientToDelete.value) {
        router.delete(destroy.url(clientToDelete.value));
    }
    showDeleteDialog.value = false;
    clientToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    clientToDelete.value = null;
}

const clientBeingDeleted = computed(() =>
    props.clients.data.find((client) => client.id === clientToDelete.value),
);

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
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
    if (!props.clients.links) return [];
    return props.clients.links.filter(
        (_, index) => index !== 0 && index !== props.clients.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.clients.meta || props.clients.data.length === 0) return 0;
    return (
        props.clients.meta.from ||
        (props.clients.meta.current_page - 1) * props.clients.meta.per_page + 1
    );
});

const showingTo = computed(() => {
    if (!props.clients.meta || props.clients.data.length === 0) return 0;
    return (
        props.clients.meta.to ||
        Math.min(
            props.clients.meta.current_page * props.clients.meta.per_page,
            props.clients.meta.total,
        )
    );
});

const total = computed(() => props.clients.meta?.total || 0);
</script>

<template>
    <Head title="Clients" />
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
                            Clients
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage your active clients and customer relationships
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

                        <!-- Add Client Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link
                                        :href="create.url()"
                                        class="shrink-0"
                                    >
                                        <Button
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Add Client</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new client</p>
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
                                placeholder="Search by name, email, or company..."
                                class="w-full pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
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
                        <Label for="date_from">Created From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            class="w-full"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Created To</Label>
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
                                <TableHead class="w-[200px]">Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Company</TableHead>
                                <TableHead>Assigned To</TableHead>
                                <TableHead>Source Lead</TableHead>
                                <TableHead>Last Updated</TableHead>
                                <TableHead class="w-[120px] text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="client in clients.data"
                                :key="client.id"
                            >
                                <TableCell class="font-medium">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <span
                                                class="text-sm font-medium text-primary"
                                            >
                                                {{
                                                    client.name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{ client.name }}
                                            </div>
                                            <div
                                                v-if="client.address"
                                                class="max-w-[150px] truncate text-xs text-muted-foreground"
                                            >
                                                {{ client.address }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ client.email ?? '-' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ client.phone ?? '-' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ client.company ?? 'N/A' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ client.assignee?.name ?? 'Unassigned' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ client.lead?.name ?? 'Direct' }}
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium">
                                        {{ new Date(client.updated_at).toLocaleDateString() }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ new Date(client.updated_at).toLocaleTimeString() }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <ActionButtons
                                        :show-edit="canEditClient(client)"
                                        :show-delete="canDelete"
                                        :on-edit="() => goToEdit(client.id)"
                                        :on-delete="
                                            () => confirmDelete(client.id)
                                        "
                                        edit-tooltip="Edit client"
                                        delete-tooltip="Delete client"
                                    />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="clients.data.length === 0">
                                <TableCell
                                    colspan="8"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No clients found matching your filters.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Footer (Outside Table) -->
                <div class="border-t bg-muted/30 px-6 py-4">
                    <div
                        v-if="clients.meta?.last_page > 1"
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
                                                :disabled="!clients.links[0].url"
                                                @click="
                                                    goToPage(clients.links[0].url)
                                                "
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!clients.links[0].url">
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
                                                    !clients.links[clients.links.length - 1].url
                                                "
                                                @click="
                                                    goToPage(
                                                        clients.links[clients.links.length - 1].url,
                                                    )
                                                "
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        v-if="!clients.links[clients.links.length - 1].url"
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
                        {{ total }} client{{ total !== 1 ? 's' : '' }} total
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Client"
                :description="`Are you sure you want to delete ${clientBeingDeleted?.name}? This action cannot be undone.`"
                confirm-text="Delete Client"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteClient"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
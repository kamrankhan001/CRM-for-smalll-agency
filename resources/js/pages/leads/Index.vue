<script setup lang="ts">
import ActionButtons from '@/components/ActionButtons.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import LeadImportDialog from '@/components/LeadImportDialog.vue';
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
    Download,
    FileText,
    Filter,
    Plus,
    Search,
    Upload,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/LeadController';

interface User {
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

interface PaginatedLeads {
    data: Lead[];
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
    leads: PaginatedLeads;
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Leads', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const showImportDialog = ref(false);
const leadToDelete = ref<number | null>(null);
const importFile = ref<File | null>(null);
const isImporting = ref(false);

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    assigned_to: props.filters.assigned_to || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function goToEdit(leadId: number) {
    router.get(edit.url(leadId));
}
function goToShow(leadId: number) {
    router.get(index.url() + `/${leadId}`);
}

function confirmDelete(leadId: number) {
    leadToDelete.value = leadId;
    showDeleteDialog.value = true;
}

function deleteLead() {
    if (leadToDelete.value) {
        router.delete(destroy.url(leadToDelete.value));
    }
    showDeleteDialog.value = false;
    leadToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    leadToDelete.value = null;
}

const leadBeingDeleted = computed(() =>
    props.leads.data.find((lead) => lead.id === leadToDelete.value),
);

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');
const canImport = computed(
    () =>
        props.auth.user.role === 'admin' || props.auth.user.role === 'manager',
);

const canEditLead = (lead: Lead) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    if (user.role === 'manager') return true;
    // Members can only edit their own or assigned leads
    return lead.created_by === user.id || lead.assigned_to === user.id;
};

// Status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'new':
            return 'default';
        case 'contacted':
            return 'secondary';
        case 'qualified':
            return 'default';
        case 'lost':
            return 'destructive';
        default:
            return 'secondary';
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
    if (!props.leads.links) return [];
    return props.leads.links.filter(
        (_, index) => index !== 0 && index !== props.leads.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.leads.meta || props.leads.data.length === 0) return 0;
    return (
        props.leads.meta.from ||
        (props.leads.meta.current_page - 1) * props.leads.meta.per_page + 1
    );
});

const showingTo = computed(() => {
    if (!props.leads.meta || props.leads.data.length === 0) return 0;
    return (
        props.leads.meta.to ||
        Math.min(
            props.leads.meta.current_page * props.leads.meta.per_page,
            props.leads.meta.total,
        )
    );
});

const total = computed(() => props.leads.meta?.total || 0);

// Import/Export functions
function exportLeads() {
    const backendFilters = {
        ...filters.value,
        status: filters.value.status === null ? '' : filters.value.status,
        assigned_to:
            filters.value.assigned_to === null ? '' : filters.value.assigned_to,
    };

    const queryString = new URLSearchParams(backendFilters).toString();
    const url = `/leads/export${queryString ? `?${queryString}` : ''}`;

    window.location.href = url;
}

function downloadSample() {
    window.location.href = '/leads/import/sample';
}

function openImportDialog() {
    showImportDialog.value = true;
}

function closeImportDialog() {
    showImportDialog.value = false;
    importFile.value = null;
    isImporting.value = false;
}

async function submitImport(file: File) {
    isImporting.value = true;

    try {
        const formData = new FormData();
        formData.append('file', file);

        await router.post('/leads/import', formData, {
            onSuccess: () => {
                closeImportDialog();
                // The success message will be shown via Inertia flash messages
            },
            onError: (errors) => {
                console.error('Import failed:', errors);
            },
            onFinish: () => {
                isImporting.value = false;
            },
        });
    } catch (error) {
        console.error('Import error:', error);
        isImporting.value = false;
    }
}
</script>

<template>
    <Head title="Leads" />
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
                            Leads
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage your potential clients and leads pipeline
                        </p>
                    </div>

                    <!-- All buttons container -->
                    <div
                        class="flex w-full items-center justify-between gap-2 lg:w-auto lg:justify-normal lg:gap-3"
                    >
                        <!-- Left side buttons (Export, Import, Sample) -->
                        <div class="flex items-center gap-1 lg:gap-2">
                            <TooltipProvider>
                                <!-- Export Button -->
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="outline"
                                            @click="exportLeads"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Download class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Export</span
                                            >
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>Export leads to Excel</p>
                                    </TooltipContent>
                                </Tooltip>

                                <!-- Import Button -->
                                <Tooltip v-if="canImport">
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="outline"
                                            @click="openImportDialog"
                                            cclass="h-9 w-9 p-0 lg:h-auto lg:w-auto md:px-4 md:py-2"
                                            size="sm"
                                        >
                                            <Upload class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Import</span
                                            >
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>Import leads from Excel</p>
                                    </TooltipContent>
                                </Tooltip>

                                <!-- Sample Button -->
                                <Tooltip v-if="canImport">
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="outline"
                                            @click="downloadSample"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <FileText class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Sample</span
                                            >
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>Download import template</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>

                        <!-- Right side buttons (Filter, Add Lead) -->
                        <div class="flex items-center gap-1 lg:gap-2">
                            <TooltipProvider>
                                <!-- Filter Button -->
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

                                <!-- Add Lead Button -->
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
                                                    >Add Lead</span
                                                >
                                            </Button>
                                        </Link>
                                    </TooltipTrigger>
                                    <TooltipContent class="lg:hidden">
                                        <p>Create new lead</p>
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
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5"
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
                                <SelectItem value="new">New</SelectItem>
                                <SelectItem value="contacted"
                                    >Contacted</SelectItem
                                >
                                <SelectItem value="qualified"
                                    >Qualified</SelectItem
                                >
                                <SelectItem value="lost">Lost</SelectItem>
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

            <!-- Table Container -->
            <div class="rounded-lg border">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Company</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Assigned To</TableHead>
                                <TableHead>Last Updated</TableHead>
                                <TableHead class="w-[120px] text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="lead in leads.data" :key="lead.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <span
                                                class="text-sm font-medium text-primary"
                                            >
                                                {{
                                                    lead.name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{ lead.name }}
                                            </div>
                                            <div
                                                v-if="lead.source"
                                                class="text-xs text-muted-foreground"
                                            >
                                                via {{ lead.source }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ lead.email ?? '-' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ lead.phone ?? '-' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ lead.company ?? 'N/A' }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="getStatusVariant(lead.status)"
                                        class="capitalize"
                                    >
                                        {{ lead.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ lead.assignee?.name ?? 'Unassigned' }}
                                </TableCell>
                                <TableCell>
                                <p class="text-sm font-medium">
                                    {{ new Date(lead.updated_at).toLocaleDateString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ new Date(lead.updated_at).toLocaleTimeString() }}
                                </p>
                                </TableCell>
                                <TableCell>
                                    <ActionButtons
                                        :show-edit="canEditLead(lead)"
                                        :show-view="true"
                                        :show-delete="canDelete"
                                        :on-edit="() => goToEdit(lead.id)"
                                        :on-view="() => goToShow(lead.id)"
                                        :on-delete="
                                            () => confirmDelete(lead.id)
                                        "
                                        edit-tooltip="Edit lead"
                                        view-tooltip="View lead"
                                        delete-tooltip="Delete lead"
                                    />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="leads.data.length === 0">
                                <TableCell
                                    colspan="8"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No leads found matching your filters.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Footer (Outside Table) -->
                <div class="border-t bg-muted/30 px-6 py-4">
                    <div
                        v-if="leads.meta?.last_page > 1"
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
                                                :disabled="!leads.links[0].url"
                                                @click="
                                                    goToPage(leads.links[0].url)
                                                "
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!leads.links[0].url">
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
                                                    !leads.links[
                                                        leads.links.length - 1
                                                    ].url
                                                "
                                                @click="
                                                    goToPage(
                                                        leads.links[
                                                            leads.links.length -
                                                                1
                                                        ].url,
                                                    )
                                                "
                                            >
                                                <ChevronRight class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        v-if="
                                            !leads.links[leads.links.length - 1]
                                                .url
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
                        {{ total }} lead{{ total !== 1 ? 's' : '' }} total
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Lead"
                :description="`Are you sure you want to delete ${leadBeingDeleted?.name}? This action cannot be undone.`"
                confirm-text="Delete Lead"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteLead"
                @cancel="cancelDelete"
            />

            <!-- Import Dialog -->
            <LeadImportDialog
                :show="showImportDialog"
                :is-importing="isImporting"
                @update:show="closeImportDialog"
                @confirm="submitImport"
                @download-sample="downloadSample"
            />
        </div>
    </AppLayout>
</template>

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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building,
    Calendar,
    ChevronLeft,
    ChevronRight,
    DollarSign,
    Filter,
    Plus,
    Search,
    Target,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/InvoiceController';

interface User {
    id: number;
    name: string;
}

interface Client {
    id: number;
    name: string;
}

interface Project {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    title: string;
    amount: number;
    amount_paid: number;
    status: 'draft' | 'sent' | 'partially_paid' | 'paid' | 'cancelled';
    issue_date: string | null;
    due_date: string | null;
    paid_at: string | null;
    client?: Client | null;
    project?: Project | null;
    creator?: User | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedInvoices {
    data: Invoice[];
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
    invoices: PaginatedInvoices;
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Invoices', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const invoiceToDelete = ref<number | null>(null);

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');

const canEditInvoice = (invoice: Invoice) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    // Managers and members can edit invoices they created
    return invoice.creator?.id === user.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function goToEdit(invoiceId: number) {
    router.get(edit.url(invoiceId));
}

function confirmDelete(invoiceId: number) {
    invoiceToDelete.value = invoiceId;
    showDeleteDialog.value = true;
}

function deleteInvoice() {
    if (invoiceToDelete.value) {
        router.delete(destroy.url(invoiceToDelete.value));
    }
    showDeleteDialog.value = false;
    invoiceToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    invoiceToDelete.value = null;
}

const invoiceBeingDeleted = computed(() =>
    props.invoices.data.find((invoice) => invoice.id === invoiceToDelete.value),
);

// Status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'sent':
            return 'default';
        case 'partially_paid':
            return 'outline';
        case 'paid':
            return 'default';
        case 'cancelled':
            return 'destructive';
        default:
            return 'secondary';
    }
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Format date
const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Filter functions
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

// Pagination logic
// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.invoices.links || props.invoices.links.length === 0) return [];
    return props.invoices.links.filter(
        (_, index) => index !== 0 && index !== props.invoices.links.length - 1,
    );
});

// Safe access for pagination buttons
const firstPageUrl = computed(() => {
    return props.invoices.links && props.invoices.links.length > 0
        ? props.invoices.links[0].url
        : null;
});

const lastPageUrl = computed(() => {
    return props.invoices.links && props.invoices.links.length > 0
        ? props.invoices.links[props.invoices.links.length - 1].url
        : null;
});

// Fix the showing calculations
const showingFrom = computed(() => {
    if (!props.invoices.meta || props.invoices.data.length === 0) return 0;
    return (
        props.invoices.meta.from ||
        (props.invoices.meta.current_page - 1) * props.invoices.meta.per_page +
            1
    );
});

const showingTo = computed(() => {
    if (!props.invoices.meta || props.invoices.data.length === 0) return 0;
    return (
        props.invoices.meta.to ||
        Math.min(
            props.invoices.meta.current_page * props.invoices.meta.per_page,
            props.invoices.meta.total,
        )
    );
});

const total = computed(() => props.invoices.meta?.total || 0);
</script>

<template>
    <Head title="Invoices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Invoices</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage your billing and invoices
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        @click="showFilters = !showFilters"
                        class="flex items-center gap-2"
                    >
                        <Filter class="h-4 w-4" />
                        {{ showFilters ? 'Hide' : 'Show' }} Filters
                    </Button>
                    <Link :href="create.url()" class="shrink-0">
                        <Button class="flex items-center gap-2">
                            <Plus class="h-4 w-4" />
                            Create Invoice
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                                placeholder="Search by invoice title..."
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
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="sent">Sent</SelectItem>
                                <SelectItem value="partially_paid"
                                    >Partially Paid</SelectItem
                                >
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="cancelled"
                                    >Cancelled</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div class="space-y-2">
                        <Label for="date_from">Issue Date From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Issue Date To</Label>
                        <Input
                            id="date_to"
                            v-model="filters.date_to"
                            type="date"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="mt-4 flex justify-between">
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
                    <Button size="sm" @click="applyFilters">
                        Apply Filters
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[250px]">Invoice</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Dates</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Project</TableHead>
                            <TableHead class="w-[120px] text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                        >
                            <TableCell class="font-medium">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                    >
                                        <DollarSign
                                            class="h-4 w-4 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ invoice.title }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Issued
                                            {{ formatDate(invoice.issue_date) }}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="space-y-1">
                                    <div class="font-medium">
                                        {{ formatCurrency(invoice.amount) }}
                                    </div>
                                    <div
                                        v-if="invoice.amount_paid > 0"
                                        class="text-xs text-green-600"
                                    >
                                        Paid:
                                        {{
                                            formatCurrency(invoice.amount_paid)
                                        }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="getStatusVariant(invoice.status)"
                                    class="capitalize"
                                >
                                    {{ invoice.status.replace('_', ' ') }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        <span
                                            >Due:
                                            {{
                                                formatDate(invoice.due_date)
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="invoice.paid_at"
                                        class="flex items-center gap-1 text-green-600"
                                    >
                                        <Target class="h-3 w-3" />
                                        <span
                                            >Paid:
                                            {{
                                                formatDate(invoice.paid_at)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="invoice.client"
                                    class="flex items-center gap-2"
                                >
                                    <Building
                                        class="h-3 w-3 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        invoice.client.name
                                    }}</span>
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="invoice.project"
                                    class="flex items-center gap-2"
                                >
                                    <Target
                                        class="h-3 w-3 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        invoice.project.name
                                    }}</span>
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <ActionButtons
                                    :show-edit="canEditInvoice(invoice)"
                                    :show-delete="canDelete"
                                    :on-edit="() => goToEdit(invoice.id)"
                                    :on-delete="() => confirmDelete(invoice.id)"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-if="!invoices.data || invoices.data.length === 0"
                        >
                            <TableCell
                                colspan="7"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No invoices found matching your filters.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                class="flex flex-col items-center border-t bg-muted/30 px-6 py-4 sm:flex-row sm:justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    Showing <b>{{ showingFrom }}</b> to
                    <b>{{ showingTo }}</b> of <b>{{ total }}</b> results
                </div>
                <nav
                    v-if="invoices.meta?.last_page > 1"
                    class="flex items-center rounded-md border"
                >
                    <button
                        class="px-3 py-2 text-sm text-muted-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!firstPageUrl"
                        @click="goToPage(firstPageUrl)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-for="link in pageLinks"
                        :key="link.label"
                        class="border-l px-3 py-2 text-sm transition-colors"
                        :class="[
                            link.active
                                ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                : 'text-muted-foreground hover:bg-muted',
                        ]"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                    >
                        {{ link.label }}
                    </button>
                    <button
                        class="border-l px-3 py-2 text-sm text-muted-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!lastPageUrl"
                        @click="goToPage(lastPageUrl)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </nav>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Invoice"
                :description="`Are you sure you want to delete '${invoiceBeingDeleted?.title}'? This action cannot be undone.`"
                confirm-text="Delete Invoice"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteInvoice"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

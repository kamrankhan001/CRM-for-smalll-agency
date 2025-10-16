<script setup lang="ts">
import {
    create,
    destroy,
    show,
    edit,
    index,
} from '@/actions/App/Http/Controllers/DocumentController';
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    FileText,
    Filter,
    Plus,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface User {
    id: number;
    name: string;
}

interface Documentable {
    id: number;
    name: string;
    type: string;
}

interface Document {
    id: number;
    title: string;
    type: string;
    file_path: string;
    documentable?: Documentable | null;
    uploader?: User | null;
    uploaded_by: number;
    created_at: string;
    updated_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDocuments {
    data: Document[];
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
    documents: PaginatedDocuments;
    filters: {
        search?: string;
        type?: string;
        uploaded_by?: string;
        documentable_type?: string;
    };
    users: User[];
    types: string[];
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const page = usePage()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Documents', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const documentToDelete = ref<number | null>(null);

// Permissions
const canDeleteDocument = (document: Document) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    return user.role === 'admin' || document.uploaded_by === user.id;
};

const canEditDocument = (document: Document) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    if (user.role === 'admin') return true;
    return document.uploaded_by === user.id;
};

const canViewDocument = (document: Document) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    // Admin and manager can always view
    if (['admin', 'manager'].includes(user.role)) {
        return true;
    }

    console.log('view baby', document.uploaded_by === user.id)
    console.log('view baby', document.uploaded_by, user.id)
    
    // Member can view only if they uploaded it
    return document.uploaded_by === user.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    type: props.filters.type || null,
    uploaded_by: props.filters.uploaded_by || null,
    documentable_type: props.filters.documentable_type || null,
});

function applyFilters() {
    const backendFilters = {
        search: filters.value.search || '',
        type: filters.value.type || '',
        uploaded_by: filters.value.uploaded_by || '',
        documentable_type: filters.value.documentable_type || '',
    };
    router.get(index.url(), backendFilters, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        search: '',
        type: null,
        uploaded_by: null,
        documentable_type: null,
    };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.type ||
        filters.value.uploaded_by ||
        filters.value.documentable_type
    );
});

function goToEdit(id: number) {
    router.get(edit.url(id));
}

function goToView(id: number) {
    router.get(show.url(id));
}

function confirmDelete(id: number) {
    documentToDelete.value = id;
    showDeleteDialog.value = true;
}

function deleteDocument() {
    if (documentToDelete.value) {
        router.delete(destroy.url(documentToDelete.value));
    }
    showDeleteDialog.value = false;
    documentToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    documentToDelete.value = null;
}

const documentBeingDeleted = computed(() =>
    props.documents.data.find((d) => d.id === documentToDelete.value),
);

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.documents.links) return [];
    return props.documents.links.filter(
        (_, i) => i !== 0 && i !== props.documents.links.length - 1,
    );
});

const showingFrom = computed(() => props.documents.meta.from || 0);
const showingTo = computed(() => props.documents.meta.to || 0);
const total = computed(() => props.documents.meta.total || 0);
</script>

<template>
    <Head title="Documents" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Documents
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage uploaded documents
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

                        <!-- Upload Document Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="create.url()" class="shrink-0">
                                        <Button
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="hidden lg:inline">Upload Document</span>
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Upload new document</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
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
                                placeholder="Search by document title..."
                                class="pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="space-y-2">
                        <Label for="type">Type</Label>
                        <Select v-model="filters.type">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All</SelectItem>
                                <SelectItem
                                    v-for="type in props.types"
                                    :key="type"
                                    :value="type"
                                >
                                    {{
                                        type.charAt(0).toUpperCase() +
                                        type.slice(1)
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Linked To -->
                    <div class="space-y-2">
                        <Label for="documentable_type">Linked To</Label>
                        <Select v-model="filters.documentable_type">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All entities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All</SelectItem>
                                <SelectItem value="lead">Lead</SelectItem>
                                <SelectItem value="client">Client</SelectItem>
                                <SelectItem value="project">Project</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Uploaded By -->
                    <div class="space-y-2">
                        <Label for="uploaded_by">Uploaded By</Label>
                        <Select v-model="filters.uploaded_by">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All</SelectItem>
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
                </div>

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
                    <Button size="sm" @click="applyFilters"
                        >Apply Filters</Button
                    >
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[200px]">Title</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Linked To</TableHead>
                            <TableHead>Uploaded By</TableHead>
                            <TableHead>Uploaded On</TableHead>
                            <TableHead class="w-[120px] text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="document in documents.data"
                            :key="document.id"
                        >
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-primary" />
                                    <span class="font-medium">{{
                                        document.title
                                    }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" class="capitalize">{{
                                    document.type
                                }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="document.documentable"
                                    class="flex items-center gap-2"
                                >
                                    <Badge
                                        variant="secondary"
                                        class="text-xs capitalize"
                                    >
                                        {{ document.documentable.type }}
                                    </Badge>
                                    <span
                                        class="text-sm text-muted-foreground"
                                        >{{ document.documentable.name }}</span
                                    >
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                {{ document.uploader?.name ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <p class="text-sm font-medium">
                                    {{ new Date(document.created_at).toLocaleDateString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ new Date(document.created_at).toLocaleTimeString() }}
                                </p>
                            </TableCell>
                            <TableCell>
                                <ActionButtons
                                    :show-edit="canEditDocument(document)"
                                    :show-view="canViewDocument(document)"
                                    :show-delete="canDeleteDocument(document)"
                                    :on-edit="() => goToEdit(document.id)"
                                    :on-view="() => goToView(document.id)"
                                    :on-delete="
                                        () => confirmDelete(document.id)
                                    "
                                    view-tooltip="View document"
                                    edit-tooltip="Edit document"
                                    delete-tooltip="Delete document"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="documents.data.length === 0">
                            <TableCell
                                colspan="6"
                                class="py-6 text-center text-muted-foreground"
                            >
                                No documents found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="border-t bg-muted/30 px-6 py-4">
                <div v-if="props.documents.meta.last_page > 1" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="text-sm text-muted-foreground">
                        Showing <span class="font-medium">{{ showingFrom }}</span> to
                        <span class="font-medium">{{ showingTo }}</span> of <span class="font-medium">{{ total }}</span> results
                    </div>
                    <TooltipProvider>
                        <nav class="flex items-center rounded-md border">
                            <!-- Prev Button -->
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="inline-block">
                                        <button
                                            class="px-3 py-2 text-sm text-muted-foreground hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                            :disabled="!props.documents.links[0].url"
                                            @click="goToPage(props.documents.links[0].url)"
                                        >
                                            <ChevronLeft class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent v-if="!props.documents.links[0].url">
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
                                            :disabled="!props.documents.links[props.documents.links.length - 1].url"
                                            @click="goToPage(props.documents.links[props.documents.links.length - 1].url)"
                                        >
                                            <ChevronRight class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent v-if="!props.documents.links[props.documents.links.length - 1].url">
                                    <p>You're on the last page</p>
                                </TooltipContent>
                            </Tooltip>
                        </nav>
                    </TooltipProvider>
                </div>
                <div v-else class="text-center text-sm text-muted-foreground">
                    {{ total }} document{{ total !== 1 ? 's' : '' }} total
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Document"
                :description="`Are you sure you want to delete '${documentBeingDeleted?.title}'? This action cannot be undone.`"
                confirm-text="Delete"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteDocument"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
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
import { computed, ref } from 'vue';
import { ChevronLeft, ChevronRight, Filter, Plus, Search, X, FileText } from 'lucide-vue-next';
import { create, destroy, edit, index } from '@/actions/App/Http/Controllers/DocumentController';

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
    };
    users: User[]; // This should now be available from the controller
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Documents', href: '#' }];

const showFilters = ref(false);
const showDeleteDialog = ref(false);
const documentToDelete = ref<number | null>(null);

// Permissions
const canDelete = computed(() => props.auth.user.role === 'admin');

const canEditDocument = (document: Document) => {
    const user = props.auth.user;
    if (user.role === 'admin') return true;
    return document.uploaded_by === user.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    type: props.filters.type || null,
    uploaded_by: props.filters.uploaded_by || null,
});

function applyFilters() {
    const backendFilters = {
        search: filters.value.search || '',
        type: filters.value.type || '',
        uploaded_by: filters.value.uploaded_by || '',
    };
    router.get(index.url(), backendFilters, { preserveState: true, replace: true });
}

function resetFilters() {
    filters.value = { search: '', type: null, uploaded_by: null };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.type || filters.value.uploaded_by;
});

function goToEdit(id: number) {
    router.get(edit.url(id));
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
    return props.documents.links.filter((_, i) => i !== 0 && i !== props.documents.links.length - 1);
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
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Documents</h1>
                    <p class="mt-1 text-muted-foreground">Manage uploaded documents</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="showFilters = !showFilters" class="flex items-center gap-2">
                        <Filter class="h-4 w-4" />
                        {{ showFilters ? 'Hide' : 'Show' }} Filters
                    </Button>
                    <Link :href="create.url()" class="shrink-0">
                        <Button class="flex items-center gap-2">
                            <Plus class="h-4 w-4" />
                            Upload Document
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
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
                                <SelectValue placeholder="All types" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All</SelectItem>
                                <SelectItem value="proposal">Proposal</SelectItem>
                                <SelectItem value="contract">Contract</SelectItem>
                                <SelectItem value="invoice">Invoice</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Uploaded By -->
                    <div class="space-y-2">
                        <Label for="uploaded_by">Uploaded By</Label>
                        <Select v-model="filters.uploaded_by">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All users" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All</SelectItem>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="mt-4 flex justify-between">
                    <Button variant="outline" size="sm" @click="resetFilters" :disabled="!hasActiveFilters">
                        <X class="h-4 w-4" /> Clear Filters
                    </Button>
                    <Button size="sm" @click="applyFilters">Apply Filters</Button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-lg border overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[200px]">Title</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Linked To</TableHead>
                            <TableHead>Uploaded By</TableHead>
                            <TableHead>Uploaded On</TableHead>
                            <TableHead class="w-[120px] text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="document in documents.data" :key="document.id">
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-primary" />
                                    <span class="font-medium">{{ document.title }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" class="capitalize">{{ document.type }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div v-if="document.documentable" class="flex items-center gap-2">
                                    <Badge variant="secondary" class="text-xs capitalize">
                                        {{ document.documentable.type }}
                                    </Badge>
                                    <span class="text-sm text-muted-foreground">{{ document.documentable.name }}</span>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell>
                                <!-- FIX: Use document.uploader.name instead of document.uploader?.name -->
                                {{ document.uploader?.name ?? '—' }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ new Date(document.created_at).toLocaleDateString() }}
                            </TableCell>
                            <TableCell>
                                <ActionButtons
                                    :show-edit="canEditDocument(document)"
                                    :show-delete="canDelete"
                                    :on-edit="() => goToEdit(document.id)"
                                    :on-delete="() => confirmDelete(document.id)"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="documents.data.length === 0">
                            <TableCell colspan="6" class="py-6 text-center text-muted-foreground">
                                No documents found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="border-t bg-muted/30 px-6 py-4 flex flex-col items-center sm:flex-row sm:justify-between">
                <div class="text-sm text-muted-foreground">
                    <!-- FIX: This will now show correct numbers like "1 to 5 of 10" -->
                    Showing <b>{{ showingFrom }}</b> to <b>{{ showingTo }}</b> of <b>{{ total }}</b> results
                </div>
                <nav v-if="props.documents.meta.last_page > 1" class="flex items-center border rounded-md">
                    <button
                        class="px-3 py-2 text-sm text-muted-foreground hover:bg-muted"
                        :disabled="!props.documents.links[0].url"
                        @click="goToPage(props.documents.links[0].url)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
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
                    <button
                        class="border-l px-3 py-2 text-sm text-muted-foreground hover:bg-muted"
                        :disabled="!props.documents.links[props.documents.links.length - 1].url"
                        @click="goToPage(props.documents.links[props.documents.links.length - 1].url)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </nav>
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
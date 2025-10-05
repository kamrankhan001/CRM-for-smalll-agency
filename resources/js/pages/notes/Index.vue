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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Filter,
    MessageSquare,
    Plus,
    Search,
    User,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/NoteController';

interface User {
    id: number;
    name: string;
}

interface Noteable {
    id: number;
    name: string;
    type: string;
}

interface Note {
    id: number;
    content: string;
    user: User;
    user_id?: number;
    noteable?: Noteable | null;
    created_at: string;
    updated_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedNotes {
    data: Note[];
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
    notes: PaginatedNotes;
    filters: {
        search?: string;
        noteable_type?: string;
        user_id?: string;
        date_range?: string;
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notes', href: '#' }];

const showDeleteDialog = ref(false);
const noteToDelete = ref<number | null>(null);
const showFilters = ref(false);

// Filters
const filters = ref({
    search: props.filters.search || '',
    noteable_type: props.filters.noteable_type || null,
    user_id: props.filters.user_id || null,
    date_range: props.filters.date_range || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

// Permission checks
const canEditNote = (note: Note) => {
    const user = props.auth.user;
    return user.role === 'admin' || note.user.id === user.id;
};

const canDeleteNote = (note: Note) => {
    const user = props.auth.user;
    return user.role === 'admin' || note.user.id === user.id;
};

function goToEdit(noteId: number) {
    router.get(edit.url(noteId));
}

function confirmDelete(noteId: number) {
    noteToDelete.value = noteId;
    showDeleteDialog.value = true;
}

function deleteNote() {
    if (noteToDelete.value) {
        router.delete(destroy.url(noteToDelete.value));
    }
    showDeleteDialog.value = false;
    noteToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    noteToDelete.value = null;
}

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        noteable_type: filters.value.noteable_type === null ? '' : filters.value.noteable_type,
        user_id: filters.value.user_id === null ? '' : filters.value.user_id,
        date_range: filters.value.date_range === null ? '' : filters.value.date_range,
    };

    router.get(index.url(), backendFilters, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        search: '',
        noteable_type: null,
        user_id: null,
        date_range: null,
        date_from: '',
        date_to: '',
    };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.noteable_type ||
        filters.value.user_id ||
        filters.value.date_range ||
        filters.value.date_from ||
        filters.value.date_to
    );
});

// Format date to relative time
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - date.getTime());
    const diffMinutes = Math.floor(diffTime / (1000 * 60));
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffMinutes < 1) {
        return 'Just now';
    } else if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
    } else if (diffHours < 24) {
        return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    } else if (diffDays < 7) {
        return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }
};

// Get noteable type badge variant
const getNoteableTypeVariant = (type: string | undefined) => {
    switch (type?.toLowerCase()) {
        case 'lead':
            return 'default';
        case 'client':
            return 'secondary';
        default:
            return 'outline';
    }
};

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.notes.links) return [];
    return props.notes.links.filter(
        (_, index) => index !== 0 && index !== props.notes.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.notes.meta || props.notes.data.length === 0) return 0;
    return (
        props.notes.meta.from ||
        (props.notes.meta.current_page - 1) * props.notes.meta.per_page + 1
    );
});

const showingTo = computed(() => {
    if (!props.notes.meta || props.notes.data.length === 0) return 0;
    return (
        props.notes.meta.to ||
        Math.min(
            props.notes.meta.current_page * props.notes.meta.per_page,
            props.notes.meta.total,
        )
    );
});

const total = computed(() => props.notes.meta?.total || 0);
</script>

<template>
    <Head title="Notes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Notes</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage and track notes for leads and clients
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
                            Add Note
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search Content</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="filters.search"
                                type="text"
                                placeholder="Search note content..."
                                class="w-full pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Noteable Type Filter -->
                    <div class="space-y-2">
                        <Label for="noteable_type">Linked To</Label>
                        <Select v-model="filters.noteable_type">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Types</SelectItem>
                                <SelectItem value="lead">Lead Notes</SelectItem>
                                <SelectItem value="client">Client Notes</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- User Filter -->
                    <div class="space-y-2">
                        <Label for="user_id">Created By</Label>
                        <Select v-model="filters.user_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Users</SelectItem>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="space-y-2">
                        <Label for="date_range">Date Range</Label>
                        <Select v-model="filters.date_range">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All dates" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Dates</SelectItem>
                                <SelectItem value="7_days">Last 7 Days</SelectItem>
                                <SelectItem value="15_days">Last 15 Days</SelectItem>
                                <SelectItem value="30_days">Last 30 Days</SelectItem>
                                <SelectItem value="custom">Custom Range</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Custom Date Range -->
                <div v-if="filters.date_range === 'custom'" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="date_from">From Date</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            class="w-full"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="date_to">To Date</Label>
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-3">
                    <!-- Notes List -->
                    <div class="space-y-4">
                        <Card
                            v-for="note in notes.data"
                            :key="note.id"
                            class="group transition-shadow hover:shadow-sm"
                        >
                            <CardContent class="p-6">
                                <!-- Note Header -->
                                <div
                                    class="mb-4 flex items-start justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <User
                                                class="h-4 w-4 text-primary"
                                            />
                                        </div>
                                        <div>
                                            <div class="font-medium">
                                                {{ note.user.name }}
                                            </div>
                                            <div
                                                class="mt-1 flex items-center gap-1 text-sm text-muted-foreground"
                                            >
                                                <Calendar class="h-3 w-3" />
                                                {{
                                                    formatDate(note.created_at)
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-if="note.noteable"
                                        class="flex items-center gap-2"
                                    >
                                        <Badge
                                            :variant="
                                                getNoteableTypeVariant(
                                                    note.noteable.type,
                                                )
                                            "
                                            class="text-xs"
                                        >
                                            {{ note.noteable.type }}
                                        </Badge>
                                        <span
                                            class="hidden text-sm text-muted-foreground sm:inline"
                                            >{{ note.noteable.name }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Note Content -->
                                <div
                                    class="mb-4 leading-relaxed whitespace-pre-wrap"
                                >
                                    {{ note.content }}
                                </div>

                                <!-- Note Actions -->
                                <div class="flex justify-end">
                                    <ActionButtons
                                        :show-edit="canEditNote(note)"
                                        :show-delete="canDeleteNote(note)"
                                        :on-edit="() => goToEdit(note.id)"
                                        :on-delete="
                                            () => confirmDelete(note.id)
                                        "
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Empty State -->
                        <Card
                            v-if="notes.data.length === 0"
                            class="py-12 text-center"
                        >
                            <CardContent>
                                <MessageSquare
                                    class="mx-auto mb-4 h-16 w-16 text-muted-foreground opacity-50"
                                />
                                <h3 class="mb-2 text-lg font-medium">
                                    No notes found
                                </h3>
                                <p class="mb-4 text-muted-foreground">
                                    {{
                                        hasActiveFilters
                                            ? 'No notes match your filters.'
                                            : 'Create your first note to get started.'
                                    }}
                                </p>
                                <Link :href="create.url()">
                                    <Button
                                        class="mx-auto flex items-center gap-2"
                                    >
                                        <Plus class="h-4 w-4" />
                                        Add Your First Note
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Pagination -->
                    <Card v-if="notes.meta?.last_page > 1">
                        <CardContent class="p-4">
                            <div
                                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                            >
                                <!-- Info -->
                                <div class="text-sm text-muted-foreground">
                                    Showing
                                    <span class="font-medium">{{
                                        showingFrom
                                    }}</span>
                                    to
                                    <span class="font-medium">{{
                                        showingTo
                                    }}</span>
                                    of
                                    <span class="font-medium">{{ total }}</span>
                                    results
                                </div>

                                <!-- Pagination Controls -->
                                <nav
                                    class="flex items-center overflow-hidden rounded-md border"
                                >
                                    <!-- Prev -->
                                    <button
                                        class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                        :disabled="!notes.links[0].url"
                                        @click="goToPage(notes.links[0].url)"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </button>

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

                                    <!-- Next -->
                                    <button
                                        class="border-l px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                        :disabled="
                                            !notes.links[notes.links.length - 1]
                                                .url
                                        "
                                        @click="
                                            goToPage(
                                                notes.links[
                                                    notes.links.length - 1
                                                ].url,
                                            )
                                        "
                                    >
                                        <ChevronRight class="h-4 w-4" />
                                    </button>
                                </nav>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- About Notes Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">About Notes</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div
                                class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-primary"
                                    ></div>
                                    <span class="font-medium text-primary"
                                        >Lead Notes</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Track interactions, follow-ups, and
                                    qualification progress for potential
                                    clients.
                                </p>
                            </div>

                            <div
                                class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-secondary"
                                    ></div>
                                    <span class="font-medium text-secondary"
                                        >Client Notes</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Document support interactions, feature
                                    requests, and relationship management
                                    details.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Note"
                :description="`Are you sure you want to delete this note? This action cannot be undone.`"
                confirm-text="Delete Note"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteNote"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
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
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserController';

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            created_at: string;
        }>;
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
        role?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '#' }];

const showDeleteDialog = ref(false);
const userToDelete = ref<number | null>(null);

// Filters
const filters = ref({
    search: props.filters.search || '',
    role: props.filters.role || null, // Use null for "All Roles"
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const getBadgeVariant = (role: string) => {
    switch (role) {
        case 'admin':
            return 'destructive';
        case 'manager':
            return 'default';
        case 'member':
            return 'secondary';
        default:
            return 'secondary';
    }
};

function goToEdit(userId: number) {
    router.get(edit.url(userId));
}

function confirmDelete(userId: number) {
    userToDelete.value = userId;
    showDeleteDialog.value = true;
}

function deleteUser() {
    if (userToDelete.value) {
        router.delete(destroy.url(userToDelete.value));
    }
    showDeleteDialog.value = false;
    userToDelete.value = null;
}

function cancelDelete() {
    showDeleteDialog.value = false;
    userToDelete.value = null;
}

const userBeingDeleted = computed(() =>
    props.users.data.find((user) => user.id === userToDelete.value),
);

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        role: filters.value.role === null ? '' : filters.value.role,
    };

    router.get(index.url(), backendFilters, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.value = {
        search: '',
        role: null, // Reset to null
        date_from: '',
        date_to: '',
    };
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.role ||
        filters.value.date_from ||
        filters.value.date_to
    );
});

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url);
}

const pageLinks = computed(() => {
    if (!props.users.links) return [];
    return props.users.links.filter(
        (_, index) => index !== 0 && index !== props.users.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.users.meta || props.users.data.length === 0) return 0;
    return props.users.meta.from || (props.users.meta.current_page - 1) * props.users.meta.per_page + 1;
});

const showingTo = computed(() => {
    if (!props.users.meta || props.users.data.length === 0) return 0;
    return props.users.meta.to || Math.min(props.users.meta.current_page * props.users.meta.per_page, props.users.meta.total);
});

const total = computed(() => props.users.meta?.total || 0);
</script>

<template>
    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Users</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage your system users and permissions
                    </p>
                </div>
                <Link :href="create.url()">
                    <Button class="flex items-center gap-2">
                        <Plus class="h-4 w-4" />
                        New User
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-lg border p-4">
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
                                placeholder="Search by name or email..."
                                class="pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <Select v-model="filters.role">
                            <SelectTrigger>
                                <SelectValue placeholder="All roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Roles</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                                <SelectItem value="manager">Manager</SelectItem>
                                <SelectItem value="member">Member</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div class="space-y-2">
                        <Label for="date_from">Joined From</Label>
                        <Input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Joined To</Label>
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
            <div class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[200px]">Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Joined Date</TableHead>
                            <TableHead class="w-[120px] text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                    >
                                        <span
                                            class="text-sm font-medium text-primary"
                                        >
                                            {{
                                                user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <span>{{ user.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{
                                user.email
                            }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="getBadgeVariant(user.role)"
                                    class="capitalize"
                                >
                                    {{ user.role }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ user.created_at }}
                            </TableCell>
                            <TableCell>
                                <ActionButtons
                                    :show-edit="true"
                                    :show-delete="true"
                                    :on-edit="() => goToEdit(user.id)"
                                    :on-delete="() => confirmDelete(user.id)"
                                />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No users found matching your filters.
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <!-- Table Footer with Pagination -->
                    <TableFooter>
                        <TableRow>
                            <TableCell colspan="5">
                                <div
                                    v-if="users.meta?.last_page > 1"
                                    class="flex flex-col items-center justify-between gap-4 py-4 sm:flex-row"
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
                                        <span class="font-medium">{{
                                            total
                                        }}</span>
                                        results
                                    </div>

                                    <!-- Pagination Controls -->
                                    <nav
                                        class="flex items-center overflow-hidden rounded-md border"
                                    >
                                        <!-- Prev -->
                                        <button
                                            class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                            :disabled="!users.links[0].url"
                                            @click="
                                                goToPage(users.links[0].url)
                                            "
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
                                                !users.links[
                                                    users.links.length - 1
                                                ].url
                                            "
                                            @click="
                                                goToPage(
                                                    users.links[
                                                        users.links.length - 1
                                                    ].url,
                                                )
                                            "
                                        >
                                            <ChevronRight class="h-4 w-4" />
                                        </button>
                                    </nav>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>

            <!-- Delete Confirmation -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete User"
                :description="`Are you sure you want to delete ${userBeingDeleted?.name}? This action cannot be undone.`"
                confirm-text="Delete User"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteUser"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>

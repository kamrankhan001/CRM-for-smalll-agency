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
    edit,
    index,
} from '@/actions/App/Http/Controllers/UserController';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
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
    users: PaginatedUsers;
    filters: {
        search?: string;
        role?: string;
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '#' }];

const showDeleteDialog = ref(false);
const userToDelete = ref<number | null>(null);
const showFilters = ref(false);

// Permission checks
const canDelete = computed(() => props.auth.user.role === 'admin');

const canEditUser = (user: User) => {
    const currentUser = props.auth.user;
    if (currentUser.role === 'admin') return true;
    // Managers and members can only edit their own profile
    return user.id === currentUser.id;
};

// Filters
const filters = ref({
    search: props.filters.search || '',
    role: props.filters.role || null,
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
        role: null,
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
    return (
        props.users.meta.from ||
        (props.users.meta.current_page - 1) * props.users.meta.per_page + 1
    );
});

const showingTo = computed(() => {
    if (!props.users.meta || props.users.data.length === 0) return 0;
    return (
        props.users.meta.to ||
        Math.min(
            props.users.meta.current_page * props.users.meta.per_page,
            props.users.meta.total,
        )
    );
});

const total = computed(() => props.users.meta?.total || 0);
</script>

<template>
    <Head title="Users" />
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
                            Users
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Manage your system users and permissions
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

                        <!-- Add User Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="create.url()" class="shrink-0">
                                        <Button
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Plus class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >New User</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Create new user</p>
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
                                placeholder="Search by name or email..."
                                class="w-full pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <Select v-model="filters.role">
                            <SelectTrigger class="w-full">
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
                            class="w-full"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="date_to">Joined To</Label>
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
                                <TableCell class="text-muted-foreground">
                                    {{ user.email }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="getBadgeVariant(user.role)"
                                        class="capitalize"
                                    >
                                        {{ user.role }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium">
                                        {{
                                            new Date(
                                                user.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                user.created_at,
                                            ).toLocaleTimeString()
                                        }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <ActionButtons
                                        :show-edit="canEditUser(user)"
                                        :show-delete="canDelete"
                                        :on-edit="() => goToEdit(user.id)"
                                        :on-delete="
                                            () => confirmDelete(user.id)
                                        "
                                        edit-tooltip="Edit user"
                                        delete-tooltip="Delete user"
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
                    </Table>
                </div>

                <!-- Table Footer moved outside Table -->
                <div class="border-t bg-muted/30 px-6 py-4">
                    <div
                        v-if="users.meta?.last_page > 1"
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
                                                :disabled="!users.links[0].url"
                                                @click="
                                                    goToPage(users.links[0].url)
                                                "
                                            >
                                                <ChevronLeft class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!users.links[0].url">
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
                                                    !users.links[
                                                        users.links.length - 1
                                                    ].url
                                                "
                                                @click="
                                                    goToPage(
                                                        users.links[
                                                            users.links.length -
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
                                            !users.links[users.links.length - 1]
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
                        {{ total }} user{{ total !== 1 ? 's' : '' }} total
                    </div>
                </div>
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

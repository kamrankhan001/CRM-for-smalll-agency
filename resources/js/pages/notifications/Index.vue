<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
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
import { Badge } from '@/components/ui/badge';
import { Bell, CheckCircle, Info, ChevronLeft, ChevronRight, Filter, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner'
import { useEchoNotification } from '@laravel/echo-vue'

// ===== INTERFACES =====
interface Notification {
    id: string | number;
    message: string;
    type: string;
    url?: string;
    read_at: string | null;
    created_at: string;
}

interface Meta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    notifications: { data: Notification[]; meta: Meta };
    filters: Record<string, any>;
}

interface LeadAssignedNotification {
    message: string;
    type: string;
    url?: string;
    lead_id: string | number;
    time?: string;
}

// ===== PROPS & REACTIVE STATE =====
const props = defineProps<Props>()
const page = usePage()
const userId = page.props.auth?.user?.id

const showFilters = ref(false);
const filters = ref({
    status: props.filters.status || '',
    type: props.filters.type || '',
});

// Create separate arrays for server-side and real-time notifications
const serverNotifications = ref<Notification[]>([...props.notifications.data]);
const realTimeNotifications = ref<Notification[]>([]);

// ===== ECHO NOTIFICATION LISTENER =====
useEchoNotification<LeadAssignedNotification>(
    `lead-assigned.${userId}`,
    (e: LeadAssignedNotification) => {
        console.log('🔔 LeadAssigned received:', e)

        // Create new real-time notification
        const newNotification: Notification = {
            id: `realtime-${Date.now()}`, // prefix to distinguish from server IDs
            message: e.message ?? 'New lead assigned.',
            type: e.type ?? 'lead_assigned',
            url: e.url ?? `/leads/${e.lead_id}`,
            read_at: null,
            created_at: new Date().toISOString(), // Shows as "Just now"
        };

        // Add to real-time notifications (limit to last 50)
        realTimeNotifications.value.unshift(newNotification);
        if (realTimeNotifications.value.length > 50) {
            realTimeNotifications.value = realTimeNotifications.value.slice(0, 50);
        }

        // Show toast notification
        toast.success(e.message ?? 'You have a new lead!', {
            description: e.time ? `Assigned at ${e.time}` : undefined,
            action: {
                label: 'View Lead',
                onClick: () => window.location.href = e.url ?? `/leads/${e.lead_id}`,
            },
        });
    },
    'App.Notifications.LeadAssignedNotification'
);

// ===== COMPUTED PROPERTIES =====

// Combine server-side and real-time notifications for display
const allNotifications = computed(() => {
    return [...realTimeNotifications.value, ...serverNotifications.value];
});

// Real-time notifications count (for display)
const realTimeCount = computed(() => realTimeNotifications.value.length);

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return filters.value.status || filters.value.type;
});

// Check if any notification is unread (for mark all as read button)
const hasUnreadNotifications = computed(() => {
    return allNotifications.value.some(n => !n.read_at);
});

// Pagination logic (only for server-side notifications)
const pageLinks = computed(() => {
    if (!props.notifications.meta.links) return [];
    return props.notifications.meta.links.filter(
        (_, index) => index !== 0 && index !== props.notifications.meta.links.length - 1,
    );
});

const showingFrom = computed(() => {
    if (!props.notifications.meta || serverNotifications.value.length === 0) return 0;
    return props.notifications.meta.from || 0;
});

const showingTo = computed(() => {
    if (!props.notifications.meta || serverNotifications.value.length === 0) return 0;
    return props.notifications.meta.to || 0;
});

const total = computed(() => props.notifications.meta?.total || 0);

// ===== METHODS =====
function applyFilters() {
    router.get('/notifications', filters.value, { preserveScroll: true,  preserveState: true });
}

function resetFilters() {
    filters.value = {
        status: '',
        type: '',
    };
    router.get('/notifications', {}, { preserveScroll: true,  preserveState: true });
}

function goToPage(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true,  preserveState: true });
}

function markAsRead(id: string | number) {
    router.post(`/notifications/${id}/mark-read`, {}, { preserveScroll: true });
}

function markAllAsRead() {
    router.post('/notifications/mark-all-read', {}, { preserveScroll: true });
}

function formatDate(dateString: string) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffTime / (1000 * 60));
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffMinutes < 1) {
        return 'Just now';
    } else if (diffMinutes < 60) {
        return `${diffMinutes}m ago`;
    } else if (diffHours < 24) {
        return `${diffHours}h ago`;
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays}d ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }
}

function getTypeVariant(type: string) {
    switch (type.toLowerCase()) {
        case 'success':
            return 'default';
        case 'warning':
            return 'secondary';
        case 'error':
            return 'destructive';
        case 'info':
            return 'outline';
        default:
            return 'outline';
    }
}
</script>

<template>
    <Head title="Notifications" />
    <AppLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Notifications</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage your notifications and stay updated
                        <span v-if="realTimeCount > 0" class="ml-2 text-primary font-medium">
                            ({{ realTimeCount }} new real-time)
                        </span>
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
                    <Button
                        @click="markAllAsRead"
                        class="flex items-center gap-2"
                        :disabled="!hasUnreadNotifications"
                    >
                        <CheckCircle class="h-4 w-4" />
                        Mark All as Read
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div v-if="showFilters" class="mb-6 rounded-lg border p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Status Filter -->
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select v-model="filters.status">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">All Statuses</SelectItem>
                                <SelectItem value="unread">Unread</SelectItem>
                                <SelectItem value="read">Read</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Type Filter -->
                    <div class="space-y-2">
                        <Label for="type">Type</Label>
                        <Input
                            id="type"
                            v-model="filters.type"
                            type="text"
                            placeholder="Filter by type..."
                            class="w-full"
                            @keyup.enter="applyFilters"
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

            <!-- Notifications Card -->
            <Card class="rounded-lg border">
                <CardContent class="p-0">
                    <!-- Notifications List -->
                    <div class="overflow-x-auto">
                        <ul class="divide-y divide-border">
                            <li
                                v-for="notification in allNotifications"
                                :key="notification.id"
                                class="p-4 hover:bg-muted/50 transition-colors"
                                :class="{
                                    'bg-muted/30': !notification.read_at,
                                    'border-l-4 border-l-primary': notification.id.toString().startsWith('realtime-')
                                }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3 flex-1">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 mt-1"
                                            :class="{
                                                'bg-green-500/10': notification.id.toString().startsWith('realtime-')
                                            }"
                                        >
                                            <Info 
                                                class="h-4 w-4" 
                                                :class="{
                                                    'text-primary': !notification.id.toString().startsWith('realtime-'),
                                                    'text-green-500': notification.id.toString().startsWith('realtime-')
                                                }" 
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <p
                                                    class="text-sm font-medium"
                                                    :class="{
                                                        'text-muted-foreground': notification.read_at,
                                                    }"
                                                >
                                                    {{ notification.message }}
                                                </p>
                                                <Badge
                                                    :variant="getTypeVariant(notification.type)"
                                                    class="text-xs capitalize"
                                                >
                                                    {{ notification.type }}
                                                </Badge>
                                                <Badge
                                                    v-if="!notification.read_at"
                                                    variant="secondary"
                                                    class="text-xs"
                                                >
                                                    New
                                                </Badge>
                                                <Badge
                                                    v-if="notification.id.toString().startsWith('realtime-')"
                                                    variant="default"
                                                    class="text-xs bg-green-500 text-white"
                                                >
                                                    Live
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-muted-foreground">
                                                {{ formatDate(notification.created_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                        <Link
                                            v-if="notification.url"
                                            :href="notification.url"
                                            class="text-xs text-primary hover:underline"
                                        >
                                            View
                                        </Link>
                                        <Button
                                            v-if="!notification.read_at && !notification.id.toString().startsWith('realtime-')"
                                            @click="markAsRead(notification.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 text-xs"
                                        >
                                            Mark as read
                                        </Button>
                                        <CheckCircle
                                            v-if="notification.read_at"
                                            class="h-4 w-4 text-green-500"
                                        />
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <!-- Empty State -->
                        <div
                            v-if="allNotifications.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            <Bell class="h-12 w-12 mx-auto mb-3 opacity-50" />
                            <p class="text-lg font-medium">No notifications found</p>
                            <p class="text-sm">We'll notify you when something important happens.</p>
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="border-t bg-muted/30 px-6 py-4">
                        <div
                            v-if="props.notifications.meta?.last_page > 1"
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
                            <nav class="flex items-center overflow-hidden rounded-md border">
                                <!-- Prev -->
                                <button
                                    class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                    :disabled="!props.notifications.meta.links[0]?.url"
                                    @click="goToPage(props.notifications.meta.links[0]?.url)"
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
                                    :disabled="!props.notifications.meta.links[props.notifications.meta.links.length - 1]?.url"
                                    @click="goToPage(props.notifications.meta.links[props.notifications.meta.links.length - 1]?.url)"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </nav>
                        </div>
                        <div
                            v-else
                            class="text-center text-sm text-muted-foreground"
                        >
                            {{ total }} notification{{ total !== 1 ? 's' : '' }} total
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
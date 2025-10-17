<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, 
    UserCheck, 
    ClipboardList, 
    FileText, 
    FolderOpen, 
    StickyNote,
    DollarSign,
    Calendar,
    Activity,
    ArrowRight,
    TrendingUp,
    Target,
    Clock
} from 'lucide-vue-next';
import { computed } from 'vue';

// === Interfaces ===
interface Stats {
    leads: number;
    clients: number;
    pending_tasks: number;
    total_appointments: number;
    upcoming_appointments: number;
    notes: number;
    projects: number;
    documents: number;
    invoices?: number;
    total_invoice_amount?: number;
    paid_invoice_amount?: number;
    users?: number;
}

interface User {
    id: number;
    name: string;
    email?: string;
}

interface RecentActivity {
    id: number;
    action: string;
    subject_type: string;
    subject_id: number;
    causer_id: number;
    created_at: string;
    causer?: User;
}

interface TopPerformer {
    name: string;
    total_leads: number;
    converted_clients: number;
    conversion_rate: number;
}

interface TaskStatusItem {
    status: string;
    label: string;
    count: number;
    percentage: number;
}

interface RecentData {
    recent_leads: any[];
    recent_tasks: any[];
    recent_appointments: any[];
    recent_clients: any[];
    recent_notes: any[];
    recent_projects: any[];
    recent_documents: any[];
    recent_invoices?: any[];
    recent_users?: any[];
}

interface Props {
    stats: Stats;
    leadByStatus: Record<string, number>;
    taskByStatus: TaskStatusItem[];
    appointmentByStatus: TaskStatusItem[];
    monthlyStats: Record<string, number>;
    topPerformers?: TopPerformer[];
    recentActivities: RecentActivity[];
    recentData: RecentData;
    userTaskStats: {
        total_tasks: number;
        pending_tasks: number;
        in_progress_tasks: number;
        completed_tasks: number;
    };
    userAppointmentStats: {
        total_appointments: number;
        scheduled_appointments: number;
        completed_appointments: number;
        cancelled_appointments: number;
        upcoming_appointments: number;
    };
    invoiceStats?: {
        total_invoices: number;
        draft_invoices: number;
        sent_invoices: number;
        partially_paid_invoices: number;
        paid_invoices: number;
        cancelled_invoices: number;
    };
    userRole: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// Fix TypeScript type for badge variant
type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive' | null | undefined;

function getStatusVariant(status: string): BadgeVariant {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'qualified':
        case 'paid':
        case 'converted':
        case 'finished':
            return 'default';
        case 'in_progress':
        case 'contacted':
        case 'sent':
        case 'partially_paid':
        case 'scheduled':
        case 'confirmed':
            return 'secondary';
        case 'pending':
        case 'new':
        case 'draft':
        case 'open':
            return 'outline';
        case 'lost':
        case 'cancelled':
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount || 0);
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatTime(dateString: string) {
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Quick stats for the main cards
const quickStats = computed(() => [
    {
        title: 'Total Leads',
        value: props.stats.leads,
        icon: Users,
        description: 'Potential clients',
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
        href: '/leads'
    },
    {
        title: 'Active Clients',
        value: props.stats.clients,
        icon: UserCheck,
        description: 'Paying customers',
        color: 'text-green-600',
        bgColor: 'bg-green-50',
        href: '/clients'
    },
    {
        title: 'Pending Tasks',
        value: props.stats.pending_tasks,
        icon: ClipboardList,
        description: 'Awaiting completion',
        color: 'text-orange-600',
        bgColor: 'bg-orange-50',
        href: '/tasks'
    },
    {
        title: 'Upcoming Appointments',
        value: props.stats.upcoming_appointments,
        icon: Clock,
        description: 'Scheduled meetings',
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
        href: '/appointments'
    },
]);

// Additional stats for second row
const additionalStats = computed(() => {
    const stats = [
        {
            title: 'Total Appointments',
            value: props.stats.total_appointments,
            icon: Calendar,
            description: 'All meetings',
            color: 'text-violet-600',
            bgColor: 'bg-violet-50',
            href: '/appointments'
        },
        {
            title: 'Documents',
            value: props.stats.documents,
            icon: FileText,
            description: 'Uploaded files',
            color: 'text-indigo-600',
            bgColor: 'bg-indigo-50',
            href: '/documents'
        },
        {
            title: 'Notes',
            value: props.stats.notes,
            icon: StickyNote,
            description: 'System notes',
            color: 'text-amber-600',
            bgColor: 'bg-amber-50',
            href: '/notes'
        },
        {
            title: 'Projects',
            value: props.stats.projects,
            icon: FolderOpen,
            description: 'Ongoing projects',
            color: 'text-cyan-600',
            bgColor: 'bg-cyan-50',
            href: '/projects'
        },
    ];

    // Add admin/manager specific stats
    if (props.userRole === 'admin' || props.userRole === 'manager') {
        stats.push({
            title: 'Invoices',
            value: props.stats.invoices || 0,
            icon: DollarSign,
            description: 'Billing documents',
            color: 'text-emerald-600',
            bgColor: 'bg-emerald-50',
            href: '/invoices'
        });
    }

    if (props.userRole === 'admin') {
        stats.push({
            title: 'Users',
            value: props.stats.users || 0,
            icon: Users,
            description: 'Team members',
            color: 'text-gray-600',
            bgColor: 'bg-gray-50',
            href: '/users'
        });
    }

    return stats;
});

// Lead status distribution for chart
const leadStatusData = computed(() => {
    return Object.entries(props.leadByStatus).map(([status, count]) => ({
        status,
        count,
        label: status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' '),
        variant: getStatusVariant(status)
    }));
});

// Check if user can see team performance data
const canViewTeamPerformance = computed(() => {
    return props.userRole === 'admin' || props.userRole === 'manager';
});

// Check if user can see invoice stats
const canViewInvoiceStats = computed(() => {
    return props.userRole === 'admin' || props.userRole === 'manager';
});

</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-6 p-6">
            <!-- Welcome Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="mt-1 text-muted-foreground">
                        Welcome back! Here's what's happening today.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Calendar class="h-5 w-5 text-muted-foreground" />
                    <span class="text-sm text-muted-foreground">
                        {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </span>
                </div>
            </div>

            <!-- Role Badge -->
            <div class="flex items-center gap-2 mb-6">
                <Badge variant="secondary" class="capitalize">
                    {{ userRole }} Role
                </Badge>
                <span class="text-sm text-muted-foreground">
                    {{ userRole === 'admin' ? 'Full system access' : userRole === 'manager' ? 'Team management access' : 'Personal workspace access' }}
                </span>
            </div>

            <!-- Pinterest-style Masonry Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6 auto-rows-min">
                
                <!-- Main Stats Cards - Full Width -->
                <div class="lg:col-span-3 xl:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card 
                        v-for="stat in quickStats" 
                        :key="stat.title"
                        class="relative overflow-hidden transition-all hover:shadow-md"
                    >
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ stat.title }}
                            </CardTitle>
                            <component 
                                :is="stat.icon" 
                                class="h-4 w-4" 
                                :class="stat.color"
                            />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ stat.description }}
                            </p>
                            <Link :href="stat.href" class="absolute inset-0"></Link>
                        </CardContent>
                    </Card>
                </div>

                <!-- Additional Stats -->
                <div class="lg:col-span-3 xl:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card 
                        v-for="stat in additionalStats" 
                        :key="stat.title"
                        class="relative overflow-hidden transition-all hover:shadow-md"
                    >
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ stat.title }}
                            </CardTitle>
                            <component 
                                :is="stat.icon" 
                                class="h-4 w-4" 
                                :class="stat.color"
                            />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ stat.description }}
                            </p>
                            <Link :href="stat.href" class="absolute inset-0"></Link>
                        </CardContent>
                    </Card>
                </div>

                <!-- My Tasks Summary -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <ClipboardList class="h-4 w-4" />
                            My Tasks
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Total Tasks</span>
                                <Badge variant="default">{{ userTaskStats.total_tasks }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Pending</span>
                                <Badge variant="outline">{{ userTaskStats.pending_tasks }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">In Progress</span>
                                <Badge variant="secondary">{{ userTaskStats.in_progress_tasks }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Completed</span>
                                <Badge variant="default">{{ userTaskStats.completed_tasks }}</Badge>
                            </div>
                        </div>
                        <Link href="/tasks">
                            <Button variant="outline" class="w-full text-xs">
                                View All Tasks
                                <ArrowRight class="ml-2 h-3 w-3" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <!-- My Appointments Summary -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Calendar class="h-4 w-4" />
                            My Appointments
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Total</span>
                                <Badge variant="default">{{ userAppointmentStats.total_appointments }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Scheduled</span>
                                <Badge variant="secondary">{{ userAppointmentStats.scheduled_appointments }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Upcoming</span>
                                <Badge variant="outline">{{ userAppointmentStats.upcoming_appointments }}</Badge>
                            </div>
                            <div class="flex justify-between items-center p-2">
                                <span class="text-sm">Completed</span>
                                <Badge variant="default">{{ userAppointmentStats.completed_appointments }}</Badge>
                            </div>
                        </div>
                        <Link href="/appointments">
                            <Button variant="outline" class="w-full text-xs">
                                View All Appointments
                                <ArrowRight class="ml-2 h-3 w-3" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <!-- Leads by Status -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Target class="h-4 w-4" />
                            Lead Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="item in leadStatusData" 
                                :key="item.status"
                                class="flex items-center justify-between p-2"
                            >
                                <Badge :variant="item.variant" class="capitalize text-xs">
                                    {{ item.label }}
                                </Badge>
                                <span class="text-sm font-medium">{{ item.count }}</span>
                            </div>
                            <div 
                                v-if="leadStatusData.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No leads data
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Task Status Overview -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <ClipboardList class="h-4 w-4" />
                            Task Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="task in taskByStatus" 
                                :key="task.status"
                                class="flex items-center justify-between p-2"
                            >
                                <Badge :variant="getStatusVariant(task.status)" class="capitalize text-xs">
                                    {{ task.label }}
                                </Badge>
                                <div class="text-right">
                                    <div class="text-sm font-medium">{{ task.count }}</div>
                                    <div class="text-xs text-muted-foreground">{{ task.percentage }}%</div>
                                </div>
                            </div>
                            <div 
                                v-if="taskByStatus.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No tasks data
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Appointment Status Overview -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Calendar class="h-4 w-4" />
                            Appointment Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="appointment in appointmentByStatus" 
                                :key="appointment.status"
                                class="flex items-center justify-between p-2"
                            >
                                <Badge :variant="getStatusVariant(appointment.status)" class="capitalize text-xs">
                                    {{ appointment.label }}
                                </Badge>
                                <div class="text-right">
                                    <div class="text-sm font-medium">{{ appointment.count }}</div>
                                    <div class="text-xs text-muted-foreground">{{ appointment.percentage }}%</div>
                                </div>
                            </div>
                            <div 
                                v-if="appointmentByStatus.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No appointments data
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Invoice Overview (Admin/Manager) -->
                <Card v-if="canViewInvoiceStats" class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <DollarSign class="h-4 w-4" />
                            Revenue
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs">Total:</span>
                                <span class="text-sm font-semibold">{{ formatCurrency(stats.total_invoice_amount || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs">Paid:</span>
                                <span class="text-sm font-semibold text-green-600">{{ formatCurrency(stats.paid_invoice_amount || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs">Due:</span>
                                <span class="text-sm font-semibold text-orange-600">
                                    {{ formatCurrency((stats.total_invoice_amount || 0) - (stats.paid_invoice_amount || 0)) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Leads -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Recent Leads
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="lead in recentData.recent_leads" 
                                :key="lead.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ lead.name }}</p>
                                        <p class="text-xs text-muted-foreground truncate">
                                            {{ lead.creator?.name ? `By ${lead.creator.name}` : 'No creator' }}
                                        </p>
                                    </div>
                                    <Badge :variant="getStatusVariant(lead.status)" class="capitalize text-xs shrink-0">
                                        {{ lead.status?.replace('_', ' ') }}
                                    </Badge>
                                </div>
                            </div>
                            <div 
                                v-if="recentData.recent_leads.length === 0"
                                class="text-center py-6 text-muted-foreground text-sm"
                            >
                                No recent leads
                            </div>
                            <Link href="/leads" class="block">
                                <Button variant="outline" class="w-full text-xs">
                                    View All Leads
                                    <ArrowRight class="ml-2 h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Appointments -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Recent Appointments
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="appointment in recentData.recent_appointments" 
                                :key="appointment.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ appointment.title }}</p>
                                        <p class="text-xs text-muted-foreground truncate">
                                            {{ formatDate(appointment.start_time) }} at {{ formatTime(appointment.start_time) }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ appointment.creator?.name ? `By ${appointment.creator.name}` : 'No creator' }}
                                        </p>
                                    </div>
                                    <Badge :variant="getStatusVariant(appointment.status)" class="capitalize text-xs shrink-0">
                                        {{ appointment.status?.replace('_', ' ') }}
                                    </Badge>
                                </div>
                            </div>
                            <div 
                                v-if="recentData.recent_appointments.length === 0"
                                class="text-center py-6 text-muted-foreground text-sm"
                            >
                                No recent appointments
                            </div>
                            <Link href="/appointments" class="block">
                                <Button variant="outline" class="w-full text-xs">
                                    View All Appointments
                                    <ArrowRight class="ml-2 h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Tasks -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="h-4 w-4" />
                            Recent Tasks
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="task in recentData.recent_tasks" 
                                :key="task.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ task.title }}</p>
                                        <p class="text-xs text-muted-foreground truncate">
                                            {{ task.assignee?.name ? `Assigned to ${task.assignee.name}` : 'Unassigned' }}
                                        </p>
                                    </div>
                                    <Badge :variant="getStatusVariant(task.status)" class="capitalize text-xs shrink-0">
                                        {{ task.status?.replace('_', ' ') }}
                                    </Badge>
                                </div>
                            </div>
                            <div 
                                v-if="recentData.recent_tasks.length === 0"
                                class="text-center py-6 text-muted-foreground text-sm"
                            >
                                No recent tasks
                            </div>
                            <Link href="/tasks" class="block">
                                <Button variant="outline" class="w-full text-xs">
                                    View All Tasks
                                    <ArrowRight class="ml-2 h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Projects -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <FolderOpen class="h-4 w-4" />
                            Projects
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="project in recentData.recent_projects" 
                                :key="project.id"
                                class="flex items-center justify-between p-2"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ project.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        {{ project.client?.name || 'No client' }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(project.status)" class="text-xs shrink-0">
                                    {{ project.status }}
                                </Badge>
                            </div>
                            <div 
                                v-if="recentData.recent_projects.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No projects
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Clients -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <UserCheck class="h-4 w-4" />
                            Clients
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="client in recentData.recent_clients" 
                                :key="client.id"
                                class="flex items-center gap-2 p-2"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ client.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        {{ client.email || 'No email' }}
                                    </p>
                                </div>
                            </div>
                            <div 
                                v-if="recentData.recent_clients.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No clients
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Performers (Admin/Manager) -->
                <Card v-if="canViewTeamPerformance && topPerformers" class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4" />
                            Top Performers
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="performer in topPerformers" 
                                :key="performer.name"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm">{{ performer.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ performer.total_leads }} leads • {{ performer.converted_clients }} clients
                                    </p>
                                </div>
                                <Badge variant="default" class="text-xs">
                                    {{ performer.conversion_rate }}%
                                </Badge>
                            </div>
                            <div 
                                v-if="topPerformers.length === 0"
                                class="text-center py-6 text-muted-foreground text-sm"
                            >
                                No performance data
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activities -->
                <Card class="lg:col-span-2 xl:col-span-3">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-4 w-4" />
                            Recent Activities
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="activity in recentActivities" 
                                :key="activity.id"
                                class="flex items-center gap-3 p-3 rounded-lg border"
                            >
                                <div class="flex-shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                    <Users class="h-3 w-3" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm">
                                        {{ activity.causer?.name ?? 'System' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground capitalize">
                                        {{ activity.action }} 
                                        {{ activity.subject_type?.split('\\').pop()?.toLowerCase() }}
                                    </p>
                                </div>
                                <div class="text-right text-xs text-muted-foreground shrink-0">
                                    <div>{{ new Date(activity.created_at).toLocaleDateString() }}</div>
                                    <div>{{ new Date(activity.created_at).toLocaleTimeString() }}</div>
                                </div>
                            </div>
                            <div 
                                v-if="recentActivities.length === 0"
                                class="text-center py-8 text-muted-foreground text-sm"
                            >
                                No recent activities
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Invoices (Admin/Manager) -->
                <Card v-if="canViewInvoiceStats && recentData.recent_invoices" class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <DollarSign class="h-4 w-4" />
                            Invoices
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="invoice in recentData.recent_invoices" 
                                :key="invoice.id"
                                class="flex items-center justify-between p-2"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ invoice.invoice_number || invoice.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatCurrency(invoice.amount) }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(invoice.status)" class="text-xs shrink-0">
                                    {{ invoice.status?.charAt(0) }}
                                </Badge>
                            </div>
                            <div 
                                v-if="recentData.recent_invoices.length === 0"
                                class="text-center py-4 text-muted-foreground text-sm"
                            >
                                No invoices
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
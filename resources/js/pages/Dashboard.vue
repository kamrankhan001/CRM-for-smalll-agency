<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    ArrowRight
} from 'lucide-vue-next';
import { computed } from 'vue';

// === Interfaces ===
interface Stats {
    leads: number;
    clients: number;
    pending_tasks: number;
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
    monthlyStats: Record<string, number>;
    topPerformers: TopPerformer[];
    recentActivities: RecentActivity[];
    recentData: RecentData;
    userTaskStats: {
        total_tasks: number;
        pending_tasks: number;
        in_progress_tasks: number;
        completed_tasks: number;
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

function getStatusVariant(status: string) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'qualified':
        case 'paid':
            return 'default';
        case 'in_progress':
        case 'contacted':
        case 'sent':
        case 'partially_paid':
            return 'secondary';
        case 'pending':
        case 'new':
        case 'draft':
            return 'outline';
        case 'lost':
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
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
        title: 'Active Projects',
        value: props.stats.projects,
        icon: FolderOpen,
        description: 'Ongoing projects',
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
        href: '/projects'
    },
]);

// Additional stats for second row
const additionalStats = computed(() => {
    const stats = [
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
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Welcome Section -->
            <div class="flex items-center justify-between">
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

            <!-- === Main Stats Cards === -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
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

            <!-- === Additional Stats === -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
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

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- My Tasks Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="h-5 w-5" />
                            My Tasks
                        </CardTitle>
                        <CardDescription>
                            Your task overview
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ userTaskStats.total_tasks }}
                                </div>
                                <p class="text-sm text-muted-foreground">Total Tasks</p>
                            </div>
                            <div class="space-y-1">
                                <div class="text-2xl font-bold text-orange-600">
                                    {{ userTaskStats.pending_tasks }}
                                </div>
                                <p class="text-sm text-muted-foreground">Pending</p>
                            </div>
                            <div class="space-y-1">
                                <div class="text-2xl font-bold text-yellow-600">
                                    {{ userTaskStats.in_progress_tasks }}
                                </div>
                                <p class="text-sm text-muted-foreground">In Progress</p>
                            </div>
                            <div class="space-y-1">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ userTaskStats.completed_tasks }}
                                </div>
                                <p class="text-sm text-muted-foreground">Completed</p>
                            </div>
                        </div>
                        <Link href="/tasks">
                            <Button variant="outline" class="w-full">
                                View All Tasks
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <!-- Invoice Stats (Admin/Manager only) -->
                <Card v-if="userRole === 'admin' || userRole === 'manager'">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <DollarSign class="h-5 w-5" />
                            Invoice Overview
                        </CardTitle>
                        <CardDescription>
                            Financial summary
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Total Revenue:</span>
                                <span class="font-semibold">{{ formatCurrency(stats.total_invoice_amount || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Paid Amount:</span>
                                <span class="font-semibold text-green-600">{{ formatCurrency(stats.paid_invoice_amount || 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Outstanding:</span>
                                <span class="font-semibold text-orange-600">
                                    {{ formatCurrency((stats.total_invoice_amount || 0) - (stats.paid_invoice_amount || 0)) }}
                                </span>
                            </div>
                        </div>
                        <Link href="/invoices">
                            <Button variant="outline" class="w-full">
                                Manage Invoices
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- === Recent Items & Activities === -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Leads -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Recent Leads
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div 
                                v-for="lead in recentData.recent_leads" 
                                :key="lead.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div>
                                    <p class="font-medium">{{ lead.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Created by {{ lead.creator?.name }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(lead.status)" class="capitalize">
                                    {{ lead.status }}
                                </Badge>
                            </div>
                            <Link href="/leads" class="block">
                                <Button variant="outline" class="w-full">
                                    View All Leads
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Tasks -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardList class="h-5 w-5" />
                            Recent Tasks
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div 
                                v-for="task in recentData.recent_tasks" 
                                :key="task.id"
                                class="flex items-center justify-between p-3 rounded-lg border"
                            >
                                <div>
                                    <p class="font-medium">{{ task.title }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Assigned to {{ task.assignee?.name || 'Unassigned' }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(task.status)" class="capitalize">
                                    {{ task.status.replace('_', ' ') }}
                                </Badge>
                            </div>
                            <Link href="/tasks" class="block">
                                <Button variant="outline" class="w-full">
                                    View All Tasks
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- === Additional Recent Items === -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Recent Projects -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <FolderOpen class="h-4 w-4" />
                            Recent Projects
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="project in recentData.recent_projects" 
                                :key="project.id"
                                class="flex items-center justify-between p-2 rounded-lg border"
                            >
                                <div>
                                    <p class="font-medium text-sm">{{ project.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ project.client?.name || 'No client' }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(project.status)" class="capitalize text-xs">
                                    {{ project.status }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Documents -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <FileText class="h-4 w-4" />
                            Recent Documents
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="doc in recentData.recent_documents" 
                                :key="doc.id"
                                class="flex items-center justify-between p-2 rounded-lg border"
                            >
                                <div>
                                    <p class="font-medium text-sm">{{ doc.title }}</p>
                                    <p class="text-xs text-muted-foreground capitalize">
                                        {{ doc.type }}
                                    </p>
                                </div>
                                <Badge variant="outline" class="text-xs capitalize">
                                    {{ doc.documentable?.type || 'General' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Invoices (Admin/Manager) -->
                <Card v-if="(userRole === 'admin' || userRole === 'manager') && recentData.recent_invoices">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <DollarSign class="h-4 w-4" />
                            Recent Invoices
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="invoice in recentData.recent_invoices" 
                                :key="invoice.id"
                                class="flex items-center justify-between p-2 rounded-lg border"
                            >
                                <div>
                                    <p class="font-medium text-sm">{{ invoice.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatCurrency(invoice.amount) }}
                                    </p>
                                </div>
                                <Badge :variant="getStatusVariant(invoice.status)" class="text-xs capitalize">
                                    {{ invoice.status.replace('_', ' ') }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- === Recent Activities === -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Recent Activities
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div 
                            v-for="activity in recentActivities" 
                            :key="activity.id"
                            class="flex items-center justify-between p-3 rounded-lg border"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                    <Users class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="font-medium">
                                        {{ activity.causer?.name ?? 'System' }}
                                    </p>
                                    <p class="text-sm text-muted-foreground capitalize">
                                        {{ activity.action }} 
                                        {{ activity.subject_type?.split('\\').pop()?.toLowerCase() }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">
                                    {{ new Date(activity.created_at).toLocaleDateString() }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ new Date(activity.created_at).toLocaleTimeString() }}
                                </p>
                            </div>
                        </div>
                        <div 
                            v-if="recentActivities.length === 0"
                            class="text-center py-8 text-muted-foreground"
                        >
                            No recent activities found.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
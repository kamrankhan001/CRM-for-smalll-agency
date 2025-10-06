<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// Shadcn-vue chart components
import { BarChart } from '@/components/ui/chart-bar';
import { DonutChart } from '@/components/ui/chart-donut';
import { LineChart } from '@/components/ui/chart-line';

// === Interfaces ===
interface Stats {
    leads: number;
    clients: number;
    pending_tasks: number;
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

interface Props {
    stats: Stats;
    leadByStatus: Record<string, number>;
    taskByStatus: TaskStatusItem[];
    monthlyStats: Record<string, number>;
    topPerformers: TopPerformer[];
    recentActivities: RecentActivity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// === Chart Data Preparation ===
const leadsChartData = computed(() => {
    const data = Object.entries(props.leadByStatus).map(([status, count]) => ({
        name: formatStatus(status),
        value: count,
    }));
    return data.length > 0 ? data : [{ name: 'No Data', value: 0 }];
});

const tasksChartData = computed(() => {
    const data = props.taskByStatus.map((task) => ({
        name: task.label,
        value: task.count,
    }));
    return data.length > 0 ? data : [{ name: 'No Tasks', value: 1 }];
});

const monthlyChartData = computed(() => {
    const data = Object.entries(props.monthlyStats).map(([monthYear, count]) => ({
        month: monthYear,
        leads: Number(count) || 0,
    }));

    // LineChart needs at least 2 data points
    if (data.length === 0) {
        return [
            { month: 'Sep 2025', leads: 0 },
            { month: 'Oct 2025', leads: 0 },
        ];
    }

    if (data.length === 1) {
        // Add a previous month for context
        const currentMonth = data[0];
        const prevMonth = getPreviousMonth(currentMonth.month);
        return [{ month: prevMonth, leads: 0 }, currentMonth];
    }

    return data;
});

const performersChartData = computed(() => {
    const data = props.topPerformers.map((performer) => ({
        name: performer.name,
        clients: performer.converted_clients || 0,
    }));
    return data.length > 0 ? data : [{ name: 'No Data', clients: 0 }];
});

// Simple color arrays for charts
const barChartColors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444'];
const donutChartColors = ['#3b82f6', '#22c55e', '#f59e0b'];

// === Utility Functions ===
function formatStatus(status: string) {
    return status
        .split('_')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

function getPreviousMonth(currentMonth: string): string {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const [currentMonthName, currentYear] = currentMonth.split(' ');
    const currentIndex = months.indexOf(currentMonthName);
    
    if (currentIndex === 0) {
        return `Dec ${parseInt(currentYear) - 1}`;
    } else {
        return `${months[currentIndex - 1]} ${currentYear}`;
    }
}

function getStatusVariant(status: string) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'qualified':
            return 'default';
        case 'in_progress':
        case 'contacted':
            return 'secondary';
        case 'pending':
        case 'new':
            return 'outline';
        case 'lost':
            return 'destructive';
        default:
            return 'outline';
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- === Stats Cards === -->
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Total Leads</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">
                            {{ props.stats.leads }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Potential clients in pipeline
                        </p>
                    </CardContent>
                </Card>

                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Total Clients</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">
                            {{ props.stats.clients }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Active paying customers
                        </p>
                    </CardContent>
                </Card>

                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Pending Tasks</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">
                            {{ props.stats.pending_tasks }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Tasks awaiting completion
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- === Charts Section === -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Leads by Status - Bar Chart -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Leads by Status (Current Month)</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Current month lead distribution across different stages
                        </p>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            :data="leadsChartData"
                            :categories="['value']"
                            index="name"
                            :colors="barChartColors"
                            :showLegend="true"
                            :showTooltip="true"
                            class="h-[250px] w-full"
                        />
                        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                            <span>Lead Status</span>
                            <span>Count</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tasks by Status - Donut Chart -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Tasks by Status (Current Month)</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Current month task distribution
                        </p>
                    </CardHeader>
                    <CardContent>
                        <DonutChart
                            :data="tasksChartData"
                            category="value"
                            index="name"
                            :colors="donutChartColors"
                            :showTooltip="true"
                            :showLegend="true"
                            class="h-[250px] w-full"
                        />
                        <div class="text-center mt-2 text-xs text-muted-foreground">
                            Task Distribution
                        </div>
                    </CardContent>
                </Card>

                <!-- Monthly Growth - Line Chart -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Monthly Growth (Last 12 Months)</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Lead growth trend over the past year
                        </p>
                    </CardHeader>
                    <CardContent>
                        <LineChart
                            :data="monthlyChartData"
                            :categories="['leads']"
                            index="month"
                            :colors="['#3b82f6']"
                            :showTooltip="true"
                            :showGridLine="true"
                            class="h-[250px] w-full"
                        />
                        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                            <span>Month</span>
                            <span>Leads Created</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Performers - Bar Chart -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle>Top Performers (Current Month)</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Team members with highest client conversions
                        </p>
                    </CardHeader>
                    <CardContent>
                        <BarChart
                            :data="performersChartData"
                            :categories="['clients']"
                            index="name"
                            :colors="['#22c55e']"
                            :showTooltip="true"
                            :showLegend="true"
                            class="h-[250px] w-full"
                        />
                        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                            <span>Team Member</span>
                            <span>Clients Converted</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- === Recent Activities === -->
            <Card class="rounded-2xl shadow-sm">
                <CardHeader>
                    <CardTitle>Recent Activities</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Action</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="a in props.recentActivities"
                                :key="a.id"
                            >
                                <TableCell>{{ a.causer?.name ?? 'System' }}</TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="getStatusVariant(a.action)"
                                        class="capitalize"
                                    >
                                        {{ a.action }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ a.subject_type?.split('\\').pop() }}
                                        #{{ a.subject_id }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {{
                                        new Date(
                                            a.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-if="props.recentActivities.length === 0"
                            >
                                <TableCell
                                    colspan="4"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    No recent activities found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
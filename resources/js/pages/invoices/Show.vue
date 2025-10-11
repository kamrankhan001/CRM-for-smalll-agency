<script setup lang="ts">
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/InvoiceController';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeft,
    Building,
    Calendar,
    Download,
    Edit,
    Mail,
    Receipt,
    Trash2,
    User,
    DollarSign,
    CheckCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
}

interface Client {
    id: number;
    name: string;
    email: string;
    company: string;
}

interface Project {
    id: number;
    name: string;
}

interface Activity {
    id: number;
    description: string;
    causer: User | null;
    created_at: string;
    properties?: any;
}

interface Invoice {
    id: number;
    invoice_number: string;
    title: string;
    amount: number;
    amount_paid: number;
    balance: number;
    status: string;
    issue_date: string;
    due_date: string;
    paid_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    client: Client | null;
    project: Project | null;
    creator: User | null;
}

interface Props {
    invoice: Invoice;
    activities: Activity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: index.url() },
    { title: props.invoice.invoice_number, href: '#' },
];

const showDeleteDialog = ref(false);

function getStatusColor(status: string) {
    const colors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        partially_paid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        overdue: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    };
    return (
        colors[status as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function formatDate(date: string | null) {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function getDaysRemaining(dueDate: string) {
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

function downloadInvoice() {
    window.open(`/invoices/${props.invoice.id}/download`, '_blank');
}

function sendInvoice() {
    router.post(`/invoices/${props.invoice.id}/send`);
}

function markAsPaid() {
    router.put(`/invoices/${props.invoice.id}/mark-as-paid`);
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteInvoice() {
    router.delete(destroy.url(props.invoice.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="props.invoice.invoice_number" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <Link :href="index.url()">
                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1
                            class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            {{ props.invoice.invoice_number }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            {{ props.invoice.title }} • Created {{ formatDate(props.invoice.created_at) }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Download Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="downloadInvoice"
                                    >
                                        <Download class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Download</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Download PDF invoice</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Send Button -->
                        <TooltipProvider v-if="props.invoice.client?.email && props.invoice.status !== 'paid'">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="sendInvoice"
                                    >
                                        <Mail class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Send</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Send invoice to client</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Mark as Paid Button -->
                        <TooltipProvider v-if="props.invoice.status !== 'paid' && props.invoice.status !== 'cancelled'">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="markAsPaid"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Mark Paid</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Mark invoice as paid</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.invoice.id)">
                                        <Button
                                            variant="outline"
                                            class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                            size="sm"
                                        >
                                            <Edit class="h-4 w-4" />
                                            <span class="hidden lg:inline"
                                                >Edit</span
                                            >
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Edit invoice information</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Delete Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="destructive"
                                        class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                                        size="sm"
                                        @click="confirmDelete"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Delete</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Delete this invoice</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Invoice Info & Notes -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Invoice Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Receipt class="h-5 w-5" />
                                Invoice Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Status & Amounts -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Status</Label
                                    >
                                    <Badge
                                        :class="getStatusColor(props.invoice.status)"
                                        class="capitalize"
                                    >
                                        {{ props.invoice.status.replace('_', ' ') }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Total Amount</Label
                                    >
                                    <p class="text-lg font-semibold flex items-center gap-2">
                                        <DollarSign class="h-5 w-5" />
                                        {{ formatCurrency(props.invoice.amount) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Payment Details -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Amount Paid</Label
                                    >
                                    <p class="text-lg font-semibold text-green-600">
                                        {{ formatCurrency(props.invoice.amount_paid) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Balance Due</Label
                                    >
                                    <p class="text-lg font-semibold text-orange-600">
                                        {{ formatCurrency(props.invoice.balance) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Payment Progress</Label
                                    >
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-green-600 h-2 rounded-full" 
                                            :style="{ width: `${(props.invoice.amount_paid / props.invoice.amount) * 100}%` }"
                                        ></div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ Math.round((props.invoice.amount_paid / props.invoice.amount) * 100) }}% paid
                                    </p>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Issue Date</Label
                                    >
                                    <p class="text-sm flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.invoice.issue_date) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Due Date</Label
                                    >
                                    <p class="text-sm flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.invoice.due_date) }}
                                        <span 
                                            v-if="getDaysRemaining(props.invoice.due_date) < 0 && props.invoice.status !== 'paid'" 
                                            class="text-xs text-red-600 font-medium"
                                        >
                                            (Overdue)
                                        </span>
                                        <span 
                                            v-else-if="getDaysRemaining(props.invoice.due_date) >= 0 && props.invoice.status !== 'paid'" 
                                            class="text-xs text-muted-foreground"
                                        >
                                            ({{ getDaysRemaining(props.invoice.due_date) }} days left)
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Paid Date -->
                                <div 
                                    v-if="props.invoice.paid_at" 
                                    class="space-y-1"
                                >
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Paid Date</Label
                                    >
                                    <p class="text-sm">
                                        {{ formatDate(props.invoice.paid_at) }}
                                    </p>
                                </div>

                            <!-- Client Information -->
                            <div v-if="props.invoice.client" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Client</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Building class="h-4 w-4 text-muted-foreground" />
                                    <Link
                                        :href="`/clients/${props.invoice.client.id}`"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.invoice.client.name }}
                                        <span v-if="props.invoice.client.company"> - {{ props.invoice.client.company }}</span>
                                    </Link>
                                </div>
                                <p v-if="props.invoice.client.email" class="text-xs text-muted-foreground ml-6">
                                    {{ props.invoice.client.email }}
                                </p>
                            </div>

                            <!-- Project Information -->
                            <div v-if="props.invoice.project" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Project</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Building class="h-4 w-4 text-muted-foreground" />
                                    <Link
                                        :href="`/projects/${props.invoice.project.id}`"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.invoice.project.name }}
                                    </Link>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="props.invoice.notes" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Notes</Label
                                >
                                <p class="text-sm whitespace-pre-wrap bg-muted/5 p-3 rounded-lg">
                                    {{ props.invoice.notes }}
                                </p>
                            </div>

                            <!-- Assignment Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Created By</Label
                                    >
                                    <p class="text-sm">{{ props.invoice.creator?.name || 'N/A' }}</p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">{{ formatDate(props.invoice.updated_at) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Activity & Statistics -->
                <div class="space-y-6">
                    <!-- Activity Log -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="h-5 w-5" />
                                Activity Log
                                <Badge variant="secondary" class="ml-2">
                                    {{ props.activities.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="props.activities.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="activity in props.activities"
                                    :key="activity.id"
                                    class="flex gap-3 border-b pb-4 last:border-b-0 last:pb-0"
                                >
                                    <div
                                        class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-primary"
                                    ></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-foreground">
                                            {{ activity.description }}
                                        </p>
                                        <div
                                            class="mt-1 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                By
                                                {{
                                                    activity.causer?.name ||
                                                    'System'
                                                }}
                                            </span>
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    formatDate(
                                                        activity.created_at,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-8 text-center">
                                <Activity
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground"
                                />
                                <p class="text-muted-foreground">
                                    No activity yet
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Activity will appear here as changes are
                                    made
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Invoice Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Invoice Statistics</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div
                                                class="cursor-default rounded-lg border bg-muted/5 p-4 text-center"
                                            >
                                                <div
                                                    class="text-2xl font-bold text-primary"
                                                >
                                                    {{ props.activities.length }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Activities
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total activities for this invoice</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div
                                                class="cursor-default rounded-lg border bg-muted/5 p-4 text-center"
                                            >
                                                <div
                                                    class="text-2xl font-bold text-primary"
                                                >
                                                    {{ Math.round((props.invoice.amount_paid / props.invoice.amount) * 100) }}%
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Paid
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Payment completion percentage</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Financial Summary -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Financial Summary
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Total Amount</span
                                        >
                                        <span class="text-sm font-medium">{{ formatCurrency(props.invoice.amount) }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Amount Paid</span
                                        >
                                        <span class="text-sm font-medium text-green-600">{{ formatCurrency(props.invoice.amount_paid) }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Balance Due</span
                                        >
                                        <span class="text-sm font-medium text-orange-600">{{ formatCurrency(props.invoice.balance) }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <ConfirmationDialog
                :show="showDeleteDialog"
                title="Delete Invoice"
                :description="`Are you sure you want to delete invoice '${props.invoice.invoice_number}'? This action cannot be undone.`"
                confirm-text="Delete Invoice"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteInvoice"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
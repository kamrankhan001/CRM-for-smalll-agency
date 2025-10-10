<script setup lang="ts">
import { index, update } from '@/actions/App/Http/Controllers/InvoiceController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, DollarSign, Calendar, FileText } from 'lucide-vue-next';
import { reactive } from 'vue';

interface Client {
    id: number;
    name: string;
}

interface Project {
    id: number;
    name: string;
}

interface Invoice {
    id: number;
    title: string;
    amount: number;
    amount_paid: number;
    status: string;
    issue_date: string | null;
    due_date: string | null;
    notes: string | null;
    client_id: number | null;
    project_id: number | null;
    created_at: string;
}

interface Props {
    invoice: Invoice;
    clients: Client[];
    projects: Project[];
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const form = reactive({
    title: props.invoice.title,
    amount: props.invoice.amount,
    amount_paid: props.invoice.amount_paid,
    status: props.invoice.status as 'draft' | 'sent' | 'partially_paid' | 'paid' | 'cancelled',
    issue_date: props.invoice.issue_date || '',
    due_date: props.invoice.due_date || '',
    notes: props.invoice.notes || '',
    client_id: props.invoice.client_id,
    project_id: props.invoice.project_id,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: index.url() },
    { title: `Edit ${props.invoice.title}`, href: '#' },
];

function submit() {
    router.put(update.url(props.invoice.id), form);
}

// Check if form is valid for submit button
const isFormValid = () => {
    return form.title && form.amount && form.issue_date && form.due_date;
};
</script>

<template>
    <Head :title="`Edit ${props.invoice.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index.url()">
                        <Button variant="ghost" size="icon" class="h-9 w-9">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            Edit Invoice
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Update invoice information for {{ props.invoice.title }}
                        </p>
                    </div>
                </div>
                <Link :href="index.url()">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Invoices
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form Card -->
                <Card class="border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Save class="h-5 w-5" />
                            Invoice Information
                        </CardTitle>
                        <CardDescription>
                            Update invoice details and billing information
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Title Field -->
                            <div class="space-y-2">
                                <Label for="title">Invoice Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter invoice title"
                                    class="w-full"
                                    :class="props.errors.title ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="props.errors.title" class="text-sm text-destructive">
                                    {{ props.errors.title }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Amount Field -->
                                <div class="space-y-2">
                                    <Label for="amount">Total Amount ($)</Label>
                                    <Input
                                        id="amount"
                                        v-model="form.amount"
                                        type="number"
                                        placeholder="0.00"
                                        class="w-full"
                                        :class="props.errors.amount ? 'border-destructive' : ''"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                    <p v-if="props.errors.amount" class="text-sm text-destructive">
                                        {{ props.errors.amount }}
                                    </p>
                                </div>

                                <!-- Status Field -->
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full" :class="props.errors.status ? 'border-destructive' : ''">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Draft</SelectItem>
                                            <SelectItem value="sent">Sent</SelectItem>
                                            <SelectItem value="partially_paid">Partially Paid</SelectItem>
                                            <SelectItem value="paid">Paid</SelectItem>
                                            <SelectItem value="cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="props.errors.status" class="text-sm text-destructive">
                                        {{ props.errors.status }}
                                    </p>
                                </div>
                            </div>

                            <!-- Amount Paid Field (Conditional) -->
                            <div v-if="form.status === 'partially_paid' || form.status === 'paid'" class="space-y-2">
                                <Label for="amount_paid">Amount Paid ($)</Label>
                                <Input
                                    id="amount_paid"
                                    v-model="form.amount_paid"
                                    type="number"
                                    placeholder="0.00"
                                    class="w-full"
                                    :class="props.errors.amount_paid ? 'border-destructive' : ''"
                                    min="0"
                                    step="0.01"
                                    :max="form.amount"
                                />
                                <p v-if="props.errors.amount_paid" class="text-sm text-destructive">
                                    {{ props.errors.amount_paid }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Enter the amount that has been paid so far
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Issue Date Field -->
                                <div class="space-y-2">
                                    <Label for="issue_date">Issue Date</Label>
                                    <Input
                                        id="issue_date"
                                        v-model="form.issue_date"
                                        type="date"
                                        class="w-full"
                                        :class="props.errors.issue_date ? 'border-destructive' : ''"
                                        required
                                    />
                                    <p v-if="props.errors.issue_date" class="text-sm text-destructive">
                                        {{ props.errors.issue_date }}
                                    </p>
                                </div>

                                <!-- Due Date Field -->
                                <div class="space-y-2">
                                    <Label for="due_date">Due Date</Label>
                                    <Input
                                        id="due_date"
                                        v-model="form.due_date"
                                        type="date"
                                        class="w-full"
                                        :class="props.errors.due_date ? 'border-destructive' : ''"
                                        required
                                    />
                                    <p v-if="props.errors.due_date" class="text-sm text-destructive">
                                        {{ props.errors.due_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Linked Entities Section -->
                            <div class="space-y-4">
                                <Label>Link To</Label>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Client Selection -->
                                    <div class="space-y-2">
                                        <Label for="client_id">Client</Label>
                                        <Select v-model="form.client_id">
                                            <SelectTrigger class="w-full" :class="props.errors.client_id ? 'border-destructive' : ''">
                                                <SelectValue placeholder="Select client" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null">No Client</SelectItem>
                                                <SelectItem
                                                    v-for="client in clients"
                                                    :key="client.id"
                                                    :value="client.id"
                                                >
                                                    {{ client.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="props.errors.client_id" class="text-sm text-destructive">
                                            {{ props.errors.client_id }}
                                        </p>
                                    </div>

                                    <!-- Project Selection -->
                                    <div class="space-y-2">
                                        <Label for="project_id">Project</Label>
                                        <Select v-model="form.project_id">
                                            <SelectTrigger class="w-full" :class="props.errors.project_id ? 'border-destructive' : ''">
                                                <SelectValue placeholder="Select project" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null">No Project</SelectItem>
                                                <SelectItem
                                                    v-for="project in projects"
                                                    :key="project.id"
                                                    :value="project.id"
                                                >
                                                    {{ project.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="props.errors.project_id" class="text-sm text-destructive">
                                            {{ props.errors.project_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Field -->
                            <div class="space-y-2">
                                <Label for="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Add any additional notes or terms..."
                                    class="min-h-[100px] w-full"
                                    :class="props.errors.notes ? 'border-destructive' : ''"
                                />
                                <p v-if="props.errors.notes" class="text-sm text-destructive">
                                    {{ props.errors.notes }}
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="inline-block flex-1">
                                                <Button
                                                    type="submit"
                                                    class="w-full gap-2"
                                                    :disabled="!isFormValid()"
                                                >
                                                    <Save class="h-4 w-4" />
                                                    Save Changes
                                                </Button>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent v-if="!isFormValid()">
                                            <div class="space-y-1">
                                                <p v-if="!form.title" class="text-sm">Title is required</p>
                                                <p v-else-if="!form.amount" class="text-sm">Amount is required</p>
                                                <p v-else-if="!form.issue_date" class="text-sm">Issue Date is required</p>
                                                <p v-else-if="!form.due_date" class="text-sm">Due Date is required</p>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <Link :href="index.url()" class="flex-1">
                                    <Button variant="outline" class="w-full">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Sidebar Information -->
                <div class="space-y-6">
                    <!-- Invoice Summary Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Invoice Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div class="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <DollarSign class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ props.invoice.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Created {{ new Date(props.invoice.created_at).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Current Status:</span>
                                    <span class="font-medium capitalize">
                                        {{ props.invoice.status.replace('_', ' ') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Total Amount:</span>
                                    <span class="font-medium">
                                        ${{ props.invoice.amount.toLocaleString() }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Amount Paid:</span>
                                    <span class="font-medium">
                                        ${{ props.invoice.amount_paid.toLocaleString() }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Balance Due:</span>
                                    <span class="font-medium">
                                        ${{ (props.invoice.amount - props.invoice.amount_paid).toLocaleString() }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Linked Client:</span>
                                    <span class="font-medium">
                                        {{ clients.find(c => c.id === props.invoice.client_id)?.name || 'Not linked' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Linked Project:</span>
                                    <span class="font-medium">
                                        {{ projects.find(p => p.id === props.invoice.project_id)?.name || 'Not linked' }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Update Tips Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Update Tips</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <DollarSign class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Update payment status as payments are received
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Calendar class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Adjust dates if payment terms change
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <FileText class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Update notes for any payment instructions or terms
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
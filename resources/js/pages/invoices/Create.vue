<script setup lang="ts">
import { index, store } from '@/actions/App/Http/Controllers/InvoiceController';
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
import {
    ArrowLeft,
    Calendar,
    DollarSign,
    FileText,
    Plus,
} from 'lucide-vue-next';
import { reactive } from 'vue';

interface Client {
    id: number;
    name: string;
}

interface Project {
    id: number;
    name: string;
}

interface Props {
    clients: Client[];
    projects: Project[];
    auth: {
        user: {
            id: number;
            name: string;
        };
    };
    errors: Record<string, string>;
}

defineProps<Props>();

const form = reactive({
    title: '',
    amount: '' as string | number,
    amount_paid: '' as string | number,
    status: 'draft' as
        | 'draft'
        | 'sent'
        | 'partially_paid'
        | 'paid'
        | 'cancelled',
    issue_date: '',
    due_date: '',
    notes: '',
    client_id: null as number | null,
    project_id: null as number | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: index.url() },
    { title: 'Create Invoice', href: '#' },
];

function submit() {
    const submitData = {
        ...form,
        amount: parseFloat(form.amount as string) || 0,
        amount_paid: form.amount_paid
            ? parseFloat(form.amount_paid as string)
            : 0,
    };
    router.post(store.url(), submitData);
}

// Check if form is valid for submit button
const isFormValid = () => {
    return form.title && form.amount && form.issue_date && form.due_date;
};
</script>

<template>
    <Head title="Create Invoice" />
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
                            Create New Invoice
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Create a new invoice for clients or projects
                        </p>
                    </div>
                </div>
                <!-- Hide on small devices, show on medium and above -->
                <Link :href="index.url()" class="hidden md:block">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Invoices
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form Card -->
                <Card class="self-start border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            Invoice Information
                        </CardTitle>
                        <CardDescription>
                            Enter invoice details and billing information
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
                                    :class="
                                        errors.title ? 'border-destructive' : ''
                                    "
                                    required
                                />
                                <p
                                    v-if="errors.title"
                                    class="text-sm text-destructive"
                                >
                                    {{ errors.title }}
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
                                        :class="
                                            errors.amount
                                                ? 'border-destructive'
                                                : ''
                                        "
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                    <p
                                        v-if="errors.amount"
                                        class="text-sm text-destructive"
                                    >
                                        {{ errors.amount }}
                                    </p>
                                </div>

                                <!-- Status Field -->
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger
                                            class="w-full"
                                            :class="
                                                errors.status
                                                    ? 'border-destructive'
                                                    : ''
                                            "
                                        >
                                            <SelectValue
                                                placeholder="Select status"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft"
                                                >Draft</SelectItem
                                            >
                                            <SelectItem value="sent"
                                                >Sent</SelectItem
                                            >
                                            <SelectItem value="partially_paid"
                                                >Partially Paid</SelectItem
                                            >
                                            <SelectItem value="paid"
                                                >Paid</SelectItem
                                            >
                                            <SelectItem value="cancelled"
                                                >Cancelled</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <p
                                        v-if="errors.status"
                                        class="text-sm text-destructive"
                                    >
                                        {{ errors.status }}
                                    </p>
                                </div>
                            </div>

                            <!-- Amount Paid Field (Conditional) -->
                            <div
                                v-if="form.status === 'partially_paid'"
                                class="space-y-2"
                            >
                                <Label for="amount_paid">Amount Paid ($)</Label>
                                <Input
                                    id="amount_paid"
                                    v-model="form.amount_paid"
                                    type="number"
                                    placeholder="0.00"
                                    class="w-full"
                                    :class="
                                        errors.amount_paid
                                            ? 'border-destructive'
                                            : ''
                                    "
                                    min="0"
                                    step="0.01"
                                    :max="form.amount"
                                />
                                <p
                                    v-if="errors.amount_paid"
                                    class="text-sm text-destructive"
                                >
                                    {{ errors.amount_paid }}
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
                                        :class="
                                            errors.issue_date
                                                ? 'border-destructive'
                                                : ''
                                        "
                                        required
                                    />
                                    <p
                                        v-if="errors.issue_date"
                                        class="text-sm text-destructive"
                                    >
                                        {{ errors.issue_date }}
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
                                        :class="
                                            errors.due_date
                                                ? 'border-destructive'
                                                : ''
                                        "
                                        required
                                    />
                                    <p
                                        v-if="errors.due_date"
                                        class="text-sm text-destructive"
                                    >
                                        {{ errors.due_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Linked Entities Section -->
                            <div class="space-y-4">
                                <Label>Link To</Label>
                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <!-- Client Selection -->
                                    <div class="space-y-2">
                                        <Label for="client_id">Client</Label>
                                        <Select v-model="form.client_id">
                                            <SelectTrigger
                                                class="w-full"
                                                :class="
                                                    errors.client_id
                                                        ? 'border-destructive'
                                                        : ''
                                                "
                                            >
                                                <SelectValue
                                                    placeholder="Select client"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null"
                                                    >No Client</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="client in clients"
                                                    :key="client.id"
                                                    :value="client.id"
                                                >
                                                    {{ client.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            v-if="errors.client_id"
                                            class="text-sm text-destructive"
                                        >
                                            {{ errors.client_id }}
                                        </p>
                                    </div>

                                    <!-- Project Selection -->
                                    <div class="space-y-2">
                                        <Label for="project_id">Project</Label>
                                        <Select v-model="form.project_id">
                                            <SelectTrigger
                                                class="w-full"
                                                :class="
                                                    errors.project_id
                                                        ? 'border-destructive'
                                                        : ''
                                                "
                                            >
                                                <SelectValue
                                                    placeholder="Select project"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null"
                                                    >No Project</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="project in projects"
                                                    :key="project.id"
                                                    :value="project.id"
                                                >
                                                    {{ project.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            v-if="errors.project_id"
                                            class="text-sm text-destructive"
                                        >
                                            {{ errors.project_id }}
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
                                    :class="
                                        errors.notes ? 'border-destructive' : ''
                                    "
                                />
                                <p
                                    v-if="errors.notes"
                                    class="text-sm text-destructive"
                                >
                                    {{ errors.notes }}
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
                                                    <Plus class="h-4 w-4" />
                                                    Create Invoice
                                                </Button>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent v-if="!isFormValid()">
                                            <div class="space-y-1">
                                                <p
                                                    v-if="!form.title"
                                                    class="text-sm"
                                                >
                                                    Title is required
                                                </p>
                                                <p
                                                    v-else-if="!form.amount"
                                                    class="text-sm"
                                                >
                                                    Amount is required
                                                </p>
                                                <p
                                                    v-else-if="!form.issue_date"
                                                    class="text-sm"
                                                >
                                                    Issue Date is required
                                                </p>
                                                <p
                                                    v-else-if="!form.due_date"
                                                    class="text-sm"
                                                >
                                                    Due Date is required
                                                </p>
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
                    <!-- Invoice Types Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Invoice Status</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <!-- Draft -->
                            <div
                                class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-gray-400"
                                    ></div>
                                    <span class="font-medium text-gray-700"
                                        >Draft</span
                                    >
                                </div>
                                <p class="text-gray-600">
                                    Invoice is in preparation and not yet sent
                                    to the client.
                                </p>
                            </div>

                            <!-- Sent -->
                            <div
                                class="space-y-2 rounded-lg border border-blue-200 bg-blue-50 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-blue-500"
                                    ></div>
                                    <span class="font-medium text-blue-700"
                                        >Sent</span
                                    >
                                </div>
                                <p class="text-blue-600">
                                    Invoice has been sent to the client and is
                                    awaiting payment.
                                </p>
                            </div>

                            <!-- Partially Paid -->
                            <div
                                class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-amber-500"
                                    ></div>
                                    <span class="font-medium text-amber-700"
                                        >Partially Paid</span
                                    >
                                </div>
                                <p class="text-amber-600">
                                    Client has made a partial payment towards
                                    the invoice.
                                </p>
                            </div>

                            <!-- Paid -->
                            <div
                                class="space-y-2 rounded-lg border border-green-200 bg-green-50 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-green-600"
                                    ></div>
                                    <span class="font-medium text-green-700"
                                        >Paid</span
                                    >
                                </div>
                                <p class="text-green-600">
                                    Invoice has been fully paid by the client.
                                </p>
                            </div>

                            <!-- Cancelled -->
                            <div
                                class="space-y-2 rounded-lg border border-red-200 bg-red-50 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-red-600"
                                    ></div>
                                    <span class="font-medium text-red-700"
                                        >Cancelled</span
                                    >
                                </div>
                                <p class="text-red-600">
                                    Invoice has been cancelled and is no longer
                                    valid.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tips Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Invoice Tips</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <DollarSign
                                    class="mt-0.5 h-4 w-4 text-primary"
                                />
                                <p class="text-muted-foreground">
                                    Set clear due dates to ensure timely
                                    payments
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Calendar class="mt-0.5 h-4 w-4 text-primary" />
                                <p class="text-muted-foreground">
                                    Link invoices to clients or projects for
                                    better tracking
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <FileText class="mt-0.5 h-4 w-4 text-primary" />
                                <p class="text-muted-foreground">
                                    Add notes for any special terms or
                                    instructions
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

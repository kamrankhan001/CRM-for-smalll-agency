<script setup lang="ts">
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/DocumentController';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, FilePlus, Upload } from 'lucide-vue-next';
import { reactive } from 'vue';

interface Lead {
    id: number;
    name: string;
}
interface Client {
    id: number;
    name: string;
}

const props = defineProps<{
    leads: Lead[];
    clients: Client[];
    auth: {
        user: {
            id: number;
            name: string;
        };
    };
}>();

const form = reactive({
    title: '',
    type: '' as 'proposal' | 'contract' | 'invoice' | '',
    documentable_type: 'lead' as 'lead' | 'client',
    documentable_id: null as number | null,
    file: null as File | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: index.url() },
    { title: 'Upload Document', href: '#' },
];

function submit() {
    const data = new FormData();
    data.append('title', form.title);
    data.append('type', form.type);
    data.append('documentable_type', form.documentable_type);
    if (form.documentable_id)
        data.append('documentable_id', form.documentable_id.toString());
    if (form.file) data.append('file', form.file);

    router.post(store.url(), data, { forceFormData: true });
}
</script>

<template>
    <Head title="Upload Document" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index.url()">
                        <Button variant="ghost" size="icon" class="h-9 w-9">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            Upload Document
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Attach a file to a Lead or Client
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Form -->
                <Card class="border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FilePlus class="h-5 w-5" />
                            Document Details
                        </CardTitle>
                        <CardDescription
                            >Provide basic details and upload your
                            file</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Title -->
                            <div class="space-y-2">
                                <Label for="title">Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter document title"
                                    required
                                />
                            </div>
    
                            <!-- Type -->
                            <div class="space-y-2">
                                <Label for="type">Type</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="Select document type"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="proposal"
                                            >Proposal</SelectItem
                                        >
                                        <SelectItem value="contract"
                                            >Contract</SelectItem
                                        >
                                        <SelectItem value="invoice"
                                            >Invoice</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
    
                            <!-- Linked Entity -->
                            <div class="space-y-4">
                                <Label>Link To</Label>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label>Entity Type</Label>
                                        <Select v-model="form.documentable_type">
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select type"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="lead"
                                                    >Lead
                                                </SelectItem>
                                                <SelectItem value="client"
                                                    >Client
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
    
                                    <div class="space-y-2">
                                        <Label>
                                            {{
                                                form.documentable_type === 'lead'
                                                    ? 'Select Lead'
                                                    : 'Select Client'
                                            }}
                                        </Label>
                                        <Select v-model="form.documentable_id">
                                            <SelectTrigger>
                                                <SelectValue
                                                    :placeholder="
                                                        form.documentable_type ===
                                                        'lead'
                                                            ? 'Choose a lead'
                                                            : 'Choose a client'
                                                    "
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <template
                                                    v-if="
                                                        form.documentable_type ===
                                                        'lead'
                                                    "
                                                >
                                                    <SelectItem
                                                        v-for="lead in props.leads"
                                                        :key="lead.id"
                                                        :value="lead.id"
                                                    >
                                                        {{ lead.name }}
                                                    </SelectItem>
                                                </template>
                                                <template v-else>
                                                    <SelectItem
                                                        v-for="client in props.clients"
                                                        :key="client.id"
                                                        :value="client.id"
                                                    >
                                                        {{ client.name }}
                                                    </SelectItem>
                                                </template>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>
    
                            <!-- File Upload -->
                            <div class="space-y-2">
                                <Label for="file">File</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    @change="
                                        (e: any) => (form.file = e.target.files[0])
                                    "
                                    accept=".pdf,.doc,.docx,.jpg,.png"
                                    required
                                />
                            </div>
    
                            <!-- Actions -->
                            <div class="flex gap-3 pt-4">
                                <Button
                                    type="submit"
                                    class="flex-1 gap-2"
                                    :disabled="!form.title || !form.file"
                                >
                                    <Upload class="h-4 w-4" />
                                    Upload
                                </Button>
                                <Link :href="index.url()" class="flex-1">
                                    <Button variant="outline" class="w-full"
                                        >Cancel</Button
                                    >
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
    
                <div class="space-y-6">
                    <!-- Document Types Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Document Types</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <!-- Proposal -->
                            <div
                                class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-primary"
                                    ></div>
                                    <span class="font-medium text-primary"
                                        >Proposal</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Business proposals, quotes, and service
                                    offerings for potential clients.
                                </p>
                            </div>
    
                            <!-- Contract -->
                            <div
                                class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-secondary"
                                    ></div>
                                    <span class="font-medium text-secondary"
                                        >Contract</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Legal agreements, service contracts, and
                                    partnership documents.
                                </p>
                            </div>
    
                            <!-- Invoice -->
                            <div
                                class="space-y-2 rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-destructive"
                                    ></div>
                                    <span class="font-medium text-destructive"
                                        >Invoice</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Billing documents, payment requests, and
                                    financial statements.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

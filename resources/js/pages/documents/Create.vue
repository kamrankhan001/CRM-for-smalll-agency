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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
interface Project {
    id: number;
    name: string;
}

const props = defineProps<{
    leads: Lead[];
    clients: Client[];
    projects: Project[];
    types: string[];
    auth: {
        user: {
            id: number;
            name: string;
        };
    };
    errors: Record<string, string>;
}>();

const form = reactive({
    title: '',
    type: '' as string,
    documentable_type: 'lead' as 'lead' | 'client' | 'project',
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
                            Attach a file to a Lead, Client, or Project
                        </p>
                    </div>
                </div>
                <!-- Hide on small devices, show on medium and above -->
                <Link :href="index.url()" class="hidden md:block">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Documents
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Form -->
                <Card class="border lg:col-span-2 self-start">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FilePlus class="h-5 w-5" />
                            Document Details
                        </CardTitle>
                        <CardDescription>
                            Provide basic details and upload your file
                        </CardDescription>
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
                                    :class="errors.title ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="errors.title" class="text-sm text-destructive">
                                    {{ errors.title }}
                                </p>
                            </div>

                            <!-- Type -->
                            <div class="space-y-2">
                                <Label for="type">Type</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger class="w-full" :class="errors.type ? 'border-destructive' : ''">
                                        <SelectValue
                                            placeholder="Select document type"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in props.types"
                                            :key="type"
                                            :value="type"
                                        >
                                            {{
                                                type.charAt(0).toUpperCase() +
                                                type.slice(1)
                                            }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.type" class="text-sm text-destructive">
                                    {{ errors.type }}
                                </p>
                            </div>

                            <!-- Linked Entity -->
                            <div class="space-y-4">
                                <Label>Link To</Label>
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="space-y-2">
                                        <Label>Entity Type</Label>
                                        <Select
                                            v-model="form.documentable_type"
                                        >
                                            <SelectTrigger :class="errors.documentable_type ? 'border-destructive' : ''">
                                                <SelectValue
                                                    placeholder="Select type"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="lead">
                                                    Lead
                                                </SelectItem>
                                                <SelectItem value="client">
                                                    Client
                                                </SelectItem>
                                                <SelectItem value="project">
                                                    Project
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="errors.documentable_type" class="text-sm text-destructive">
                                            {{ errors.documentable_type }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label>
                                            {{
                                                form.documentable_type ===
                                                'lead'
                                                    ? 'Select Lead'
                                                    : form.documentable_type ===
                                                        'client'
                                                      ? 'Select Client'
                                                      : 'Select Project'
                                            }}
                                        </Label>
                                        <Select v-model="form.documentable_id">
                                            <SelectTrigger :class="errors.documentable_id ? 'border-destructive' : ''">
                                                <SelectValue
                                                    :placeholder="
                                                        form.documentable_type ===
                                                        'lead'
                                                            ? 'Choose a lead'
                                                            : form.documentable_type ===
                                                                'client'
                                                              ? 'Choose a client'
                                                              : 'Choose a project'
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
                                                <template
                                                    v-else-if="
                                                        form.documentable_type ===
                                                        'client'
                                                    "
                                                >
                                                    <SelectItem
                                                        v-for="client in props.clients"
                                                        :key="client.id"
                                                        :value="client.id"
                                                    >
                                                        {{ client.name }}
                                                    </SelectItem>
                                                </template>
                                                <template v-else>
                                                    <SelectItem
                                                        v-for="project in props.projects"
                                                        :key="project.id"
                                                        :value="project.id"
                                                    >
                                                        {{ project.name }}
                                                    </SelectItem>
                                                </template>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="errors.documentable_id" class="text-sm text-destructive">
                                            {{ errors.documentable_id }}
                                        </p>
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
                                        (e: any) =>
                                            (form.file = e.target.files[0])
                                    "
                                    accept=".pdf,.doc,.docx,.jpg,.png"
                                    :class="errors.file ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="errors.file" class="text-sm text-destructive">
                                    {{ errors.file }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-4">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="inline-block flex-1">
                                                <Button
                                                    type="submit"
                                                    class="w-full gap-2"
                                                    :disabled="!form.title || !form.file"
                                                >
                                                    <Upload class="h-4 w-4" />
                                                    Upload
                                                </Button>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent v-if="!form.title || !form.file">
                                            <p v-if="!form.title">Title is required</p>
                                            <p v-else-if="!form.file">File is required</p>
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

                <!-- Side Info -->
                <div class="space-y-6">
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Document Types</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <template v-for="type in props.types" :key="type">
                                <div
                                    :class="[
                                        'space-y-2 rounded-lg border p-4',
                                        type === 'proposal'
                                            ? 'border-primary/20 bg-primary/5'
                                            : type === 'contract'
                                              ? 'border-secondary/20 bg-secondary/5'
                                              : type === 'invoice'
                                                ? 'border-destructive/20 bg-destructive/5'
                                                : type === 'report'
                                                  ? 'border-blue-400/20 bg-blue-400/5'
                                                  : type === 'brief'
                                                    ? 'border-amber-400/20 bg-amber-400/5'
                                                    : 'border-muted/20 bg-muted/5',
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'h-2.5 w-2.5 rounded-sm',
                                                type === 'proposal'
                                                    ? 'bg-primary'
                                                    : type === 'contract'
                                                      ? 'bg-secondary'
                                                      : type === 'invoice'
                                                        ? 'bg-destructive'
                                                        : type === 'report'
                                                          ? 'bg-blue-400'
                                                          : type === 'brief'
                                                            ? 'bg-amber-400'
                                                            : 'bg-muted',
                                            ]"
                                        ></div>
                                        <span
                                            :class="[
                                                'font-medium',
                                                type === 'proposal'
                                                    ? 'text-primary'
                                                    : type === 'contract'
                                                      ? 'text-secondary'
                                                      : type === 'invoice'
                                                        ? 'text-destructive'
                                                        : type === 'report'
                                                          ? 'text-blue-500'
                                                          : type === 'brief'
                                                            ? 'text-amber-500'
                                                            : 'text-muted-foreground',
                                            ]"
                                        >
                                            {{
                                                type.charAt(0).toUpperCase() +
                                                type.slice(1)
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground">
                                        {{
                                            type === 'proposal'
                                                ? 'Business proposals and quotes.'
                                                : type === 'contract'
                                                  ? 'Legal agreements and contracts.'
                                                  : type === 'invoice'
                                                    ? 'Billing and financial documents.'
                                                    : type === 'report'
                                                      ? 'Progress or performance reports.'
                                                      : type === 'brief'
                                                        ? 'Summary briefs or outlines.'
                                                        : 'Miscellaneous documents.'
                                        }}
                                    </p>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
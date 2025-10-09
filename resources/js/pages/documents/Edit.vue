<script setup lang="ts">
import {
    index,
    update,
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
import { ArrowLeft, Save } from 'lucide-vue-next';
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
interface Document {
    id: number;
    title: string;
    type: string;
    documentable_type: string;
    documentable_id: number;
    documentable?: Lead | Client | Project | null;
    file_path: string;
}

interface Props {
    document: Document;
    leads: Lead[];
    clients: Client[];
    projects: Project[];
    types: string[];
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const form = reactive({
    title: props.document.title,
    type: props.document.type as string,
    documentable_type: props.document.documentable_type.includes('Lead')
        ? 'lead'
        : props.document.documentable_type.includes('Project')
          ? 'project'
          : 'client',
    documentable_id: props.document.documentable_id,
    file: null as File | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: index.url() },
    { title: `Edit ${props.document.title}`, href: '#' },
];

function submit() {
    const data = new FormData();
    data.append('title', form.title);
    data.append('type', form.type);
    data.append('documentable_type', form.documentable_type);
    if (form.documentable_id)
        data.append('documentable_id', form.documentable_id.toString());
    if (form.file) data.append('file', form.file);
    data.append('_method', 'PUT');

    router.post(update.url(props.document.id), data, { forceFormData: true });
}
</script>

<template>
    <Head :title="`Edit ${props.document.title}`" />
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
                            Edit Document
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Modify document details or replace file
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
                <!-- Main Form Card -->
                <Card class="border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Save class="h-5 w-5" />
                            Document Information
                        </CardTitle>
                        <CardDescription
                            >Update document details</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Title Field -->
                            <div class="space-y-2">
                                <Label for="title">Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter document title"
                                    class="w-full"
                                    :class="props.errors.title ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="props.errors.title" class="text-sm text-destructive">
                                    {{ props.errors.title }}
                                </p>
                            </div>

                            <!-- Type Field -->
                            <div class="space-y-2">
                                <Label for="type">Type</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger class="w-full" :class="props.errors.type ? 'border-destructive' : ''">
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
                                <p v-if="props.errors.type" class="text-sm text-destructive">
                                    {{ props.errors.type }}
                                </p>
                            </div>

                            <!-- Linked Entity Section -->
                            <div class="space-y-4">
                                <Label>Linked To</Label>
                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <!-- Entity Type -->
                                    <div class="space-y-2">
                                        <Label for="documentable_type"
                                            >Entity Type</Label
                                        >
                                        <Select
                                            v-model="form.documentable_type"
                                        >
                                            <SelectTrigger class="w-full" :class="props.errors.documentable_type ? 'border-destructive' : ''">
                                                <SelectValue
                                                    placeholder="Select type"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="lead"
                                                    >Lead</SelectItem
                                                >
                                                <SelectItem value="client"
                                                    >Client</SelectItem
                                                >
                                                <SelectItem value="project"
                                                    >Project</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                        <p v-if="props.errors.documentable_type" class="text-sm text-destructive">
                                            {{ props.errors.documentable_type }}
                                        </p>
                                    </div>

                                    <!-- Entity Selection -->
                                    <div class="space-y-2">
                                        <Label
                                            v-if="form.documentable_type === 'lead'"
                                            for="documentable_id"
                                            >Select Lead</Label
                                        >
                                        <Label
                                            v-else-if="
                                                form.documentable_type === 'client'
                                            "
                                            for="documentable_id"
                                            >Select Client</Label
                                        >
                                        <Label v-else for="documentable_id"
                                            >Select Project</Label
                                        >
                                        <Select v-model="form.documentable_id">
                                            <SelectTrigger class="w-full" :class="props.errors.documentable_id ? 'border-destructive' : ''">
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
                                        <p v-if="props.errors.documentable_id" class="text-sm text-destructive">
                                            {{ props.errors.documentable_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- File Field -->
                            <div class="space-y-2">
                                <Label for="file"
                                    >Replace File (optional)</Label
                                >
                                <Input
                                    id="file"
                                    type="file"
                                    @change="
                                        (e: any) =>
                                            (form.file = e.target.files[0])
                                    "
                                    accept=".pdf,.doc,.docx,.jpg,.png"
                                    class="w-full"
                                    :class="props.errors.file ? 'border-destructive' : ''"
                                />
                                <p v-if="props.errors.file" class="text-sm text-destructive">
                                    {{ props.errors.file }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Current file:
                                    <a
                                        :href="props.document.file_path"
                                        class="text-primary hover:underline"
                                        target="_blank"
                                        >View</a
                                    >
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4">
                                <Button
                                    type="submit"
                                    class="flex-1 gap-2"
                                    :disabled="!form.title"
                                >
                                    <Save class="h-4 w-4" />
                                    Save Changes
                                </Button>
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
                    <!-- Document Summary Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Document Summary</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div
                                class="flex items-center gap-3 rounded-lg bg-muted/50 p-3"
                            >
                                <div
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                >
                                    <span
                                        class="text-sm font-medium text-primary"
                                    >
                                        {{
                                            props.document.title
                                                .split(' ')[0]
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <p class="line-clamp-2 font-medium">
                                        {{ props.document.title }}
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground capitalize"
                                    >
                                        {{ props.document.type }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Current Type:</span
                                    >
                                    <span class="font-medium capitalize">{{
                                        props.document.type
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Linked To:</span
                                    >
                                    <span class="font-medium">
                                        {{
                                            props.document.documentable?.name ||
                                            'Not linked'
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Entity Type:</span
                                    >
                                    <span class="font-medium capitalize">
                                        {{
                                        props.document.documentable_type.includes('Lead')
                                        ? 'Lead' :
                                        props.document.documentable_type.includes('Project')
                                        ? 'Project'
                                        : 'Client' }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- File Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >File Information</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground"
                                    >Current File:</span
                                >
                                <a
                                    :href="props.document.file_path"
                                    target="_blank"
                                    class="font-medium text-primary hover:underline"
                                >
                                    View File
                                </a>
                            </div>
                            <div class="border-t pt-2">
                                <p class="mb-2 text-muted-foreground">
                                    When replacing file:
                                </p>
                                <div class="flex items-start gap-2">
                                    <div
                                        class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                    ></div>
                                    <p class="text-muted-foreground">
                                        Old file will be permanently deleted
                                    </p>
                                </div>
                                <div class="flex items-start gap-2">
                                    <div
                                        class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                    ></div>
                                    <p class="text-muted-foreground">
                                        New file must meet format requirements
                                    </p>
                                </div>
                                <div class="flex items-start gap-2">
                                    <div
                                        class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                    ></div>
                                    <p class="text-muted-foreground">
                                        Document link will remain the same
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/DocumentController';
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
    AlertCircle,
    ArrowLeft,
    Calendar,
    Download,
    Edit,
    File,
    FileText,
    Trash2,
    User,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Documentable {
    id: number;
    name: string;
    type: string;
}

interface Activity {
    id: number;
    description: string;
    causer: User | null;
    created_at: string;
    properties?: any;
}

interface Document {
    id: number;
    title: string;
    type: string;
    file_path: string;
    file_url: string;
    documentable_type: string;
    documentable_id: number;
    uploaded_by: number;
    created_at: string;
    updated_at: string;
    uploader: User | null;
    documentable: Documentable | null;
}

interface Props {
    document: Document;
    activities: Activity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: index.url() },
    { title: props.document.title, href: '#' },
];

const showDeleteDialog = ref(false);

function getTypeColor(type: string) {
    const colors = {
        proposal: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        contract: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        invoice: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        report: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        brief: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
        misc: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };
    return (
        colors[type as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function formatDate(date: string | null) {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getFileExtension(filePath: string) {
    return filePath.split('.').pop()?.toUpperCase() || 'FILE';
}

function getFileIcon(type: string) {
    const extensions: Record<string, string> = {
        pdf: 'PDF',
        doc: 'DOC',
        docx: 'DOC',
        png: 'IMG',
        jpg: 'IMG',
        jpeg: 'IMG',
    };
    return extensions[type] || 'FILE';
}

function getDocumentableRoute(documentable: Documentable | null) {
    if (!documentable) return '#';
    const type = documentable.type.toLowerCase();
    return `/${type}s/${documentable.id}`;
}

function downloadDocument() {
    window.open(props.document.file_url, '_blank');
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteDocument() {
    router.delete(destroy.url(props.document.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="props.document.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="mb-4 flex items-center gap-4">
                    <Link :href="index.url()">
                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1
                            class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            {{ props.document.title }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Document uploaded {{ formatDate(props.document.created_at) }}
                            <span v-if="props.document.documentable" class="ml-2">
                                • Related to
                                <Link
                                    :href="
                                        getDocumentableRoute(props.document.documentable)
                                    "
                                    class="text-primary hover:underline"
                                >
                                    {{ props.document.documentable.name }}
                                </Link>
                            </span>
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
                                        @click="downloadDocument"
                                    >
                                        <Download class="h-4 w-4" />
                                        <span class="hidden lg:inline"
                                            >Download</span
                                        >
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent class="lg:hidden">
                                    <p>Download this document</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.document.id)">
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
                                    <p>Edit document information</p>
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
                                    <p>Delete this document</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Document Info & File -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Document Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Document Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Document Type & File Info -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Document Type</Label
                                    >
                                    <Badge
                                        :class="getTypeColor(props.document.type)"
                                        class="capitalize"
                                    >
                                        {{ props.document.type }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >File Type</Label
                                    >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10"
                                        >
                                            <span
                                                class="text-xs font-medium text-primary"
                                            >
                                                {{
                                                    getFileIcon(
                                                        getFileExtension(
                                                            props.document.file_path,
                                                        ),
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <span class="text-sm capitalize">
                                            {{
                                                getFileExtension(
                                                    props.document.file_path,
                                                )
                                            }}
                                            file
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- File Preview & Download -->
                            <div class="space-y-3">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >File</Label
                                >
                                <div
                                    class="flex items-center justify-between rounded-lg border bg-muted/5 p-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10"
                                        >
                                            <File class="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">
                                                {{ props.document.title }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    getFileExtension(
                                                        props.document.file_path,
                                                    )
                                                }}
                                                •
                                                {{
                                                    formatDate(
                                                        props.document.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="downloadDocument"
                                    >
                                        <Download class="h-4 w-4" />
                                        Download
                                    </Button>
                                </div>
                            </div>

                            <!-- Uploaded By -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Uploaded By</Label
                                >
                                <div
                                    v-if="props.document.uploader"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                    >
                                        <span
                                            class="text-sm font-medium text-primary"
                                        >
                                            {{
                                                props.document.uploader.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ props.document.uploader.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ props.document.uploader.email }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    Unknown uploader
                                </p>
                            </div>

                            <!-- Related Entity -->
                            <div v-if="props.document.documentable" class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Related To</Label
                                >
                                <div class="flex items-center gap-2">
                                    <AlertCircle
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Link
                                        :href="
                                            getDocumentableRoute(
                                                props.document.documentable,
                                            )
                                        "
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.document.documentable.name }} ({{
                                            props.document.documentable.type
                                        }})
                                    </Link>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Uploaded</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.document.created_at) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">
                                        {{ formatDate(props.document.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Activity -->
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

                    <!-- Document Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Document Statistics</CardTitle>
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
                                            <p>Total activities for this document</p>
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
                                                    {{
                                                        getFileExtension(
                                                            props.document.file_path,
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    File Type
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Document file format</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Document Information -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Document Info
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Type</span
                                        >
                                        <span class="text-sm font-medium capitalize">
                                            {{ props.document.type }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Uploaded</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{ formatDate(props.document.created_at) }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Last Updated</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{ formatDate(props.document.updated_at) }}
                                        </span>
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
                title="Delete Document"
                :description="`Are you sure you want to delete '${props.document.title}'? This action cannot be undone and the file will be permanently removed.`"
                confirm-text="Delete Document"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteDocument"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
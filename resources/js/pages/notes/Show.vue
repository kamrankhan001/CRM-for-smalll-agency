<script setup lang="ts">
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/NoteController';
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
    ClipboardList,
    Edit,
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

interface Noteable {
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

interface Note {
    id: number;
    content: string;
    user_id: number;
    noteable_type: string;
    noteable_id: number;
    created_at: string;
    updated_at: string;
    user: User | null;
    noteable: Noteable | null;
}

interface Props {
    note: Note;
    activities: Activity[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notes', href: index.url() },
    { title: 'Note Details', href: '#' },
];

const showDeleteDialog = ref(false);

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

function getNoteableTypeColor(type: string) {
    const colors = {
        lead: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        client: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        project: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    };
    return (
        colors[type as keyof typeof colors] ||
        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    );
}

function getNoteableRoute(noteable: Noteable | null) {
    if (!noteable) return '#';
    const type = noteable.type.toLowerCase();
    return `/${type}s/${noteable.id}`;
}

function confirmDelete() {
    showDeleteDialog.value = true;
}

function deleteNote() {
    router.delete(destroy.url(props.note.id));
    showDeleteDialog.value = false;
}

function cancelDelete() {
    showDeleteDialog.value = false;
}
</script>

<template>
    <Head :title="'Note Details'" />
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
                            class="text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            Note Details
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            Created {{ formatDate(props.note.created_at) }}
                            <span v-if="props.note.noteable" class="ml-2">
                                • Related to
                                <Link
                                    :href="
                                        getNoteableRoute(props.note.noteable)
                                    "
                                    class="text-primary hover:underline"
                                >
                                    {{ props.note.noteable.name }}
                                </Link>
                            </span>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Edit Button -->
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Link :href="edit.url(props.note.id)">
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
                                    <p>Edit this note</p>
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
                                    <p>Delete this note</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column - Note Content & Information -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Note Content Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
                                Note Content
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="rounded-lg bg-muted/5 p-4">
                                    <p class="whitespace-pre-wrap text-sm leading-relaxed">
                                        {{ props.note.content }}
                                    </p>
                                </div>
                                
                                <!-- Content Statistics -->
                                <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                    <span>{{ props.note.content.length }} characters</span>
                                    <span>{{ props.note.content.split(/\s+/).length }} words</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Note Information Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Note Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Created By -->
                            <div class="space-y-1">
                                <Label
                                    class="text-sm font-medium text-muted-foreground"
                                    >Created By</Label
                                >
                                <div
                                    v-if="props.note.user"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                                    >
                                        <span
                                            class="text-sm font-medium text-primary"
                                        >
                                            {{
                                                props.note.user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ props.note.user.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ props.note.user.email }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">
                                    Unknown user
                                </p>
                            </div>

                            <!-- Related Entity -->
                            <div v-if="props.note.noteable" class="space-y-1">
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
                                            getNoteableRoute(props.note.noteable)
                                        "
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.note.noteable.name }}
                                    </Link>
                                    <Badge
                                        :class="
                                            getNoteableTypeColor(
                                                props.note.noteable.type,
                                            )
                                        "
                                        class="capitalize"
                                    >
                                        {{ props.note.noteable.type }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Timeline Information -->
                            <div
                                class="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2"
                            >
                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Created</Label
                                    >
                                    <p class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(props.note.created_at) }}
                                    </p>
                                </div>

                                <div class="space-y-1">
                                    <Label
                                        class="text-sm font-medium text-muted-foreground"
                                        >Last Updated</Label
                                    >
                                    <p class="text-sm">
                                        {{ formatDate(props.note.updated_at) }}
                                    </p>
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

                    <!-- Note Statistics -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle>Note Statistics</CardTitle>
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
                                            <p>Total activities for this note</p>
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
                                                        props.note.content.length
                                                    }}
                                                </div>
                                                <div
                                                    class="mt-1 text-sm text-muted-foreground"
                                                >
                                                    Characters
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Total characters in note content</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>

                            <!-- Content Information -->
                            <div class="border-t pt-4">
                                <h4 class="mb-3 text-sm font-medium">
                                    Content Info
                                </h4>
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Words</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{
                                                props.note.content.split(
                                                    /\s+/,
                                                ).length
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Characters</span
                                        >
                                        <span class="text-sm font-medium">
                                            {{ props.note.content.length }}
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
                                            {{ formatDate(props.note.updated_at) }}
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
                title="Delete Note"
                :description="`Are you sure you want to delete this note? This action cannot be undone.`"
                confirm-text="Delete Note"
                cancel-text="Cancel"
                variant="destructive"
                @confirm="deleteNote"
                @cancel="cancelDelete"
            />
        </div>
    </AppLayout>
</template>
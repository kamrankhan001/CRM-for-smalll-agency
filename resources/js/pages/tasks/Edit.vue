<script setup lang="ts">
import { index, update } from '@/actions/App/Http/Controllers/TaskController';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { reactive } from 'vue';

interface User {
    id: number;
    name: string;
}
interface Project {
    id: number;
    name: string;
}
interface Lead {
    id: number;
    name: string;
}
interface Client {
    id: number;
    name: string;
}
interface Task {
    id: number;
    title: string;
    description?: string;
    status: string;
    due_date?: string;
    taskable_type: string;
    taskable_id: number;
    assigned_to?: number;
    assignee?: User | null;
    taskable?: Lead | Client | Project | null;
    created_at: string;
}

interface Props {
    task: Task;
    users: User[];
    leads: Lead[];
    clients: Client[];
    projects: Project[];
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const form = reactive({
    title: props.task.title,
    description: props.task.description || '',
    status: props.task.status as 'pending' | 'in_progress' | 'completed',
    due_date: props.task.due_date || '',
    taskable_type: props.task.taskable_type.includes('Lead')
        ? 'lead'
        : props.task.taskable_type.includes('Project')
          ? 'project'
          : 'client',
    taskable_id: props.task.taskable_id,
    assigned_to: props.task.assigned_to || null,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tasks', href: index.url() },
    { title: `Edit ${props.task.title}`, href: '#' },
];

function submit() {
    router.put(update.url(props.task.id), form);
}
</script>

<template>
    <Head :title="`Edit ${props.task.title}`" />
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
                            Edit Task
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Update task information for {{ props.task.title }}
                        </p>
                    </div>
                </div>
                <!-- Hide on small devices, show on medium and above -->
                <Link :href="index.url()" class="hidden md:block">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Tasks
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form Card -->
                <Card class="border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Save class="h-5 w-5" />
                            Task Information
                        </CardTitle>
                        <CardDescription>
                            Update task details and modify assignment
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Title Field -->
                            <div class="space-y-2">
                                <Label for="title">Task Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter task title"
                                    class="w-full"
                                    :class="errors.title ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="errors.title" class="text-sm text-destructive">
                                    {{ errors.title }}
                                </p>
                            </div>

                            <!-- Description Field -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Enter task description and details..."
                                    class="min-h-[100px] w-full"
                                    :class="errors.description ? 'border-destructive' : ''"
                                />
                                <p v-if="errors.description" class="text-sm text-destructive">
                                    {{ errors.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Status Field -->
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full" :class="errors.status ? 'border-destructive' : ''">
                                            <SelectValue
                                                placeholder="Select status"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending"
                                                >Pending</SelectItem
                                            >
                                            <SelectItem value="in_progress"
                                                >In Progress</SelectItem
                                            >
                                            <SelectItem value="completed"
                                                >Completed</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <p v-if="errors.status" class="text-sm text-destructive">
                                        {{ errors.status }}
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
                                        :class="errors.due_date ? 'border-destructive' : ''"
                                    />
                                    <p v-if="errors.due_date" class="text-sm text-destructive">
                                        {{ errors.due_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Linked Entity Section -->
                            <div class="space-y-4">
                                <Label>Link To</Label>
                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <!-- Entity Type -->
                                    <div class="space-y-2">
                                        <Label for="taskable_type"
                                            >Entity Type</Label
                                        >
                                        <Select v-model="form.taskable_type">
                                            <SelectTrigger class="w-full" :class="errors.taskable_type ? 'border-destructive' : ''">
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
                                        <p v-if="errors.taskable_type" class="text-sm text-destructive">
                                            {{ errors.taskable_type }}
                                        </p>
                                    </div>

                                    <!-- Entity Selection -->
                                    <div class="space-y-2">
                                        <Label
                                            v-if="form.taskable_type === 'lead'"
                                            for="taskable_id"
                                            >Select Lead</Label
                                        >
                                        <Label
                                            v-else-if="
                                                form.taskable_type === 'client'
                                            "
                                            for="taskable_id"
                                            >Select Client</Label
                                        >
                                        <Label v-else for="taskable_id"
                                            >Select Project</Label
                                        >
                                        <Select v-model="form.taskable_id">
                                            <SelectTrigger class="w-full" :class="errors.taskable_id ? 'border-destructive' : ''">
                                                <SelectValue
                                                    :placeholder="
                                                        form.taskable_type ===
                                                        'lead'
                                                            ? 'Select a lead'
                                                            : form.taskable_type ===
                                                                'client'
                                                              ? 'Select a client'
                                                              : 'Select a project'
                                                    "
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <template
                                                    v-if="
                                                        form.taskable_type ===
                                                        'lead'
                                                    "
                                                >
                                                    <SelectItem
                                                        v-for="lead in leads"
                                                        :key="lead.id"
                                                        :value="lead.id"
                                                    >
                                                        {{ lead.name }}
                                                    </SelectItem>
                                                </template>
                                                <template
                                                    v-else-if="
                                                        form.taskable_type ===
                                                        'client'
                                                    "
                                                >
                                                    <SelectItem
                                                        v-for="client in clients"
                                                        :key="client.id"
                                                        :value="client.id"
                                                    >
                                                        {{ client.name }}
                                                    </SelectItem>
                                                </template>
                                                <template v-else>
                                                    <SelectItem
                                                        v-for="project in projects"
                                                        :key="project.id"
                                                        :value="project.id"
                                                    >
                                                        {{ project.name }}
                                                    </SelectItem>
                                                </template>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="errors.taskable_id" class="text-sm text-destructive">
                                            {{ errors.taskable_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned To Field -->
                            <div class="space-y-2">
                                <Label for="assigned_to">Assigned To</Label>
                                <Select v-model="form.assigned_to">
                                    <SelectTrigger class="w-full" :class="errors.assigned_to ? 'border-destructive' : ''">
                                        <SelectValue
                                            placeholder="Select user"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null"
                                            >Unassigned</SelectItem
                                        >
                                        <SelectItem
                                            v-for="user in users"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.assigned_to" class="text-sm text-destructive">
                                    {{ errors.assigned_to }}
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
                    <!-- Task Summary Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Task Summary</CardTitle>
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
                                            props.task.title
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium">
                                        {{ props.task.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Created
                                        {{
                                            new Date(
                                                props.task.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Current Status:</span
                                    >
                                    <span class="font-medium capitalize">
                                        {{
                                            props.task.status.replace('_', ' ')
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Current Assignee:</span
                                    >
                                    <span class="font-medium">
                                        {{
                                            props.task.assignee?.name ||
                                            'Unassigned'
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground"
                                        >Linked To:</span
                                    >
                                    <span class="font-medium">
                                        {{
                                            props.task.taskable?.name ||
                                            'Not linked'
                                        }}
                                        <span
                                            class="text-xs text-muted-foreground capitalize"
                                        >
                                            ({{
                                                props.task.taskable_type.includes(
                                                    'Lead',
                                                )
                                                    ? 'lead'
                                                    : props.task.taskable_type.includes(
                                                            'Project',
                                                        )
                                                      ? 'project'
                                                      : 'client'
                                            }})
                                        </span>
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
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Update status regularly to reflect current
                                    progress
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Adjust due dates if timelines change
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Reassign tasks if team member availability
                                    changes
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
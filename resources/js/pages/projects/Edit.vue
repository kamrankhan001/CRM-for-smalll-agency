<script setup lang="ts">
import { index, update } from '@/actions/App/Http/Controllers/ProjectController';
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
import { ArrowLeft, FolderKanban, Users, Calendar, Target, Building, Save } from 'lucide-vue-next';
import { reactive } from 'vue';

interface User {
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
interface Project {
    id: number;
    name: string;
    description?: string;
    status: string;
    start_date?: string;
    end_date?: string;
    client_id?: number | null;
    lead_id?: number | null;
    members: { id: number }[];
    creator?: User | null;
    client?: Client | null;
    lead?: Lead | null;
    created_at: string;
}

interface Props {
    project: Project;
    clients: Client[];
    leads: Lead[];
    users: User[];
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const form = reactive({
    name: props.project.name,
    description: props.project.description || '',
    status: props.project.status as 'planning' | 'in_progress' | 'on_hold' | 'completed',
    start_date: props.project.start_date || '',
    end_date: props.project.end_date || '',
    client_id: props.project.client_id || null,
    lead_id: props.project.lead_id || null,
    members: props.project.members.map(m => m.id),
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: index.url() },
    { title: `Edit ${props.project.name}`, href: '#' },
];

function submit() {
    router.put(update.url(props.project.id), form);
}
</script>

<template>
    <Head :title="`Edit ${props.project.name}`" />
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
                            Edit Project
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Update project information for {{ props.project.name }}
                        </p>
                    </div>
                </div>
                <!-- Hide on small devices, show on medium and above -->
                <Link :href="index.url()" class="hidden md:block">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Projects
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form Card -->
                <Card class="border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FolderKanban class="h-5 w-5" />
                            Project Information
                        </CardTitle>
                        <CardDescription>
                            Update project details and team assignments
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <Label for="name">Project Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter project name"
                                    class="w-full"
                                    :class="errors.name ? 'border-destructive' : ''"
                                    required
                                />
                                <p v-if="errors.name" class="text-sm text-destructive">
                                    {{ errors.name }}
                                </p>
                            </div>

                            <!-- Description Field -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Enter project description and objectives..."
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
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="planning">Planning</SelectItem>
                                            <SelectItem value="in_progress">In Progress</SelectItem>
                                            <SelectItem value="on_hold">On Hold</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="errors.status" class="text-sm text-destructive">
                                        {{ errors.status }}
                                    </p>
                                </div>

                                <!-- Timeline Fields -->
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="start_date">Start Date</Label>
                                        <Input
                                            id="start_date"
                                            v-model="form.start_date"
                                            type="date"
                                            class="w-full"
                                            :class="errors.start_date ? 'border-destructive' : ''"
                                        />
                                        <p v-if="errors.start_date" class="text-sm text-destructive">
                                            {{ errors.start_date }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="end_date">End Date</Label>
                                        <Input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            class="w-full"
                                            :class="errors.end_date ? 'border-destructive' : ''"
                                        />
                                        <p v-if="errors.end_date" class="text-sm text-destructive">
                                            {{ errors.end_date }}
                                        </p>
                                    </div>
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
                                            <SelectTrigger class="w-full" :class="errors.client_id ? 'border-destructive' : ''">
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
                                        <p v-if="errors.client_id" class="text-sm text-destructive">
                                            {{ errors.client_id }}
                                        </p>
                                    </div>

                                    <!-- Lead Selection -->
                                    <div class="space-y-2">
                                        <Label for="lead_id">Lead</Label>
                                        <Select v-model="form.lead_id">
                                            <SelectTrigger class="w-full" :class="errors.lead_id ? 'border-destructive' : ''">
                                                <SelectValue placeholder="Select lead" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem :value="null">No Lead</SelectItem>
                                                <SelectItem
                                                    v-for="lead in leads"
                                                    :key="lead.id"
                                                    :value="lead.id"
                                                >
                                                    {{ lead.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="errors.lead_id" class="text-sm text-destructive">
                                            {{ errors.lead_id }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Team Members Field -->
                            <div class="space-y-2">
                                <Label for="members">Team Members</Label>
                                <Select v-model="form.members" multiple>
                                    <SelectTrigger class="w-full" :class="errors.members ? 'border-destructive' : ''">
                                        <SelectValue placeholder="Select team members" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="user in users"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="errors.members" class="text-sm text-destructive">
                                    {{ errors.members }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Select multiple team members to collaborate on this project
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4">
                                <Button
                                    type="submit"
                                    class="flex-1 gap-2"
                                    :disabled="!form.name"
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
                    <!-- Project Summary Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Project Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div class="flex items-center gap-3 rounded-lg bg-muted/50 p-3">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <span class="text-sm font-medium text-primary">
                                        {{ props.project.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium">{{ props.project.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Created {{ new Date(props.project.created_at).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Current Status:</span>
                                    <span class="font-medium capitalize">
                                        {{ props.project.status.replace('_', ' ') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Created By:</span>
                                    <span class="font-medium">
                                        {{ props.project.creator?.name || '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Linked Client:</span>
                                    <span class="font-medium">
                                        {{ props.project.client?.name || 'Not linked' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Linked Lead:</span>
                                    <span class="font-medium">
                                        {{ props.project.lead?.name || 'Not linked' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Team Members:</span>
                                    <span class="font-medium">
                                        {{ props.project.members.length }}
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
                                <Target class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Update project status to reflect current progress accurately
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Calendar class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Adjust timeline dates if project scope or deadlines change
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Users class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Update team members as project staffing needs evolve
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Building class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Re-link to different clients or leads if project focus shifts
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
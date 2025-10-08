<script setup lang="ts">
import { index, store } from '@/actions/App/Http/Controllers/ProjectController';
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
import { ArrowLeft, FolderKanban, Users, Calendar, Target } from 'lucide-vue-next';
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

interface Props {
    users: User[];
    leads: Lead[];
    clients: Client[];
}

defineProps<Props>();

const form = reactive({
    name: '',
    description: '',
    status: 'planning' as 'planning' | 'in_progress' | 'on_hold' | 'completed',
    start_date: '',
    end_date: '',
    client_id: null as number | null,
    lead_id: null as number | null,
    members: [] as number[],
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: index.url() },
    { title: 'Create Project', href: '#' },
];

function submit() {
    router.post(store.url(), form);
}
</script>

<template>
    <Head title="Create Project" />
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
                            Create New Project
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Set up a new project and assign team members
                        </p>
                    </div>
                </div>
                <Link :href="index.url()">
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
                            Enter project details and assign team members
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
                                    required
                                />
                            </div>

                            <!-- Description Field -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Enter project description and objectives..."
                                    class="min-h-[100px] w-full"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Status Field -->
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="planning">Planning</SelectItem>
                                            <SelectItem value="in_progress">In Progress</SelectItem>
                                            <SelectItem value="on_hold">On Hold</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                        </SelectContent>
                                    </Select>
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
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="end_date">End Date</Label>
                                        <Input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            class="w-full"
                                        />
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
                                            <SelectTrigger class="w-full">
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
                                    </div>

                                    <!-- Lead Selection -->
                                    <div class="space-y-2">
                                        <Label for="lead_id">Lead</Label>
                                        <Select v-model="form.lead_id">
                                            <SelectTrigger class="w-full">
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
                                    </div>
                                </div>
                            </div>

                            <!-- Team Members Field -->
                            <div class="space-y-2">
                                <Label for="members">Team Members</Label>
                                <Select v-model="form.members" multiple>
                                    <SelectTrigger class="w-full">
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
                                    <Plus class="h-4 w-4" />
                                    Create Project
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
                    <!-- Project Types Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Project Types</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <!-- Client Projects -->
                            <div class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-sm bg-primary"></div>
                                    <span class="font-medium text-primary">Client Projects</span>
                                </div>
                                <p class="text-muted-foreground">
                                    Projects associated with existing clients for ongoing work, maintenance, or new initiatives.
                                </p>
                            </div>

                            <!-- Lead Projects -->
                            <div class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-sm bg-secondary"></div>
                                    <span class="font-medium text-secondary">Lead Projects</span>
                                </div>
                                <p class="text-muted-foreground">
                                    Projects linked to potential leads for proposals, demonstrations, or initial engagements.
                                </p>
                            </div>

                            <!-- Internal Projects -->
                            <div class="space-y-2 rounded-lg border border-green-200 bg-green-50 p-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-sm bg-green-600"></div>
                                    <span class="font-medium text-green-700">Internal Projects</span>
                                </div>
                                <p class="text-green-600">
                                    Standalone projects not linked to specific clients or leads for internal initiatives.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Project Tips Card -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg">Project Tips</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <Target class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Set clear start and end dates to track project timeline
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Users class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Assign team members who will collaborate on project tasks
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <Calendar class="h-4 w-4 text-primary mt-0.5" />
                                <p class="text-muted-foreground">
                                    Link to clients or leads for better organization and tracking
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
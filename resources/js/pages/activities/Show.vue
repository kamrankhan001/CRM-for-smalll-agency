<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, User, Calendar, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge as ShadcnBadge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';

// Import Wayfinder-generated actions
import { index } from '@/actions/App/Http/Controllers/ActivityController';

interface Causer {
    id: number;
    name: string;
    role: string;
}

interface Subject {
    id: number;
    type: string;
}

const props = defineProps<{
    activity: {
        id: number;
        description: string;
        model_type: string;
        action: string;
        causer: Causer;
        subject: Subject;
        properties: any;
        created_at: string;
        updated_at: string;
    };
    auth: {
        user: {
            id: number;
            role: 'admin' | 'manager' | 'member';
            name: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Activities', href: index.url() },
    { title: 'Details', href: '#' },
];

// Action variants for badges
const getActionVariant = (action: string) => {
    switch (action) {
        case 'created':
            return 'default';
        case 'updated':
            return 'secondary';
        case 'deleted':
            return 'destructive';
        case 'assigned':
            return 'outline';
        case 'commented':
            return 'outline';
        default:
            return 'outline';
    }
};

// Role variants for badges
const getRoleVariant = (role: string) => {
    switch (role) {
        case 'admin':
            return 'default';
        case 'manager':
            return 'secondary';
        case 'member':
            return 'outline';
        default:
            return 'outline';
    }
};

// Format date to "2025-9-20 5:00 AM" format
const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    
    const year = date.getFullYear();
    const month = date.getMonth() + 1; // Months are 0-based
    const day = date.getDate();
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12; // 0 should be 12
    
    return `${year}-${month}-${day} ${hours}:${minutes} ${ampm}`;
};

// Check if properties contain changes (for old/new values)
const hasChanges = computed(() => {
    return props.activity.properties && 
        (props.activity.properties.old || props.activity.properties.attributes);
});

// Format changes for display
const formatChanges = (properties: any) => {
    if (!properties) return null;
    
    if (properties.old && properties.attributes) {
        // Updated activity with old and new values
        const changes: any = {};
        Object.keys(properties.attributes).forEach(key => {
            if (properties.old[key] !== properties.attributes[key]) {
                changes[key] = {
                    from: properties.old[key],
                    to: properties.attributes[key]
                };
            }
        });
        return Object.keys(changes).length > 0 ? changes : null;
    }
    
    return properties;
};
</script>

<template>
    <Head title="Activity Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Activity Details</h1>
                    <p class="mt-1 text-muted-foreground">
                        Detailed view of system activity
                    </p>
                </div>
                <Link :href="index.url()">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Activities
                    </Button>
                </Link>
            </div>

            <!-- Main Activity Card -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <!-- Removed icon, showing model name in title -->
                            <div>
                                <CardTitle class="text-xl">{{ activity.model_type }}</CardTitle>
                                <div class="flex items-center gap-2 mt-1">
                                    <ShadcnBadge 
                                        :variant="getActionVariant(activity.action)"
                                        class="capitalize"
                                    >
                                        {{ activity.action }}
                                    </ShadcnBadge>
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDateTime(activity.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- User Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Performed By</label>
                            <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">{{ activity.causer.name }}</div>
                                    <ShadcnBadge 
                                        :variant="getRoleVariant(activity.causer.role)"
                                        class="text-xs capitalize mt-1"
                                    >
                                        {{ activity.causer.role }}
                                    </ShadcnBadge>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Date & Time</label>
                            <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">
                                        {{ new Date(activity.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        }) }}
                                    </div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        {{ new Date(activity.created_at).toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        }) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground">Description</label>
                        <div class="p-3 rounded-lg border bg-muted/30">
                            <p class="text-foreground">{{ activity.description }}</p>
                        </div>
                    </div>

                    <!-- Subject Information -->
                    <div v-if="activity.subject" class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground">Related To</label>
                        <div class="p-3 rounded-lg border bg-muted/30">
                            <div class="flex items-center gap-2">
                                <ShadcnBadge variant="outline" class="capitalize">
                                    {{ activity.subject.type }}
                                </ShadcnBadge>
                                <span class="text-sm text-muted-foreground">ID: {{ activity.subject.id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Changes/Properties -->
                    <div v-if="hasChanges" class="space-y-3">
                        <label class="text-sm font-medium text-muted-foreground">
                            {{ activity.action === 'updated' ? 'Changes Made' : 'Properties' }}
                        </label>
                        <div class="rounded-lg border overflow-hidden">
                            <div class="bg-muted/30 px-4 py-2 border-b">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4" />
                                    <span class="text-sm font-medium">Details</span>
                                </div>
                            </div>
                            <div class="p-4 bg-background">
                                <pre class="text-sm whitespace-pre-wrap font-mono text-foreground">{{
                                    JSON.stringify(formatChanges(activity.properties) || activity.properties, null, 2)
                                }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- No changes message -->
                    <div v-else-if="activity.properties" class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground">Additional Data</label>
                        <div class="p-4 rounded-lg border bg-muted/30 text-center">
                            <p class="text-muted-foreground">No detailed changes recorded for this activity</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Technical Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Activity ID:</span>
                            <span class="font-mono">{{ activity.id }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Model Type:</span>
                            <span>{{ activity.model_type }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Action:</span>
                            <span class="capitalize">{{ activity.action }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Created:</span>
                            <span>{{ formatDateTime(activity.created_at) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, User, Calendar, FileText, RefreshCw, Plus, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';

// Import Wayfinder-generated actions
import { index } from '@/actions/App/Http/Controllers/ActivityController';

interface Causer {
    id: number;
    name: string;
    role: string;
    email?: string;
}

interface Subject {
    id: number;
    name?: string;
    title?: string;
    type: string;
}

interface Changes {
    old?: Record<string, any>;
    attributes?: Record<string, any>;
}

interface Activity {
    id: number;
    description: string;
    action: string;
    causer: Causer;
    subject: Subject | null;
    changes: Changes | null;
    created_at: string;
    updated_at: string;
    subject_type: string;
    subject_id: number;
}

const props = defineProps<{
    activity: Activity;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Activities', href: index.url() },
    { title: 'Activity Details', href: '#' },
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

// Action icons
const getActionIcon = (action: string) => {
    switch (action) {
        case 'created':
            return Plus;
        case 'updated':
            return RefreshCw;
        case 'deleted':
            return Trash2;
        default:
            return RefreshCw;
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

// Format date to readable format
const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12;
    
    return `${year}-${month}-${day} ${hours}:${minutes} ${ampm}`;
};

// Check if activity has changes
const hasChanges = computed(() => {
    return props.activity.changes && 
        (props.activity.changes.old || props.activity.changes.attributes);
});

// Get model type from subject_type - simplified version
const modelType = computed(() => {
    if (!props.activity.subject_type) return 'Unknown';
    
    // Extract class name from full namespace
    const parts = props.activity.subject_type.split('\\');
    return parts[parts.length - 1] || 'Unknown';
});

// Get subject display name
const subjectName = computed(() => {
    if (!props.activity.subject) return 'Unknown';
    return props.activity.subject.name || props.activity.subject.title || `ID: ${props.activity.subject.id}`;
});

// Format changes for display
const formattedChanges = computed(() => {
    const changes = props.activity.changes;
    if (!changes) return null;
    
    if (changes.old && changes.attributes) {
        // Updated activity with old and new values
        const formattedChanges: Record<string, { from: any; to: any }> = {};
        // Use type assertion to tell TypeScript that attributes is defined
        const attributes = changes.attributes!;
        Object.keys(attributes).forEach(key => {
            if (changes.old?.[key] !== attributes[key]) {
                formattedChanges[key] = {
                    from: changes.old?.[key] ?? null,
                    to: attributes[key]
                };
            }
        });
        return Object.keys(formattedChanges).length > 0 ? formattedChanges : null;
    }
    
    return changes.attributes || changes.old || changes;
});

// Get subject route based on type
const getSubjectRoute = (subject: Subject | null): string | undefined => {
    if (!subject) return undefined;
    
    const type = subject.type.toLowerCase();
    return `/${type}s/${subject.id}`;
};

// Format value for display
const formatValue = (value: any): string => {
    if (value === null) return 'null';
    if (value === '') return 'empty';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
};

// Helper to safely cast formatted changes for template
const getFormattedChangesEntries = computed(() => {
    const changes = formattedChanges.value;
    if (!changes || typeof changes !== 'object') return [];
    
    // Check if it's the Record<string, {from: any, to: any}> type
    if (Object.values(changes).every(val => val && typeof val === 'object' && 'from' in val && 'to' in val)) {
        return Object.entries(changes as Record<string, { from: any; to: any }>);
    }
    
    return [];
});
</script>

<template>
    <Head title="Activity Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <!-- Header with Back Arrow -->
            <div class="flex items-center gap-4 mb-6">
                <Link :href="index.url()">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Activity Details</h1>
                    <p class="text-sm text-muted-foreground sm:text-base">
                        Detailed view of system activity
                    </p>
                </div>
            </div>

            <!-- Main Activity Card -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div>
                                <CardTitle class="text-xl flex items-center gap-2">
                                    <component 
                                        :is="getActionIcon(activity.action)" 
                                        class="h-5 w-5" 
                                    />
                                    {{ modelType }} {{ activity.action }}
                                </CardTitle>
                                <div class="flex items-center gap-2 mt-1">
                                    <Badge 
                                        :variant="getActionVariant(activity.action)"
                                        class="capitalize"
                                    >
                                        {{ activity.action }}
                                    </Badge>
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDateTime(activity.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Activity Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- User Information -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">Performed By</label>
                            <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">{{ activity.causer.name }}</div>
                                    <Badge 
                                        :variant="getRoleVariant(activity.causer.role)"
                                        class="text-xs capitalize mt-1"
                                    >
                                        {{ activity.causer.role }}
                                    </Badge>
                                    <div v-if="activity.causer.email" class="text-xs text-muted-foreground mt-1">
                                        {{ activity.causer.email }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time -->
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
                                            second: '2-digit',
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
                            <div class="flex items-center gap-3">
                                <Badge variant="outline" class="capitalize">
                                    {{ activity.subject.type }}
                                </Badge>
                                <div class="flex-1">
                                    <span class="text-sm font-medium">{{ subjectName }}</span>
                                </div>
                                <Link 
                                    v-if="getSubjectRoute(activity.subject)" 
                                    :href="getSubjectRoute(activity.subject) as string"
                                >
                                    <Button variant="outline" size="sm">
                                        View
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Changes/Properties -->
                    <div v-if="hasChanges" class="space-y-3">
                        <label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                            <RefreshCw class="h-4 w-4" />
                            {{ activity.action === 'updated' ? 'Changes Made' : 'Properties' }}
                        </label>
                        <div class="rounded-lg border overflow-hidden">
                            <div class="bg-muted/30 px-4 py-2 border-b">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4" />
                                    <span class="text-sm font-medium">
                                        {{ activity.action === 'updated' ? 'Field Changes' : 'Activity Data' }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 bg-background">
                                <template v-if="activity.action === 'updated' && getFormattedChangesEntries.length > 0">
                                    <div 
                                        v-for="[key, change] in getFormattedChangesEntries" 
                                        :key="key"
                                        class="py-2 border-b last:border-b-0"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="font-medium text-sm capitalize mb-1">
                                                    {{ key.replace(/_/g, ' ') }}
                                                </div>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <div class="text-xs text-muted-foreground mb-1">Before</div>
                                                        <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded border text-red-700 dark:text-red-300">
                                                            {{ formatValue(change.from) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs text-muted-foreground mb-1">After</div>
                                                        <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded border text-green-700 dark:text-green-300">
                                                            {{ formatValue(change.to) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <pre class="text-sm whitespace-pre-wrap font-mono text-foreground">{{
                                        JSON.stringify(activity.changes, null, 2)
                                    }}</pre>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- No changes message -->
                    <div v-else-if="activity.changes" class="space-y-2">
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
                            <span class="capitalize">{{ modelType }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Subject ID:</span>
                            <span class="font-mono">{{ activity.subject_id }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Action:</span>
                            <span class="capitalize">{{ activity.action }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Created:</span>
                            <span>{{ formatDateTime(activity.created_at) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Last Updated:</span>
                            <span>{{ formatDateTime(activity.updated_at) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { useEchoNotification } from '@laravel/echo-vue';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

// Define the shape of the incoming notification payload
interface LeadAssignedNotification {
    message: string;
    type: string;
    url?: string;
    lead_id: string | number;
    time?: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = page.props.flash || {};
const userId = page.props.auth?.user?.id;

// Listen to new lead assigned notifications for this user from ANY page
useEchoNotification<LeadAssignedNotification>(
    `lead-assigned.${userId}`,
    (e: LeadAssignedNotification) => {
        console.log('🔔 LeadAssigned received:', e);

        // Show real-time toast on any page
        toast.success(e.message ?? 'You have a new lead!', {
            description: e.time ? `Assigned at ${e.time}` : undefined,
            action: {
                label: 'View Lead',
                onClick: () => window.location.href = e.url ?? `/leads/${e.lead_id}`,
            },
        });
    },
    'App.Notifications.LeadAssignedNotification'
);

onMounted(() => {
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster position="top-right" richColors class="pointer-events-auto" />
    </AppLayout>
</template>
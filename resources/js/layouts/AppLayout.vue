<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { useEchoNotification } from '@laravel/echo-vue';
import { notificationService } from '@/services/notificationService';
import type { AppNotification } from '@/types/notifications';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = page.props.flash || {};
const userId = page.props.auth?.user?.id;

// Single listener for ALL notification types on user's private channel
useEchoNotification<AppNotification>(
    notificationService.getChannel(userId),
    (notification: AppNotification) => {
        notificationService.handleNotification(notification);
    }
);

// Listen for global notifications (note_added)
useEchoNotification<AppNotification>(
    'notifications.global',
    (notification: AppNotification) => {
        notificationService.handleNotification(notification);
    },
    'App.Notifications.NoteAddedNotification'
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
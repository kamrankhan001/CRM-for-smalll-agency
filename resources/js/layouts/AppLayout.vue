<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useEchoNotification } from '@laravel/echo-vue';
import { notificationService } from '@/services/notificationService';
import type { AppNotification } from '@/types/notifications';
import { useNotifications } from '@/composables/useNotifications';
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
const notificationStore = useNotifications();

const isNotificationPage = page.url.startsWith('/notifications');

watch(() => page.url, () => {
    notificationStore.resetInitialCountFlag();
});

onMounted(() => {
    const initialCount = page.props.unreadNotificationsCount || 0;
    notificationStore.setInitialServerCount(initialCount);
    
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
});

if (!isNotificationPage) {
    useEchoNotification<AppNotification>(
        notificationService.getChannel(userId),
        (notification: AppNotification) => {
            handleRealTimeNotification(notification);
        }
    );

    useEchoNotification<AppNotification>(
        'notifications.global',
        (notification: AppNotification) => {
            handleRealTimeNotification(notification);
        },
        'App.Notifications.NoteAddedNotification'
    );
}

function handleRealTimeNotification(notification: AppNotification) {
    const realTimeNotification = {
        id: `realtime-${Date.now()}-${notification.type}`,
        message: notification.message,
        type: notification.type,
        url: notification.url,
        read_at: null,
        created_at: new Date().toISOString(),
    };
    
    notificationStore.addRealTimeNotification(realTimeNotification);
    notificationService.handleNotification(notification);

    // Play notification sound
    playNotificationSound();
}

function playNotificationSound() {
    const audio = new Audio('/sounds/notification.wav');
    audio.volume = 0.3;
    
    const playPromise = audio.play();
    
    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.log('Notification sound play failed:', error);
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster position="top-right" richColors class="pointer-events-auto" />
    </AppLayout>
</template>
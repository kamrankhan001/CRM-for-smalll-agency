<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl, urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();

const page = usePage();
const notificationStore = useNotifications();

const unreadCount = computed(() => notificationStore.totalUnreadCount.value);
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100 relative"
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                    >
                        <Link :href="toUrl(item.href)">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            
                            <Badge 
                                v-if="item.title === 'Notifications' && unreadCount > 0"
                                variant="destructive" 
                                class="absolute right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                            >
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </Badge>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
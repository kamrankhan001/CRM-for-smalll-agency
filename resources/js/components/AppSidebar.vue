<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    ClipboardList,
    FolderKanban,
    LayoutGrid,
    StickyNote,
    Users,
    DollarSign,
    Target,
    UserCheck,
    FileSearch,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = page.props.auth?.user;

// Main navigation links with improved icons
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Leads',
        href: '/leads',
        icon: Target, // Better for "target" leads
    },
    {
        title: 'Clients',
        href: '/clients',
        icon: UserCheck, // Represents verified/active clients
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: FolderKanban,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: ClipboardList,
    },
    {
        title: 'Notes',
        href: '/notes',
        icon: StickyNote,
    },
    {
        title: 'Documents',
        href: '/documents',
        icon: FileSearch, // More specific than generic FileText
    },
    {
        title: 'Activities',
        href: '/activities',
        icon: Activity,
    },
];

// Add Invoice link for admin & manager with better icon
if (['admin', 'manager'].includes(user?.role)) {
    mainNavItems.push({
        title: 'Invoices',
        href: '/invoices',
        icon: DollarSign, // Much better for invoices than FileText
    });
}

// Conditionally add Users link if role === 'admin'
if (user?.role === 'admin') {
    mainNavItems.push({
        title: 'Users',
        href: '/users',
        icon: Users,
    });
}

const footerNavItems: NavItem[] = [
    {
        title: 'Notifications',
        href: '/notifications',
        icon: Bell,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
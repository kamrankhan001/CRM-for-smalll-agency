<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Table, TableHeader, TableHead, TableRow, TableBody, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import type { BreadcrumbItem } from '@/types';

const props = defineProps({
    stats: Object,
    recent_notes: Array,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- === Stats Cards === -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Leads -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base font-medium">Total Leads</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">{{ props.stats.leads }}</p>
                        <p class="text-sm text-muted-foreground mt-1">Potential clients in pipeline</p>
                    </CardContent>
                </Card>

                <!-- Clients -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base font-medium">Total Clients</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">{{ props.stats.clients }}</p>
                        <p class="text-sm text-muted-foreground mt-1">Active paying customers</p>
                    </CardContent>
                </Card>

                <!-- Pending Tasks -->
                <Card class="rounded-2xl shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base font-medium">Pending Tasks</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">{{ props.stats.pending_tasks }}</p>
                        <p class="text-sm text-muted-foreground mt-1">Tasks awaiting completion</p>
                    </CardContent>
                </Card>
            </div>

            <!-- === Recent Notes === -->
            <Card class="rounded-2xl shadow-sm">
                <CardHeader>
                    <CardTitle class="text-base font-medium">Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Note</TableHead>
                                <TableHead>Linked To</TableHead>
                                <TableHead>Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="note in props.recent_notes"
                                :key="note.id"
                            >
                                <TableCell>{{ note.user?.name ?? 'Unknown' }}</TableCell>
                                <TableCell class="max-w-[300px] truncate">{{ note.content }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ note.noteable_type.split('\\').pop() }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ new Date(note.created_at).toLocaleDateString() }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';
import { reactive } from 'vue';

// Import Wayfinder-generated actions
import { index, store } from '@/actions/App/Http/Controllers/UserController';

interface Props {
    auth: {
        user: {
            id: number;
            name: string;
        };
    };
    errors: Record<string, string>;
}

const props = defineProps<Props>();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'member',
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index.url() },
    { title: 'Create User', href: '#' },
];

function submit() {
    router.post(store.url(), form);
}

// Check if form is valid for submit button tooltip
const isFormValid = () => {
    return (
        form.name && form.email && form.password && form.password_confirmation
    );
};
</script>

<template>
    <Head title="Create User" />
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
                            Create New User
                        </h1>
                        <p class="mt-1 text-muted-foreground">
                            Add a new user to your system with specific role
                            permissions
                        </p>
                    </div>
                </div>
                <!-- Hide on small devices, show on medium and above -->
                <Link :href="index.url()" class="hidden md:block">
                    <Button variant="outline" class="flex items-center gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Users
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form Card -->
                <Card class="self-start border lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UserPlus class="h-5 w-5" />
                            User Information
                        </CardTitle>
                        <CardDescription>
                            Enter the user's details and assign appropriate
                            permissions
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <Label for="name">Full Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter full name"
                                    class="w-full"
                                    :class="
                                        props.errors.name
                                            ? 'border-destructive'
                                            : ''
                                    "
                                    required
                                />
                                <p
                                    v-if="props.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ props.errors.name }}
                                </p>
                            </div>

                            <!-- Email Field -->
                            <div class="space-y-2">
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    class="w-full"
                                    :class="
                                        props.errors.email
                                            ? 'border-destructive'
                                            : ''
                                    "
                                    required
                                />
                                <p
                                    v-if="props.errors.email"
                                    class="text-sm text-destructive"
                                >
                                    {{ props.errors.email }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Password Field -->
                            <div class="space-y-2">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Create a secure password"
                                    class="w-full"
                                    :class="
                                        props.errors.password
                                            ? 'border-destructive'
                                            : ''
                                    "
                                    required
                                />
                                <p
                                    v-if="props.errors.password"
                                    class="text-sm text-destructive"
                                >
                                    {{ props.errors.password }}
                                </p>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="space-y-2">
                                <Label for="password_confirmation"
                                    >Confirm Password</Label
                                >
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Confirm your password"
                                    class="w-full"
                                    :class="
                                        props.errors.password_confirmation
                                            ? 'border-destructive'
                                            : ''
                                    "
                                    required
                                />
                                <p
                                    v-if="props.errors.password_confirmation"
                                    class="text-sm text-destructive"
                                >
                                    {{ props.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>

                        <!-- Role Field -->
                        <div class="space-y-2">
                            <Label for="role">User Role</Label>
                            <Select v-model="form.role">
                                <SelectTrigger
                                    class="w-full"
                                    :class="
                                        props.errors.role
                                            ? 'border-destructive'
                                            : ''
                                    "
                                >
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="admin">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-2 w-2 rounded-full bg-destructive"
                                            ></div>
                                            <span>Admin</span>
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="manager">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-2 w-2 rounded-full bg-primary"
                                            ></div>
                                            <span>Manager</span>
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="member">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-2 w-2 rounded-full bg-muted-foreground"
                                            ></div>
                                            <span>Member</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="props.errors.role"
                                class="text-sm text-destructive"
                            >
                                {{ props.errors.role }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                <span
                                    v-if="form.role === 'admin'"
                                    class="font-medium text-destructive"
                                    >Admin</span
                                >
                                <span
                                    v-else-if="form.role === 'manager'"
                                    class="font-medium text-primary"
                                    >Manager</span
                                >
                                <span
                                    v-else
                                    class="font-medium text-muted-foreground"
                                    >Member</span
                                >
                                <span class="text-muted-foreground">
                                    {{
                                        form.role === 'admin'
                                            ? ' - Full system access'
                                            : form.role === 'manager'
                                              ? ' - Manage users and content'
                                              : ' - Basic user permissions'
                                    }}
                                </span>
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-4">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div class="inline-block flex-1">
                                            <Button
                                                @click="submit"
                                                class="w-full gap-2"
                                                :disabled="!isFormValid()"
                                            >
                                                <UserPlus class="h-4 w-4" />
                                                Create User
                                            </Button>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent v-if="!isFormValid()">
                                        <div class="space-y-1">
                                            <p
                                                v-if="!form.name"
                                                class="text-sm"
                                            >
                                                Full name is required
                                            </p>
                                            <p
                                                v-else-if="!form.email"
                                                class="text-sm"
                                            >
                                                Email address is required
                                            </p>
                                            <p
                                                v-else-if="!form.password"
                                                class="text-sm"
                                            >
                                                Password is required
                                            </p>
                                            <p
                                                v-else-if="
                                                    !form.password_confirmation
                                                "
                                                class="text-sm"
                                            >
                                                Password confirmation is
                                                required
                                            </p>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                            <Link :href="index.url()" class="flex-1">
                                <Button variant="outline" class="w-full">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Role Permissions Info -->
                <div class="space-y-6">
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Role Permissions</CardTitle
                            >
                            <p class="text-sm text-muted-foreground">
                                Each role defines what access level a user has
                                within your CRM.
                            </p>
                        </CardHeader>

                        <CardContent class="space-y-4 text-sm">
                            <!-- Admin -->
                            <div
                                class="space-y-2 rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-destructive"
                                    ></div>
                                    <span class="font-medium text-destructive"
                                        >Admin</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Full access — manage users, roles, clients,
                                    sessions, and system settings.
                                </p>
                            </div>

                            <!-- Manager -->
                            <div
                                class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-primary"
                                    ></div>
                                    <span class="font-medium text-primary"
                                        >Manager</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Manage clients and team members, edit
                                    sessions, but can't modify admins or system
                                    settings.
                                </p>
                            </div>

                            <!-- Member -->
                            <div
                                class="space-y-2 rounded-lg border border-muted-foreground/20 bg-muted p-4"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm bg-muted-foreground"
                                    ></div>
                                    <span
                                        class="font-medium text-muted-foreground"
                                        >Member</span
                                    >
                                </div>
                                <p class="text-muted-foreground">
                                    Manage assigned clients and session notes.
                                    No access to user management or global data.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Password Requirements -->
                    <Card class="border">
                        <CardHeader>
                            <CardTitle class="text-lg"
                                >Password Requirements</CardTitle
                            >
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Minimum 6 characters in length
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Must contain letters and numbers
                                </p>
                            </div>
                            <div class="flex items-start gap-2">
                                <div
                                    class="mt-1.5 h-1.5 w-1.5 rounded-full bg-primary"
                                ></div>
                                <p class="text-muted-foreground">
                                    Passwords must match in both fields
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

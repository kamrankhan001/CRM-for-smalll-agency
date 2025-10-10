<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
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
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { ArrowLeft, Save, User } from 'lucide-vue-next';

// Import Wayfinder-generated actions
import { index, update } from '@/actions/App/Http/Controllers/UserController';

interface Props {
  user: {
    id: number;
    name: string;
    email: string;
    role: string;
  };
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
  name: props.user.name,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  role: props.user.role,
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Users', href: index.url() },
  { title: `Edit ${props.user.name}`, href: '#' },
];

function submit() {
  router.put(update.url(props.user.id), form);
}

// Check if form is valid for submit button tooltip
const isFormValid = () => {
  return form.name && form.email;
};
</script>

<template>
  <Head :title="`Edit ${props.user.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Header Section -->
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
          <Link :href="index.url()">
            <Button variant="ghost" size="icon" class="h-9 w-9">
              <ArrowLeft class="h-4 w-4" />
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
            <p class="text-muted-foreground mt-1">
              Update user information and permissions for {{ props.user.name }}
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

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <Card class="lg:col-span-2 border self-start">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <User class="h-5 w-5" />
              User Information
            </CardTitle>
            <CardDescription>
              Update the user's details and modify role permissions
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Name Field -->
              <div class="space-y-2">
                <Label for="name">Full Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  placeholder="Enter full name"
                  class="w-full"
                  :class="props.errors.name ? 'border-destructive' : ''"
                  required
                />
                <p v-if="props.errors.name" class="text-sm text-destructive">
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
                  :class="props.errors.email ? 'border-destructive' : ''"
                  required
                />
                <p v-if="props.errors.email" class="text-sm text-destructive">
                  {{ props.errors.email }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Password Field -->
              <div class="space-y-2">
                <Label for="password">New Password</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  placeholder="Leave blank to keep current"
                  class="w-full"
                  :class="props.errors.password ? 'border-destructive' : ''"
                />
                <p v-if="props.errors.password" class="text-sm text-destructive">
                  {{ props.errors.password }}
                </p>
                <p class="text-xs text-muted-foreground">
                  Only enter if you want to change the password
                </p>
              </div>

              <!-- Confirm Password Field -->
              <div class="space-y-2">
                <Label for="password_confirmation">Confirm New Password</Label>
                <Input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  placeholder="Confirm new password"
                  class="w-full"
                  :class="props.errors.password_confirmation ? 'border-destructive' : ''"
                />
                <p v-if="props.errors.password_confirmation" class="text-sm text-destructive">
                  {{ props.errors.password_confirmation }}
                </p>
              </div>
            </div>

            <!-- Role Field -->
            <div class="space-y-2">
              <Label for="role">User Role</Label>
              <Select v-model="form.role">
                <SelectTrigger class="w-full" :class="props.errors.role ? 'border-destructive' : ''">
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="admin">
                    <div class="flex items-center gap-2">
                      <div class="w-2 h-2 bg-destructive rounded-full"></div>
                      <span>Admin</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="manager">
                    <div class="flex items-center gap-2">
                      <div class="w-2 h-2 bg-primary rounded-full"></div>
                      <span>Manager</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="member">
                    <div class="flex items-center gap-2">
                      <div class="w-2 h-2 bg-muted-foreground rounded-full"></div>
                      <span>Member</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="props.errors.role" class="text-sm text-destructive">
                {{ props.errors.role }}
              </p>
              <p class="text-sm text-muted-foreground">
                <span v-if="form.role === 'admin'" class="text-destructive font-medium">Admin</span>
                <span v-else-if="form.role === 'manager'" class="text-primary font-medium">Manager</span>
                <span v-else class="text-muted-foreground font-medium">Member</span>
                <span class="text-muted-foreground">
                  {{ 
                    form.role === 'admin' ? ' - Full system access' :
                    form.role === 'manager' ? ' - Manage users and content' :
                    ' - Basic user permissions'
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
                        <Save class="h-4 w-4" />
                        Save Changes
                      </Button>
                    </div>
                  </TooltipTrigger>
                  <TooltipContent v-if="!isFormValid()">
                    <p>Please fill in all required fields</p>
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

        <!-- Sidebar Information -->
        <div class="space-y-6">
          <!-- User Summary Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">User Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary">
                    {{ props.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <p class="font-medium">{{ props.user.name }}</p>
                  <p class="text-muted-foreground">{{ props.user.email }}</p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Current Role:</span>
                  <span 
                    :class="{
                      'text-destructive font-medium': props.user.role === 'admin',
                      'text-primary font-medium': props.user.role === 'manager',
                      'text-muted-foreground font-medium': props.user.role === 'member'
                    }"
                    class="capitalize"
                  >
                    {{ props.user.role }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Update Tips Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Update Tips</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Password fields can be left empty to keep current password</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Changing roles will immediately update user permissions</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">User will be notified of significant permission changes</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Email changes may require verification</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
    CardDescription 
} from '@/components/ui/card'
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ArrowLeft, UserPlus } from 'lucide-vue-next'
import { store, index } from '@/actions/App/Http/Controllers/LeadController';

interface User {
  id: number
  name: string
}

interface Props {
  users: User[]
  errors: Record<string, string>
}

defineProps<Props>()

const form = useForm({
  name: '',
  email: '',
  phone: '',
  company: '',
  source: '',
  status: 'new',
  assigned_to: null as number | null
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leads', href: index.url() },
  { title: 'Create Lead', href: '#' },
]

function submit() {
  form.post(store.url())
}
</script>

<template>
  <Head title="Create Lead" />
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
              Create New Lead
            </h1>
            <p class="mt-1 text-muted-foreground">
              Add a new potential client to your sales pipeline
            </p>
          </div>
        </div>
        <!-- Hide on small devices, show on medium and above -->
        <Link :href="index.url()" class="hidden md:block">
          <Button variant="outline" class="flex items-center gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Leads
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <Card class="lg:col-span-2 border">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <UserPlus class="h-5 w-5" />
              Lead Information
            </CardTitle>
            <CardDescription>
              Enter the lead's details and assign to a team member
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
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
                    :class="errors.name ? 'border-destructive' : ''"
                    required
                  />
                  <p v-if="errors.name" class="text-sm text-destructive">
                    {{ errors.name }}
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
                    :class="errors.email ? 'border-destructive' : ''"
                  />
                  <p v-if="errors.email" class="text-sm text-destructive">
                    {{ errors.email }}
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Phone Field -->
                <div class="space-y-2">
                  <Label for="phone">Phone Number</Label>
                  <Input
                    id="phone"
                    v-model="form.phone"
                    type="text"
                    placeholder="Enter phone number"
                    class="w-full"
                    :class="errors.phone ? 'border-destructive' : ''"
                  />
                  <p v-if="errors.phone" class="text-sm text-destructive">
                    {{ errors.phone }}
                  </p>
                </div>

                <!-- Company Field -->
                <div class="space-y-2">
                  <Label for="company">Company</Label>
                  <Input
                    id="company"
                    v-model="form.company"
                    type="text"
                    placeholder="Enter company name"
                    class="w-full"
                    :class="errors.company ? 'border-destructive' : ''"
                  />
                  <p v-if="errors.company" class="text-sm text-destructive">
                    {{ errors.company }}
                  </p>
                </div>
              </div>

              <!-- Source Field -->
              <div class="space-y-2">
                <Label for="source">Source</Label>
                <Input
                  id="source"
                  v-model="form.source"
                  type="text"
                  placeholder="e.g. Website, Referral, Social Media"
                  class="w-full"
                  :class="errors.source ? 'border-destructive' : ''"
                />
                <p v-if="errors.source" class="text-sm text-destructive">
                  {{ errors.source }}
                </p>
                <p class="text-xs text-muted-foreground">
                  How did this lead find out about your business?
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Field -->
                <div class="space-y-2">
                  <Label for="status">Status</Label>
                  <Select v-model="form.status">
                    <SelectTrigger class="w-full" :class="errors.status ? 'border-destructive' : ''">
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="new">New</SelectItem>
                      <SelectItem value="contacted">Contacted</SelectItem>
                      <SelectItem value="qualified">Qualified</SelectItem>
                      <SelectItem value="lost">Lost</SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="errors.status" class="text-sm text-destructive">
                    {{ errors.status }}
                  </p>
                </div>

                <!-- Assigned To Field -->
                <div class="space-y-2">
                  <Label for="assigned_to">Assigned To</Label>
                  <Select v-model="form.assigned_to">
                    <SelectTrigger class="w-full" :class="errors.assigned_to ? 'border-destructive' : ''">
                      <SelectValue placeholder="Select user" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">Unassigned</SelectItem>
                      <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="errors.assigned_to" class="text-sm text-destructive">
                    {{ errors.assigned_to }}
                  </p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 pt-4">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <div class="inline-block flex-1">
                        <Button 
                          type="submit" 
                          class="w-full gap-2"
                          :disabled="form.processing || !form.name"
                        >
                          <UserPlus class="h-4 w-4" />
                          Create Lead
                        </Button>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent v-if="form.processing || !form.name">
                      <p v-if="form.processing">Creating lead...</p>
                      <p v-else-if="!form.name">Name is required to create lead</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
                <Link :href="index.url()" class="flex-1">
                  <Button variant="outline" class="w-full">
                    Cancel
                  </Button>
                </Link>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Sidebar Information -->
        <div class="space-y-6">
          <!-- Status Guide Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Lead Status Guide</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <!-- New -->
              <div class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-primary"></div>
                  <span class="font-medium text-primary">New</span>
                </div>
                <p class="text-muted-foreground">
                  Fresh lead that hasn't been contacted yet. Initial outreach pending.
                </p>
              </div>

              <!-- Contacted -->
              <div class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-secondary"></div>
                  <span class="font-medium text-secondary">Contacted</span>
                </div>
                <p class="text-muted-foreground">
                  Initial contact made. Follow-up and qualification in progress.
                </p>
              </div>

              <!-- Qualified -->
              <div class="space-y-2 rounded-lg border border-green-500/20 bg-green-500/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-green-500"></div>
                  <span class="font-medium text-green-600">Qualified</span>
                </div>
                <p class="text-muted-foreground">
                  Lead meets criteria and shows strong potential. Ready for conversion.
                </p>
              </div>

              <!-- Lost -->
              <div class="space-y-2 rounded-lg border border-destructive/20 bg-destructive/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-destructive"></div>
                  <span class="font-medium text-destructive">Lost</span>
                </div>
                <p class="text-muted-foreground">
                  Lead is no longer interested or doesn't meet requirements.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
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
import { store, index } from '@/actions/App/Http/Controllers/ClientController';
import { reactive } from 'vue'

interface User {
  id: number
  name: string
}

interface Lead {
  id: number
  name: string
}

interface Props {
  users: User[]
  leads: Lead[]
  errors: Record<string, string>
}

defineProps<Props>()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  company: '',
  address: '',
  lead_id: null as number | null,
  assigned_to: null as number | null,
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Clients', href: index.url() },
  { title: 'Create Client', href: '#' },
]

function submit() {
  router.post(store.url(), form)
}
</script>

<template>
  <Head title="Create Client" />
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
              Create New Client
            </h1>
            <p class="mt-1 text-muted-foreground">
              Add a new client to your customer management system
            </p>
          </div>
        </div>
        <!-- Hide on small devices, show on medium and above -->
        <Link :href="index.url()" class="hidden md:block">
          <Button variant="outline" class="flex items-center gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Clients
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <Card class="lg:col-span-2 border">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <UserPlus class="h-5 w-5" />
              Client Information
            </CardTitle>
            <CardDescription>
              Enter the client's details and assign to a team member
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
                    placeholder="Enter client name"
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

              <!-- Address Field -->
              <div class="space-y-2">
                <Label for="address">Address</Label>
                <Input
                  id="address"
                  v-model="form.address"
                  type="text"
                  placeholder="Enter full address"
                  class="w-full"
                  :class="errors.address ? 'border-destructive' : ''"
                />
                <p v-if="errors.address" class="text-sm text-destructive">
                  {{ errors.address }}
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Linked Lead Field -->
                <div class="space-y-2">
                  <Label for="lead_id">Linked Lead</Label>
                  <Select v-model="form.lead_id">
                    <SelectTrigger class="w-full" :class="errors.lead_id ? 'border-destructive' : ''">
                      <SelectValue placeholder="Select a lead (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">No Lead (Direct Client)</SelectItem>
                      <SelectItem v-for="lead in leads" :key="lead.id" :value="lead.id">
                        {{ lead.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="errors.lead_id" class="text-sm text-destructive">
                    {{ errors.lead_id }}
                  </p>
                  <p class="text-xs text-muted-foreground">
                    Link this client to an existing lead for tracking
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
                          :disabled="!form.name"
                        >
                          <UserPlus class="h-4 w-4" />
                          Create Client
                        </Button>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent v-if="!form.name">
                      <p>Name is required to create client</p>
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
          <!-- Client Types Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Client Types</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <!-- Lead Conversion -->
              <div class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-primary"></div>
                  <span class="font-medium text-primary">Lead Conversion</span>
                </div>
                <p class="text-muted-foreground">
                  Converted from qualified leads. Tracks the complete sales journey from initial contact to customer.
                </p>
              </div>

              <!-- Direct Client -->
              <div class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-secondary"></div>
                  <span class="font-medium text-secondary">Direct Client</span>
                </div>
                <p class="text-muted-foreground">
                  Clients who signed up directly without going through the lead process. Common for referrals or existing contacts.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
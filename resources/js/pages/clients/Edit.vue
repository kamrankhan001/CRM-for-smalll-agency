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
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ArrowLeft, Save, User } from 'lucide-vue-next'
import { update, index } from '@/actions/App/Http/Controllers/ClientController';
import { reactive } from 'vue'

interface User {
  id: number
  name: string
}

interface Lead {
  id: number
  name: string
}

interface Client {
  id: number
  name: string
  email: string | null
  phone: string | null
  company: string | null
  address: string | null
  lead_id: number | null
  assigned_to: number | null
  lead?: Lead | null
  assignee?: User | null
  creator?: User | null
  created_at: string
}

interface Props {
  client: Client
  users: User[]
  leads: Lead[]
}

const props = defineProps<Props>()

const form = reactive({
  name: props.client.name,
  email: props.client.email || '',
  phone: props.client.phone || '',
  company: props.client.company || '',
  address: props.client.address || '',
  lead_id: props.client.lead_id,
  assigned_to: props.client.assigned_to,
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Clients', href: index.url() },
  { title: `Edit ${props.client.name}`, href: '#' },
]

function submit() {
  router.put(update.url(props.client.id), form)
}
</script>

<template>
  <Head :title="`Edit ${props.client.name}`" />
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
            <h1 class="text-3xl font-bold tracking-tight">Edit Client</h1>
            <p class="text-muted-foreground mt-1">
              Update client information and assignment for {{ props.client.name }}
            </p>
          </div>
        </div>
        <Link :href="index.url()">
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
              <User class="h-5 w-5" />
              Client Information
            </CardTitle>
            <CardDescription>
              Update the client's details and modify assignment
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
                    required
                  />
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
                  />
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
                  />
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
                  />
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
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Linked Lead Field -->
                <div class="space-y-2">
                  <Label for="lead_id">Linked Lead</Label>
                  <Select v-model="form.lead_id">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select a lead (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">No Lead (Direct Client)</SelectItem>
                      <SelectItem v-for="lead in leads" :key="lead.id" :value="lead.id">
                        {{ lead.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Assigned To Field -->
                <div class="space-y-2">
                  <Label for="assigned_to">Assigned To</Label>
                  <Select v-model="form.assigned_to">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select user" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">Unassigned</SelectItem>
                      <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 pt-4">
                <Button 
                  type="submit" 
                  class="flex-1 gap-2"
                  :disabled="!form.name"
                >
                  <Save class="h-4 w-4" />
                  Save Changes
                </Button>
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
          <!-- Client Summary Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Client Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary">
                    {{ props.client.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <p class="font-medium">{{ props.client.name }}</p>
                  <p class="text-muted-foreground">{{ props.client.email || 'No email' }}</p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Current Assignee:</span>
                  <span class="font-medium">
                    {{ props.client.assignee?.name || 'Unassigned' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Source:</span>
                  <span class="font-medium">
                    {{ props.client.lead?.name || 'Direct Client' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Created:</span>
                  <span class="font-medium">
                    {{ new Date(props.client.created_at).toLocaleDateString() }}
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
                <p class="text-muted-foreground">Keep contact information current for effective communication</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Reassign clients if team member roles or availability changes</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Maintain accurate lead links for sales performance tracking</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
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
import { update, index } from '@/actions/App/Http/Controllers/LeadController';
import { reactive } from 'vue'

interface User {
  id: number
  name: string
}

interface Lead {
  id: number
  name: string
  email: string | null
  phone: string | null
  company: string | null
  source: string | null
  status: 'new' | 'contacted' | 'qualified' | 'lost'
  assigned_to: number | null
  assignee?: User | null
  creator?: User | null
  created_at: string
}

interface Props {
  lead: Lead
  users: User[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const form = reactive({
  name: props.lead.name,
  email: props.lead.email || '',
  phone: props.lead.phone || '',
  company: props.lead.company || '',
  source: props.lead.source || '',
  status: props.lead.status,
  assigned_to: props.lead.assigned_to
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leads', href: index.url() },
  { title: `Edit ${props.lead.name}`, href: '#' },
]

function submit() {
  router.put(update.url(props.lead.id), form)
}
</script>

<template>
  <Head :title="`Edit ${props.lead.name}`" />
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
            <h1 class="text-3xl font-bold tracking-tight">Edit Lead</h1>
            <p class="text-muted-foreground mt-1">
              Update lead information and status for {{ props.lead.name }}
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
              <User class="h-5 w-5" />
              Lead Information
            </CardTitle>
            <CardDescription>
              Update the lead's details and modify assignment
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
          <!-- Lead Summary Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Lead Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary">
                    {{ props.lead.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <p class="font-medium">{{ props.lead.name }}</p>
                  <p class="text-muted-foreground">{{ props.lead.email || 'No email' }}</p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Current Status:</span>
                  <span 
                    :class="{
                      'text-primary font-medium': props.lead.status === 'new',
                      'text-secondary font-medium': props.lead.status === 'contacted',
                      'text-green-600 font-medium': props.lead.status === 'qualified',
                      'text-destructive font-medium': props.lead.status === 'lost'
                    }"
                    class="capitalize"
                  >
                    {{ props.lead.status }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Assigned To:</span>
                  <span class="font-medium">
                    {{ props.lead.assignee?.name || 'Unassigned' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Created:</span>
                  <span class="font-medium">
                    {{ new Date(props.lead.created_at).toLocaleDateString() }}
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
                <p class="text-muted-foreground">Update status promptly to reflect current progress</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Reassign leads if team member availability changes</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Keep contact information current for effective follow-up</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
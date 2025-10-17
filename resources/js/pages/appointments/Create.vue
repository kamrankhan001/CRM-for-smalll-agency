<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
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
import { ArrowLeft, Calendar, Clock } from 'lucide-vue-next'
import { store, index } from '@/actions/App/Http/Controllers/AppointmentController';
import { reactive, computed } from 'vue';

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
}

interface Project {
  id: number
  name: string
}

interface Props {
  users: User[]
  leads: Lead[]
  clients: Client[]
  projects: Project[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const form = reactive({
  title: '',
  description: '',
  appointable_type: 'lead' as 'lead' | 'client' | 'project',
  appointable_id: null as number | null,
  date: '',
  start_time: '',
  end_time: '',
  status: 'pending'
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Appointments', href: index.url() },
  { title: 'Create Appointment', href: '#' },
]

function submit() {
  router.post(store.url(), form)
}

// Get available options based on selected type
const appointableOptions = computed(() => {
  switch (form.appointable_type) {
    case 'lead':
      return props.leads
    case 'client':
      return props.clients
    case 'project':
      return props.projects
    default:
      return []
  }
})

// Check if form is valid for submit button tooltip
const isFormValid = () => {
  return form.title && form.date && form.start_time && form.end_time;
};
</script>

<template>
  <Head title="Create Appointment" />
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
              Create New Appointment
            </h1>
            <p class="mt-1 text-muted-foreground">
              Schedule a new meeting or appointment
            </p>
          </div>
        </div>
        <!-- Hide on small devices, show on medium and above -->
        <Link :href="index.url()" class="hidden md:block">
          <Button variant="outline" class="flex items-center gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Appointments
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <Card class="lg:col-span-2 border shadow-sm">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calendar class="h-5 w-5" />
              Appointment Details
            </CardTitle>
            <CardDescription>
              Enter the appointment details and link to relevant records
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Title Field -->
              <div class="space-y-2">
                <Label for="title">Appointment Title</Label>
                <Input
                  id="title"
                  v-model="form.title"
                  type="text"
                  placeholder="Enter appointment title"
                  class="w-full"
                  :class="errors.title ? 'border-destructive' : ''"
                  required
                />
                <p v-if="errors.title" class="text-sm text-destructive">
                  {{ errors.title }}
                </p>
              </div>

              <!-- Description Field -->
              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Enter appointment description, agenda, or notes"
                  class="w-full min-h-[100px]"
                  :class="errors.description ? 'border-destructive' : ''"
                />
                <p v-if="errors.description" class="text-sm text-destructive">
                  {{ errors.description }}
                </p>
              </div>

              <!-- Linked Entity Section -->
              <div class="space-y-4">
                <Label>Link To</Label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Entity Type -->
                  <div class="space-y-2">
                    <Label for="appointable_type">Entity Type</Label>
                    <Select v-model="form.appointable_type">
                      <SelectTrigger class="w-full" :class="errors.appointable_type ? 'border-destructive' : ''">
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="lead">Lead</SelectItem>
                        <SelectItem value="client">Client</SelectItem>
                        <SelectItem value="project">Project</SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="errors.appointable_type" class="text-sm text-destructive">
                      {{ errors.appointable_type }}
                    </p>
                  </div>

                  <!-- Entity Selection -->
                  <div class="space-y-2">
                    <Label v-if="form.appointable_type === 'lead'" for="appointable_id">Select Lead</Label>
                    <Label v-else-if="form.appointable_type === 'client'" for="appointable_id">Select Client</Label>
                    <Label v-else for="appointable_id">Select Project</Label>
                    <Select 
                      v-model="form.appointable_id" 
                      :disabled="!form.appointable_type"
                    >
                      <SelectTrigger class="w-full" :class="errors.appointable_id ? 'border-destructive' : ''">
                        <SelectValue :placeholder="
                          form.appointable_type === 'lead' ? 'Select a lead' : 
                          form.appointable_type === 'client' ? 'Select a client' : 
                          'Select a project'
                        " />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem 
                          v-for="item in appointableOptions" 
                          :key="item.id" 
                          :value="item.id"
                        >
                          {{ item.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="errors.appointable_id" class="text-sm text-destructive">
                      {{ errors.appointable_id }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Date and Time Fields -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Date Field -->
                <div class="space-y-2">
                  <Label for="date">Date</Label>
                  <Input
                    id="date"
                    v-model="form.date"
                    type="date"
                    class="w-full"
                    :class="errors.date ? 'border-destructive' : ''"
                    required
                  />
                  <p v-if="errors.date" class="text-sm text-destructive">
                    {{ errors.date }}
                  </p>
                </div>

                <!-- Start Time Field -->
                <div class="space-y-2">
                  <Label for="start_time">Start Time</Label>
                  <Input
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    class="w-full"
                    :class="errors.start_time ? 'border-destructive' : ''"
                    required
                  />
                  <p v-if="errors.start_time" class="text-sm text-destructive">
                    {{ errors.start_time }}
                  </p>
                </div>

                <!-- End Time Field -->
                <div class="space-y-2">
                  <Label for="end_time">End Time</Label>
                  <Input
                    id="end_time"
                    v-model="form.end_time"
                    type="time"
                    class="w-full"
                    :class="errors.end_time ? 'border-destructive' : ''"
                    required
                  />
                  <p v-if="errors.end_time" class="text-sm text-destructive">
                    {{ errors.end_time }}
                  </p>
                </div>
              </div>

              <!-- Status Field -->
              <div class="space-y-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger class="w-full" :class="errors.status ? 'border-destructive' : ''">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="confirmed">Confirmed</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="errors.status" class="text-sm text-destructive">
                  {{ errors.status }}
                </p>
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
                          :disabled="!isFormValid()"
                        >
                          <Calendar class="h-4 w-4" />
                          Create Appointment
                        </Button>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent v-if="!isFormValid()">
                      <div class="space-y-1">
                        <p v-if="!form.title" class="text-sm">Title is required</p>
                        <p v-else-if="!form.date" class="text-sm">Date is required</p>
                        <p v-else-if="!form.start_time" class="text-sm">Start time is required</p>
                        <p v-else-if="!form.end_time" class="text-sm">End time is required</p>
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
            </form>
          </CardContent>
        </Card>

        <!-- Sidebar Information -->
        <div class="space-y-6">
          <!-- Status Guide Card -->
          <Card class="border shadow-sm">
            <CardHeader>
              <CardTitle class="text-lg">Appointment Status Guide</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <!-- Pending -->
              <div class="space-y-2 rounded-lg border border-blue-500/20 bg-blue-500/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-blue-500"></div>
                  <span class="font-medium text-blue-600">Pending</span>
                </div>
                <p class="text-muted-foreground">
                  Appointment is scheduled but not yet confirmed by all parties.
                </p>
              </div>

              <!-- Confirmed -->
              <div class="space-y-2 rounded-lg border border-green-500/20 bg-green-500/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-green-500"></div>
                  <span class="font-medium text-green-600">Confirmed</span>
                </div>
                <p class="text-muted-foreground">
                  All parties have confirmed attendance. Ready to proceed.
                </p>
              </div>

              <!-- Cancelled -->
              <div class="space-y-2 rounded-lg border border-red-500/20 bg-red-500/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-red-500"></div>
                  <span class="font-medium text-red-600">Cancelled</span>
                </div>
                <p class="text-muted-foreground">
                  Appointment has been cancelled and will not take place.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Tips Card -->
          <Card class="border shadow-sm">
            <CardHeader>
              <CardTitle class="text-lg">Quick Tips</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
              <div class="flex items-start gap-3">
                <Clock class="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                <p class="text-muted-foreground">
                  Ensure end time is after start time for proper scheduling.
                </p>
              </div>
              <div class="flex items-start gap-3">
                <Calendar class="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                <p class="text-muted-foreground">
                  Link appointments to relevant leads, clients, or projects for better organization.
                </p>
              </div>
              <div class="flex items-start gap-3">
                <div class="h-4 w-4 mt-0.5 text-primary flex-shrink-0">📧</div>
                <p class="text-muted-foreground">
                  Notifications will be sent to relevant team members upon creation.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
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
import { ArrowLeft, Save, Calendar } from 'lucide-vue-next'
import { update, index } from '@/actions/App/Http/Controllers/AppointmentController';
import { reactive, computed } from 'vue'

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

interface Appointment {
  id: number
  title: string
  description: string | null
  appointable_type: string
  appointable_id: number | null
  date: string
  start_time: string
  end_time: string
  status: 'pending' | 'confirmed' | 'cancelled'
  created_by: number
  creator?: User | null
  appointable?: {
    id: number
    type: string
    name: string
  } | null
  created_at: string
  updated_at: string
}

interface Props {
  appointment: Appointment
  users: User[]
  leads: Lead[]
  clients: Client[]
  projects: Project[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const form = reactive({
  title: props.appointment.title,
  description: props.appointment.description || '',
  appointable_type: props.appointment.appointable_type,
  appointable_id: props.appointment.appointable_id,
  date: props.appointment.date,
  start_time: props.appointment.start_time,
  end_time: props.appointment.end_time,
  status: props.appointment.status
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Appointments', href: index.url() },
  { title: `Edit ${props.appointment.title}`, href: '#' },
]

// Get available options based on selected type
const appointableOptions = computed(() => {
  switch (form.appointable_type) {
    case 'App\\Models\\Lead':
      return props.leads
    case 'App\\Models\\Client':
      return props.clients
    case 'App\\Models\\Project':
      return props.projects
    default:
      return []
  }
})

function submit() {
  router.put(update.url(props.appointment.id), form)
}
</script>

<template>
  <Head :title="`Edit ${props.appointment.title}`" />
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
            <h1 class="text-3xl font-bold tracking-tight">Edit Appointment</h1>
            <p class="text-muted-foreground mt-1">
              Update appointment details for {{ props.appointment.title }}
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
        <Card class="lg:col-span-2 border">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calendar class="h-5 w-5" />
              Appointment Details
            </CardTitle>
            <CardDescription>
              Update the appointment details and modify scheduling
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

              <!-- Linked To Fields -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Appointable Type -->
                <div class="space-y-2">
                  <Label for="appointable_type">Link To</Label>
                  <Select v-model="form.appointable_type">
                    <SelectTrigger class="w-full" :class="errors.appointable_type ? 'border-destructive' : ''">
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="App\\Models\\Lead">Lead</SelectItem>
                      <SelectItem value="App\\Models\\Client">Client</SelectItem>
                      <SelectItem value="App\\Models\\Project">Project</SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="errors.appointable_type" class="text-sm text-destructive">
                    {{ errors.appointable_type }}
                  </p>
                </div>

                <!-- Appointable ID -->
                <div class="space-y-2">
                  <Label for="appointable_id">Select Record</Label>
                  <Select 
                    v-model="form.appointable_id" 
                    :disabled="!form.appointable_type"
                  >
                    <SelectTrigger class="w-full" :class="errors.appointable_id ? 'border-destructive' : ''">
                      <SelectValue placeholder="Select record" />
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
                          :disabled="!form.title || !form.date"
                        >
                          <Save class="h-4 w-4" />
                          Save Changes
                        </Button>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent v-if="!form.title || !form.date">
                      <p v-if="!form.title">Title is required</p>
                      <p v-else-if="!form.date">Date is required</p>
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
          <!-- Appointment Summary Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Appointment Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <Calendar class="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p class="font-medium">{{ props.appointment.title }}</p>
                  <p class="text-muted-foreground">
                    {{ new Date(props.appointment.date).toLocaleDateString() }}
                  </p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Current Status:</span>
                  <span 
                    :class="{
                      'text-yellow-600 font-medium': props.appointment.status === 'pending',
                      'text-green-600 font-medium': props.appointment.status === 'confirmed',
                      'text-destructive font-medium': props.appointment.status === 'cancelled'
                    }"
                    class="capitalize"
                  >
                    {{ props.appointment.status }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Time:</span>
                  <span class="font-medium">
                    {{ props.appointment.start_time }} - {{ props.appointment.end_time }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Linked To:</span>
                  <span class="font-medium">
                    {{ props.appointment.appointable?.name || 'Not linked' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Created By:</span>
                  <span class="font-medium">
                    {{ props.appointment.creator?.name || 'Unknown' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Last Updated:</span>
                  <span class="font-medium">
                    {{ new Date(props.appointment.updated_at).toLocaleDateString() }}
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
                <p class="text-muted-foreground">Update status promptly to reflect current state</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Ensure time slots don't overlap with other appointments</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Link to relevant records for better organization</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Notify participants of any schedule changes</p>
              </div>
            </CardContent>
          </Card>

          <!-- Status Guide Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Status Guide</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                <div>
                  <span class="font-medium text-yellow-600">Pending</span>
                  <p class="text-muted-foreground">Awaiting confirmation</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <div>
                  <span class="font-medium text-green-600">Confirmed</span>
                  <p class="text-muted-foreground">All parties confirmed</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-destructive rounded-full"></div>
                <div>
                  <span class="font-medium text-destructive">Cancelled</span>
                  <p class="text-muted-foreground">Appointment cancelled</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
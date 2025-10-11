<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
    CardDescription
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { 
    Edit, 
    Trash2, 
    Briefcase,
    FileText,
    Activity,
    ClipboardList,
    User,
    Building,
    Receipt,
    Mail,
    Phone,
    MapPin,
    DollarSign
} from 'lucide-vue-next'
import { ref } from 'vue'
import { edit, destroy, index } from '@/actions/App/Http/Controllers/ClientController';
import { create as createProject } from '@/actions/App/Http/Controllers/ProjectController';
import { create as createInvoice } from '@/actions/App/Http/Controllers/InvoiceController';

interface User {
  id: number
  name: string
  email: string
}

interface Client {
  id: number
  name: string
  email: string
  phone: string
  company: string
  address: string
  industry?: string
  revenue?: number
  status: string
  lead_id?: number
  assigned_to?: number
  created_by: number
  created_at: string
  updated_at: string
  assignee?: User
  creator?: User
  lead?: {
    id: number
    name: string
  }
}

interface Project {
  id: number
  name: string
  description: string | null
  status: string
  start_date: string | null
  end_date: string | null
  created_at: string
}

interface Invoice {
  id: number
  invoice_number: string
  amount: number
  status: string
  due_date: string
  created_at: string
}

interface Note {
  id: number
  content: string
  user: User
  created_at: string
}

interface Activity {
  id: number
  description: string
  causer: User | null
  created_at: string
  properties?: any
}

interface Props {
  client: Client
  projects: Project[]
  invoices: Invoice[]
  notes: Note[]
  activities: Activity[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Clients', href: index.url() },
  { title: props.client.name, href: '#' },
]

const showDeleteDialog = ref(false)

function getStatusColor(status: string) {
  const colors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    lead: 'bg-blue-100 text-blue-800',
    vip: 'bg-purple-100 text-purple-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

function getProjectStatusColor(status: string) {
  const colors = {
    planning: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    on_hold: 'bg-orange-100 text-orange-800',
    completed: 'bg-green-100 text-green-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

function getInvoiceStatusColor(status: string) {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

function confirmDelete() {
  showDeleteDialog.value = true
}

function deleteClient() {
  router.delete(destroy.url(props.client.id))
  showDeleteDialog.value = false
}

function cancelDelete() {
  showDeleteDialog.value = false
}

function getDaysRemaining(dueDate: string) {
  const today = new Date()
  const due = new Date(dueDate)
  const diffTime = due.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}
</script>

<template>
  <Head :title="props.client.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Header Section -->
      <div class="mb-6">
        <div
          class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
          <div class="space-y-1">
            <h1
              class="text-2xl font-bold tracking-tight sm:text-3xl"
            >
              {{ props.client.name }}
            </h1>
            <p class="text-sm text-muted-foreground sm:text-base">
              Client since {{ formatDate(props.client.created_at) }}
              <span v-if="props.client.company" class="ml-2">• {{ props.client.company }}</span>
            </p>
          </div>

          <!-- All buttons container -->
          <div
            class="flex w-full items-center justify-end gap-2 lg:w-auto lg:justify-normal lg:gap-3"
          >
            <!-- Add Project Button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Link :href="createProject.url() + `?client_id=${props.client.id}`">
                    <Button
                      variant="outline"
                      class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                      size="sm"
                    >
                      <Briefcase class="h-4 w-4" />
                      <span class="hidden lg:inline">Add Project</span>
                    </Button>
                  </Link>
                </TooltipTrigger>
                <TooltipContent class="lg:hidden">
                  <p>Create new project for this client</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <!-- Add Invoice Button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Link :href="createInvoice.url() + `?client_id=${props.client.id}`">
                    <Button
                      variant="outline"
                      class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                      size="sm"
                    >
                      <Receipt class="h-4 w-4" />
                      <span class="hidden lg:inline">Add Invoice</span>
                    </Button>
                  </Link>
                </TooltipTrigger>
                <TooltipContent class="lg:hidden">
                  <p>Create new invoice for this client</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <!-- Edit Button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Link :href="edit.url(props.client.id)">
                    <Button
                      variant="outline"
                      class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                      size="sm"
                    >
                      <Edit class="h-4 w-4" />
                      <span class="hidden lg:inline">Edit</span>
                    </Button>
                  </Link>
                </TooltipTrigger>
                <TooltipContent class="lg:hidden">
                  <p>Edit client information</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <!-- Delete Button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button 
                    variant="destructive"
                    class="h-9 w-9 p-0 md:px-4 md:py-2 lg:h-auto lg:w-auto"
                    size="sm"
                    @click="confirmDelete"
                  >
                    <Trash2 class="h-4 w-4" />
                    <span class="hidden lg:inline">Delete</span>
                  </Button>
                </TooltipTrigger>
                <TooltipContent class="lg:hidden">
                  <p>Delete this client</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Client Info & Relationships -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Client Information Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Information -->
            <Card class="border">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Building class="h-5 w-5" />
                  Company Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Company Name</Label>
                  <p class="text-sm">{{ props.client.company || 'Not specified' }}</p>
                </div>

                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Industry</Label>
                  <p class="text-sm">{{ props.client.industry || 'Not specified' }}</p>
                </div>

                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Annual Revenue</Label>
                  <p class="text-sm flex items-center gap-2">
                    <DollarSign class="h-4 w-4" />
                    {{ props.client.revenue ? formatCurrency(props.client.revenue) : 'Not specified' }}
                  </p>
                </div>

                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Status</Label>
                  <Badge :class="getStatusColor(props.client.status)" class="capitalize">
                    {{ props.client.status }}
                  </Badge>
                </div>
              </CardContent>
            </Card>

            <!-- Contact Information -->
            <Card class="border">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <User class="h-5 w-5" />
                  Contact Information
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                  <p class="text-sm flex items-center gap-2">
                    <Mail class="h-4 w-4" />
                    <a v-if="props.client.email" :href="`mailto:${props.client.email}`" class="text-primary hover:underline">
                      {{ props.client.email }}
                    </a>
                    <span v-else class="text-muted-foreground">Not specified</span>
                  </p>
                </div>

                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Phone</Label>
                  <p class="text-sm flex items-center gap-2">
                    <Phone class="h-4 w-4" />
                    <a v-if="props.client.phone" :href="`tel:${props.client.phone}`" class="text-primary hover:underline">
                      {{ props.client.phone }}
                    </a>
                    <span v-else class="text-muted-foreground">Not specified</span>
                  </p>
                </div>

                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Address</Label>
                  <p class="text-sm flex items-start gap-2">
                    <MapPin class="h-4 w-4 mt-0.5 flex-shrink-0" />
                    <span class="whitespace-pre-wrap">{{ props.client.address || 'Not specified' }}</span>
                  </p>
                </div>

                <div class="space-y-1 pt-2 border-t">
                  <Label class="text-sm font-medium text-muted-foreground">Assigned User</Label>
                  <p class="text-sm">{{ props.client.assignee?.name || 'Not assigned' }}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Projects List -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Briefcase class="h-5 w-5" />
                Projects
                <Badge variant="secondary" class="ml-2">
                  {{ props.projects.length }}
                </Badge>
              </CardTitle>
              <CardDescription>
                All projects associated with this client
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="props.projects.length > 0" class="space-y-3">
                <div 
                  v-for="project in props.projects" 
                  :key="project.id"
                  class="p-3 border rounded-lg hover:bg-muted/5 transition-colors"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <Link :href="`/projects/${project.id}`" class="font-medium text-sm text-primary hover:underline">
                        {{ project.name }}
                      </Link>
                      <div class="flex items-center gap-2 mt-1">
                        <Badge :class="getProjectStatusColor(project.status)" class="capitalize text-xs">
                          {{ project.status.replace('_', ' ') }}
                        </Badge>
                        <span v-if="project.start_date" class="text-xs text-muted-foreground">
                          Started {{ formatDate(project.start_date) }}
                        </span>
                      </div>
                      <p v-if="project.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">
                        {{ project.description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Briefcase class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                <p class="text-muted-foreground">No projects yet</p>
                <p class="text-sm text-muted-foreground mt-1">
                  Create the first project for this client
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Invoices List -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Receipt class="h-5 w-5" />
                Invoices
                <Badge variant="secondary" class="ml-2">
                  {{ props.invoices.length }}
                </Badge>
              </CardTitle>
              <CardDescription>
                All invoices for this client
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="props.invoices.length > 0" class="space-y-3">
                <div 
                  v-for="invoice in props.invoices" 
                  :key="invoice.id"
                  class="p-3 border rounded-lg hover:bg-muted/5 transition-colors"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h4 class="font-medium text-sm">Invoice #{{ invoice.invoice_number }}</h4>
                      <div class="flex items-center gap-2 mt-1">
                        <Badge :class="getInvoiceStatusColor(invoice.status)" class="capitalize text-xs">
                          {{ invoice.status }}
                        </Badge>
                        <span class="text-xs text-muted-foreground">
                          Due {{ formatDate(invoice.due_date) }}
                        </span>
                        <span v-if="getDaysRemaining(invoice.due_date) < 0 && invoice.status !== 'paid'" 
                              class="text-xs text-red-600 font-medium">
                          (Overdue)
                        </span>
                      </div>
                    </div>
                    <div class="text-right">
                      <p class="text-sm font-medium">{{ formatCurrency(invoice.amount) }}</p>
                      <p class="text-xs text-muted-foreground">
                        {{ formatDate(invoice.created_at) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Receipt class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                <p class="text-muted-foreground">No invoices yet</p>
                <p class="text-sm text-muted-foreground mt-1">
                  Create the first invoice for this client
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Notes Section -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <ClipboardList class="h-5 w-5" />
                Notes
                <Badge variant="secondary" class="ml-2">
                  {{ props.notes.length }}
                </Badge>
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="props.notes.length > 0" class="space-y-4">
                <div 
                  v-for="note in props.notes" 
                  :key="note.id"
                  class="p-4 border rounded-lg bg-muted/5"
                >
                  <p class="text-sm whitespace-pre-wrap">{{ note.content }}</p>
                  <div class="flex justify-between items-center mt-3 pt-3 border-t">
                    <span class="text-xs text-muted-foreground">
                      Added by {{ note.user.name }}
                    </span>
                    <span class="text-xs text-muted-foreground">
                      {{ formatDate(note.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div v-else class="text-center py-8">
                <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                <p class="text-muted-foreground">No notes yet</p>
                <p class="text-sm text-muted-foreground mt-1">
                  Add the first note to track important client information
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column - Activity & Statistics -->
        <div class="space-y-6">
          <!-- Activity Log -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Activity class="h-5 w-5" />
                Activity Log
                <Badge variant="secondary" class="ml-2">
                  {{ props.activities.length }}
                </Badge>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="props.activities.length > 0" class="space-y-4">
                <div 
                  v-for="activity in props.activities" 
                  :key="activity.id"
                  class="flex gap-3 pb-4 last:pb-0 border-b last:border-b-0"
                >
                  <div class="flex-shrink-0 w-2 h-2 mt-2 bg-primary rounded-full"></div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-foreground">{{ activity.description }}</p>
                    <div class="flex justify-between items-center mt-1">
                      <span class="text-xs text-muted-foreground">
                        By {{ activity.causer?.name || 'System' }}
                      </span>
                      <span class="text-xs text-muted-foreground">
                        {{ formatDate(activity.created_at) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-else class="text-center py-8">
                <Activity class="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                <p class="text-muted-foreground">No activity yet</p>
                <p class="text-sm text-muted-foreground mt-1">
                  Activity will appear here as changes are made
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Client Statistics -->
          <Card class="border">
            <CardHeader>
              <CardTitle>Client Statistics</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <div class="text-center p-4 border rounded-lg bg-muted/5 cursor-default">
                        <div class="text-2xl font-bold text-primary">{{ props.projects.length }}</div>
                        <div class="text-sm text-muted-foreground mt-1">Projects</div>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Total projects for this client</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <div class="text-center p-4 border rounded-lg bg-muted/5 cursor-default">
                        <div class="text-2xl font-bold text-primary">{{ props.invoices.length }}</div>
                        <div class="text-sm text-muted-foreground mt-1">Invoices</div>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Total invoices for this client</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </div>

              <div class="pt-4 border-t">
                <div class="space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Created</span>
                    <span class="text-sm font-medium">{{ formatDate(props.client.created_at) }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Last Updated</span>
                    <span class="text-sm font-medium">{{ formatDate(props.client.updated_at) }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Created By</span>
                    <span class="text-sm font-medium">{{ props.client.creator?.name || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Delete Confirmation Dialog -->
      <ConfirmationDialog
        :show="showDeleteDialog"
        title="Delete Client"
        :description="`Are you sure you want to delete ${props.client.name}? This action cannot be undone and will also delete all associated projects, invoices, and related data.`"
        confirm-text="Delete Client"
        cancel-text="Cancel"
        variant="destructive"
        @confirm="deleteClient"
        @cancel="cancelDelete"
      />
    </div>
  </AppLayout>
</template>
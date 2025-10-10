<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { 
    Card, 
    CardContent, 
    CardHeader, 
    CardTitle,
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
    ArrowLeft, 
    Edit, 
    Trash2, 
    User, 
    Mail, 
    Phone, 
    Building, 
    Calendar,
    FileText,
    CheckCircle,
    Activity,
    ClipboardList
} from 'lucide-vue-next'
import { edit, destroy, index, convert } from '@/actions/App/Http/Controllers/LeadController';
import { ref } from 'vue'

interface User {
  id: number
  name: string
  email: string
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
}

interface Task {
  id: number
  title: string
  status: string
}

interface Document {
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
  score: number | null
  assigned_to: number | null
  assignee?: User | null
  creator?: User | null
  created_at: string
  updated_at: string
  notes: Note[]
  activities: Activity[]
  tasks: Task[]
  documents: Document[]
}

interface Props {
  lead: Lead
  notes: Note[]
  activities: Activity[]
  tasks: Task[]
  documents: Document[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leads', href: index.url() },
  { title: props.lead.name, href: '#' },
]

const showDeleteDialog = ref(false)
const showConvertDialog = ref(false)

// Check if lead is converted based on status
const isConverted = ref(props.lead.status === 'qualified')

function getStatusColor(status: string) {
  const colors = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    lost: 'bg-red-100 text-red-800'
  }
  return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

function getScoreColor(score: number | null) {
  if (!score) return 'bg-gray-100 text-gray-800'
  if (score >= 80) return 'bg-green-100 text-green-800'
  if (score >= 60) return 'bg-blue-100 text-blue-800'
  if (score >= 40) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function confirmDelete() {
  showDeleteDialog.value = true
}

function deleteLead() {
  router.delete(destroy.url(props.lead.id))
  showDeleteDialog.value = false
}

function cancelDelete() {
  showDeleteDialog.value = false
}

function confirmConvert() {
  showConvertDialog.value = true
}

function convertToClient() {
  router.post(convert.url(props.lead.id))
  showConvertDialog.value = false
}

function cancelConvert() {
  showConvertDialog.value = false
}
</script>

<template>
  <Head :title="props.lead.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Header Section -->
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Link :href="index.url()">
                  <Button variant="ghost" size="icon" class="h-9 w-9">
                    <ArrowLeft class="h-4 w-4" />
                  </Button>
                </Link>
              </TooltipTrigger>
              <TooltipContent>
                <p>Back to leads</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-3xl font-bold tracking-tight">{{ props.lead.name }}</h1>
              <Badge :class="getStatusColor(props.lead.status)" class="capitalize">
                {{ props.lead.status }}
              </Badge>
              <Badge v-if="props.lead.score" :class="getScoreColor(props.lead.score)">
                Score: {{ props.lead.score }}
              </Badge>
            </div>
            <p class="text-muted-foreground mt-1">
              {{ props.lead.company || 'No company specified' }}
            </p>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <TooltipProvider>
            <!-- Convert to Client Button -->
            <Tooltip v-if="!isConverted">
              <TooltipTrigger as-child>
                <Button 
                  @click="confirmConvert"
                  class="flex items-center gap-2"
                >
                  <CheckCircle class="h-4 w-4" />
                  Convert to Client
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>Convert this lead to a client</p>
              </TooltipContent>
            </Tooltip>
            
            <!-- Edit Button -->
            <Tooltip>
              <TooltipTrigger as-child>
                <Link :href="edit.url(props.lead.id)">
                  <Button variant="outline" class="flex items-center gap-2">
                    <Edit class="h-4 w-4" />
                    Edit
                  </Button>
                </Link>
              </TooltipTrigger>
              <TooltipContent>
                <p>Edit lead information</p>
              </TooltipContent>
            </Tooltip>
            
            <!-- Delete Button -->
            <Tooltip>
              <TooltipTrigger as-child>
                <Button 
                  variant="destructive" 
                  class="flex items-center gap-2"
                  @click="confirmDelete"
                >
                  <Trash2 class="h-4 w-4" />
                  Delete
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>Delete this lead</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Lead Info & Notes -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Lead Information Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <User class="h-5 w-5" />
                Lead Information
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Contact Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Mail class="h-4 w-4" />
                    <Label class="text-sm font-medium">Email</Label>
                  </div>
                  <p class="text-sm">
                    <a v-if="props.lead.email" :href="`mailto:${props.lead.email}`" class="text-primary hover:underline">
                      {{ props.lead.email }}
                    </a>
                    <span v-else class="text-muted-foreground">No email</span>
                  </p>
                </div>
                
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Phone class="h-4 w-4" />
                    <Label class="text-sm font-medium">Phone</Label>
                  </div>
                  <p class="text-sm">
                    <a v-if="props.lead.phone" :href="`tel:${props.lead.phone}`" class="text-primary hover:underline">
                      {{ props.lead.phone }}
                    </a>
                    <span v-else class="text-muted-foreground">No phone</span>
                  </p>
                </div>
              </div>

              <!-- Company & Source -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Building class="h-4 w-4" />
                    <Label class="text-sm font-medium">Company</Label>
                  </div>
                  <p class="text-sm">{{ props.lead.company || 'No company' }}</p>
                </div>
                
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Source</Label>
                  <p class="text-sm">{{ props.lead.source || 'No source specified' }}</p>
                </div>
              </div>

              <!-- Assignment Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Created By</Label>
                  <p class="text-sm">{{ props.lead.creator?.name || 'N/A' }}</p>
                </div>
                
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Assigned To</Label>
                  <p class="text-sm">{{ props.lead.assignee?.name || 'Unassigned' }}</p>
                </div>
              </div>

              <!-- Dates -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-muted-foreground">
                    <Calendar class="h-4 w-4" />
                    <Label class="text-sm font-medium">Created</Label>
                  </div>
                  <p class="text-sm">{{ formatDate(props.lead.created_at) }}</p>
                </div>
                
                <div class="space-y-1">
                  <Label class="text-sm font-medium text-muted-foreground">Last Updated</Label>
                  <p class="text-sm">{{ formatDate(props.lead.updated_at) }}</p>
                </div>
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
            <CardContent class="space-y-6">
              <!-- Notes List -->
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
                  Add the first note to track important information
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column - Activity & Related Items -->
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

          <!-- Related Items -->
          <Card class="border">
            <CardHeader>
              <CardTitle>Related Items</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <div class="text-center p-4 border rounded-lg bg-muted/5 cursor-default">
                        <div class="text-2xl font-bold text-primary">{{ props.tasks.length }}</div>
                        <div class="text-sm text-muted-foreground mt-1">Tasks</div>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Total tasks associated with this lead</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <div class="text-center p-4 border rounded-lg bg-muted/5 cursor-default">
                        <div class="text-2xl font-bold text-primary">{{ props.documents.length }}</div>
                        <div class="text-sm text-muted-foreground mt-1">Documents</div>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Total documents associated with this lead</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </div>
              
              <div class="pt-4 border-t">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Client Status</span>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <Badge :class="isConverted ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                          {{ isConverted ? 'Converted' : 'Not Converted' }}
                        </Badge>
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>{{ isConverted ? 'This lead has been converted to a client' : 'This lead has not been converted to a client yet' }}</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Delete Confirmation Dialog -->
      <ConfirmationDialog
        :show="showDeleteDialog"
        title="Delete Lead"
        :description="`Are you sure you want to delete ${props.lead.name}? This action cannot be undone.`"
        confirm-text="Delete Lead"
        cancel-text="Cancel"
        variant="destructive"
        @confirm="deleteLead"
        @cancel="cancelDelete"
      />

      <!-- Convert to Client Confirmation Dialog -->
      <ConfirmationDialog
        :show="showConvertDialog"
        title="Convert to Client"
        :description="`Are you sure you want to convert ${props.lead.name} to a client? This will create a new client record and move this lead to the clients section.`"
        confirm-text="Convert to Client"
        cancel-text="Cancel"
        variant="default"
        @confirm="convertToClient"
        @cancel="cancelConvert"
      />
    </div>
  </AppLayout>
</template> 
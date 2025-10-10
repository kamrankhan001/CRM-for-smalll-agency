<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
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
import { ArrowLeft, Save, MessageSquare } from 'lucide-vue-next'
import { update, index } from '@/actions/App/Http/Controllers/NoteController';
import { reactive } from 'vue'

interface User { 
  id: number; 
  name: string 
}

interface Noteable {
  id: number;
  name: string;
  type: string;
}

interface Note {
  id: number;
  content: string;
  user: User;
  noteable?: Noteable | null;
  created_at: string;
  updated_at: string;
}

interface Props { 
  note: Note;
  leads: Array<{ id: number; name: string }>;
  clients: Array<{ id: number; name: string }>;
  projects: Array<{ id: number; name: string }>;
  errors: Record<string, string>;
}

const props = defineProps<Props>()

// Initialize form with current note data
const form = reactive({
  content: props.note.content,
  noteable_type: props.note.noteable?.type.toLowerCase() as 'lead' | 'client' | 'project' || 'lead',
  noteable_id: props.note.noteable?.id || null as number | null,
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Notes', href: index.url() },
  { title: `Edit Note`, href: '#' },
]

function submit() {
  router.put(update.url(props.note.id), form)
}

// Check if form is valid for submit button tooltip
const isFormValid = () => {
  return form.content && form.noteable_id;
};
</script>

<template>
  <Head :title="`Edit Note`" />
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
            <h1 class="text-3xl font-bold tracking-tight">Edit Note</h1>
            <p class="text-muted-foreground mt-1">
              Update note information and linking
            </p>
          </div>
        </div>
        <Link :href="index.url()" class="hidden md:block">
          <Button variant="outline" class="flex items-center gap-2">
            <ArrowLeft class="h-4 w-4" />
            Back to Notes
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Card -->
        <Card class="lg:col-span-2 border">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <MessageSquare class="h-5 w-5" />
              Note Information
            </CardTitle>
            <CardDescription>
              Update note details and modify entity linking
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Content Field -->
              <div class="space-y-2">
                <Label for="content">Note Content</Label>
                <Textarea
                  id="content"
                  v-model="form.content"
                  placeholder="Write your note here..."
                  class="w-full min-h-[100px]"
                  :class="props.errors.content ? 'border-destructive' : ''"
                  required
                />
                <p v-if="props.errors.content" class="text-sm text-destructive">
                  {{ props.errors.content }}
                </p>
              </div>

              <!-- Linked Entity Section -->
              <div class="space-y-4">
                <Label>Link To</Label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Entity Type -->
                  <div class="space-y-2">
                    <Label for="noteable_type">Entity Type</Label>
                    <Select v-model="form.noteable_type">
                      <SelectTrigger class="w-full" :class="props.errors.noteable_type ? 'border-destructive' : ''">
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="lead">Lead</SelectItem>
                        <SelectItem value="client">Client</SelectItem>
                        <SelectItem value="project">Project</SelectItem>
                      </SelectContent>
                    </Select>
                    <p v-if="props.errors.noteable_type" class="text-sm text-destructive">
                      {{ props.errors.noteable_type }}
                    </p>
                  </div>

                  <!-- Entity Selection -->
                  <div class="space-y-2">
                    <Label v-if="form.noteable_type === 'lead'" for="noteable_id">Select Lead</Label>
                    <Label v-else-if="form.noteable_type === 'client'" for="noteable_id">Select Client</Label>
                    <Label v-else for="noteable_id">Select Project</Label>
                    <Select v-model="form.noteable_id">
                      <SelectTrigger class="w-full" :class="props.errors.noteable_id ? 'border-destructive' : ''">
                        <SelectValue :placeholder="
                          form.noteable_type === 'lead' ? 'Select a lead' : 
                          form.noteable_type === 'client' ? 'Select a client' : 
                          'Select a project'
                        " />
                      </SelectTrigger>
                      <SelectContent>
                        <template v-if="form.noteable_type === 'lead'">
                          <SelectItem 
                            v-for="lead in leads" 
                            :key="lead.id" 
                            :value="lead.id"
                          >
                            {{ lead.name }}
                          </SelectItem>
                        </template>
                        <template v-else-if="form.noteable_type === 'client'">
                          <SelectItem 
                            v-for="client in clients" 
                            :key="client.id" 
                            :value="client.id"
                          >
                            {{ client.name }}
                          </SelectItem>
                        </template>
                        <template v-else>
                          <SelectItem 
                            v-for="project in projects" 
                            :key="project.id" 
                            :value="project.id"
                          >
                            {{ project.name }}
                          </SelectItem>
                        </template>
                      </SelectContent>
                    </Select>
                    <p v-if="props.errors.noteable_id" class="text-sm text-destructive">
                      {{ props.errors.noteable_id }}
                    </p>
                  </div>
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
                          :disabled="!isFormValid()"
                        >
                          <Save class="h-4 w-4" />
                          Save Changes
                        </Button>
                      </div>
                    </TooltipTrigger>
                    <TooltipContent v-if="!isFormValid()">
                      <div class="space-y-1">
                        <p v-if="!form.content" class="text-sm">Note content is required</p>
                        <p v-else-if="!form.noteable_id" class="text-sm">Please select an entity to link to</p>
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
          <!-- Note Summary Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">Note Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-primary">
                    {{ props.note.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <p class="font-medium">{{ props.note.user.name }}</p>
                  <p class="text-muted-foreground text-xs">Created {{ new Date(props.note.created_at).toLocaleDateString() }}</p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Current Link:</span>
                  <span class="font-medium">
                    {{ props.note.noteable?.name || 'Not linked' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Entity Type:</span>
                  <span class="font-medium capitalize">
                    {{ props.note.noteable?.type?.toLowerCase() || 'None' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Last Updated:</span>
                  <span class="font-medium">
                    {{ new Date(props.note.updated_at).toLocaleDateString() }}
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
                <p class="text-muted-foreground">Keep content updated with latest information</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">You can change which entity this note is linked to</p>
              </div>
              <div class="flex items-start gap-2">
                <div class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5"></div>
                <p class="text-muted-foreground">Maintain clear and concise note content</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
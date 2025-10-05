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
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ArrowLeft, Plus, MessageSquare } from 'lucide-vue-next'
import { store, index } from '@/actions/App/Http/Controllers/NoteController';
import { reactive } from 'vue'

interface Props { 
  leads: Array<{ id: number; name: string }>;
  clients: Array<{ id: number; name: string }>;
}

defineProps<Props>()

const form = reactive({
  content: '',
  noteable_type: 'lead' as 'lead' | 'client',
  noteable_id: null as number | null,
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Notes', href: index.url() },
  { title: 'Create Note', href: '#' },
]

function submit() {
  router.post(store.url(), form)
}
</script>

<template>
  <Head title="Create Note" />
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
              Create New Note
            </h1>
            <p class="mt-1 text-muted-foreground">
              Add a new note and link it to a lead or client
            </p>
          </div>
        </div>
        <Link :href="index.url()">
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
              Enter note details and link to a lead or client
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
                  required
                />
              </div>

              <!-- Linked Entity Section -->
              <div class="space-y-4">
                <Label>Link To</Label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Entity Type -->
                  <div class="space-y-2">
                    <Label for="noteable_type">Entity Type</Label>
                    <Select v-model="form.noteable_type">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="lead">Lead</SelectItem>
                        <SelectItem value="client">Client</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Entity Selection -->
                  <div class="space-y-2">
                    <Label v-if="form.noteable_type === 'lead'" for="noteable_id">Select Lead</Label>
                    <Label v-else for="noteable_id">Select Client</Label>
                    <Select v-model="form.noteable_id">
                      <SelectTrigger class="w-full">
                        <SelectValue :placeholder="form.noteable_type === 'lead' ? 'Select a lead' : 'Select a client'" />
                      </SelectTrigger>
                      <SelectContent>
                        <template v-if="form.noteable_type === 'lead'">
                          <SelectItem v-for="lead in leads" :key="lead.id" :value="lead.id">
                            {{ lead.name }}
                          </SelectItem>
                        </template>
                        <template v-else>
                          <SelectItem v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                          </SelectItem>
                        </template>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 pt-4">
                <Button 
                  type="submit" 
                  class="flex-1 gap-2"
                  :disabled="!form.content || !form.noteable_id"
                >
                  <Plus class="h-4 w-4" />
                  Create Note
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
          <!-- About Notes Card -->
          <Card class="border">
            <CardHeader>
              <CardTitle class="text-lg">About Notes</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
              <div class="space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-primary"></div>
                  <span class="font-medium text-primary">Lead Notes</span>
                </div>
                <p class="text-muted-foreground">
                  Track interactions, follow-ups, and qualification progress for potential clients.
                </p>
              </div>

              <div class="space-y-2 rounded-lg border border-secondary/20 bg-secondary/5 p-4">
                <div class="flex items-center gap-2">
                  <div class="h-2.5 w-2.5 rounded-sm bg-secondary"></div>
                  <span class="font-medium text-secondary">Client Notes</span>
                </div>
                <p class="text-muted-foreground">
                  Document support interactions, feature requests, and relationship management details.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
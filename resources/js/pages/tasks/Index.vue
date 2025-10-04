<script setup lang="ts">
import ActionButtons from '@/components/ActionButtons.vue';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Import Wayfinder-generated actions
import {
    create,
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/TaskController';

interface User {
  id: number
  name: string
}

interface Taskable {
  id: number
  name: string
  type: string
}

interface Task {
  id: number
  title: string
  description: string | null
  status: 'pending' | 'in_progress' | 'completed'
  due_date: string | null
  assignee?: User | null
  creator?: User | null
  taskable?: Taskable | null
  created_at: string
  updated_at: string
}

interface PaginationLinks {
  url: string | null
  label: string
  active: boolean
}

interface PaginatedTasks {
  data: Task[]
  links: PaginationLinks[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  }
}

const props = defineProps<{
  tasks: PaginatedTasks
  filters: {
    search?: string;
    status?: string;
    assigned_to?: string;
    date_from?: string;
    date_to?: string;
  };
  users: User[];
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tasks', href: '#' }]

const showDeleteDialog = ref(false)
const taskToDelete = ref<number | null>(null)

// Filters
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    assigned_to: props.filters.assigned_to || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

function goToEdit(taskId: number) {
    router.get(edit.url(taskId))
}

function confirmDelete(taskId: number) {
    taskToDelete.value = taskId
    showDeleteDialog.value = true
}

function deleteTask() {
    if (taskToDelete.value) {
        router.delete(destroy.url(taskToDelete.value))
    }
    showDeleteDialog.value = false
    taskToDelete.value = null
}

function cancelDelete() {
    showDeleteDialog.value = false
    taskToDelete.value = null
}

const taskBeingDeleted = computed(() =>
    props.tasks.data.find((task) => task.id === taskToDelete.value),
)

// Status badge variant
const getStatusVariant = (status: string) => {
    switch(status) {
        case 'pending': return 'secondary'
        case 'in_progress': return 'default'
        case 'completed': return 'default'
        default: return 'secondary'
    }
}

// Format date to relative time or specific format
const formatDate = (dateString: string | null) => {
    if (!dateString) return 'No due date'
    
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = date.getTime() - now.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays === 0) {
        return 'Today'
    } else if (diffDays === 1) {
        return 'Tomorrow'
    } else if (diffDays === -1) {
        return 'Yesterday'
    } else if (diffDays < 0) {
        return `${Math.abs(diffDays)} days overdue`
    } else if (diffDays < 7) {
        return `In ${diffDays} days`
    } else {
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        })
    }
}

// Get taskable type badge
const getTaskableTypeVariant = (type: string | undefined) => {
    switch(type?.toLowerCase()) {
        case 'lead': return 'default'
        case 'client': return 'secondary'
        default: return 'outline'
    }
}

// Filter functions
function applyFilters() {
    const backendFilters = {
        ...filters.value,
        status: filters.value.status === null ? '' : filters.value.status,
        assigned_to: filters.value.assigned_to === null ? '' : filters.value.assigned_to,
    }

    router.get(index.url(), backendFilters, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.value = {
        search: '',
        status: null,
        assigned_to: null,
        date_from: '',
        date_to: '',
    }
    router.get(index.url(), {}, { preserveState: true, replace: true })
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.status ||
        filters.value.assigned_to ||
        filters.value.date_from ||
        filters.value.date_to
    )
})

// Pagination logic
function goToPage(url: string | null) {
    if (url) router.visit(url)
}

const pageLinks = computed(() => {
    if (!props.tasks.links) return []
    return props.tasks.links.filter(
        (_, index) => index !== 0 && index !== props.tasks.links.length - 1,
    )
})

const showingFrom = computed(() => {
    if (!props.tasks.meta || props.tasks.data.length === 0) return 0
    return props.tasks.meta.from || (props.tasks.meta.current_page - 1) * props.tasks.meta.per_page + 1
})

const showingTo = computed(() => {
    if (!props.tasks.meta || props.tasks.data.length === 0) return 0
    return props.tasks.meta.to || Math.min(props.tasks.meta.current_page * props.tasks.meta.per_page, props.tasks.meta.total)
})

const total = computed(() => props.tasks.meta?.total || 0)
</script>

<template>
  <Head title="Tasks" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Tasks</h1>
          <p class="mt-1 text-muted-foreground">
            Manage your team's tasks and assignments
          </p>
        </div>
        <Link :href="create.url()" class="shrink-0">
          <Button class="flex items-center gap-2">
            <Plus class="h-4 w-4" />
            Add Task
          </Button>
        </Link>
      </div>

      <!-- Filters -->
      <div class="mb-6 rounded-lg border p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
          <!-- Search -->
          <div class="space-y-2">
            <Label for="search">Search</Label>
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
              <Input
                id="search"
                v-model="filters.search"
                type="text"
                placeholder="Search by task title..."
                class="pl-10"
                @keyup.enter="applyFilters"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <div class="space-y-2">
            <Label for="status">Status</Label>
            <Select v-model="filters.status">
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">All Statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="in_progress">In Progress</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Assigned To Filter -->
          <div class="space-y-2">
            <Label for="assigned_to">Assigned To</Label>
            <Select v-model="filters.assigned_to">
              <SelectTrigger>
                <SelectValue placeholder="All users" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">All Users</SelectItem>
                <SelectItem value="unassigned">Unassigned</SelectItem>
                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                  {{ user.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Date From -->
          <div class="space-y-2">
            <Label for="date_from">Due Date From</Label>
            <Input
              id="date_from"
              v-model="filters.date_from"
              type="date"
            />
          </div>

          <!-- Date To -->
          <div class="space-y-2">
            <Label for="date_to">Due Date To</Label>
            <Input
              id="date_to"
              v-model="filters.date_to"
              type="date"
            />
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="mt-4 flex justify-between">
          <Button
            variant="outline"
            size="sm"
            @click="resetFilters"
            :disabled="!hasActiveFilters"
            class="flex items-center gap-2"
          >
            <X class="h-4 w-4" />
            Clear Filters
          </Button>
          <Button size="sm" @click="applyFilters">
            Apply Filters
          </Button>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[250px]">Task</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Due Date</TableHead>
              <TableHead>Assigned To</TableHead>
              <TableHead>Linked To</TableHead>
              <TableHead>Last Updated</TableHead>
              <TableHead class="w-[120px] text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="task in tasks.data" :key="task.id">
              <TableCell class="font-medium">
                <div class="flex items-center space-x-3">
                  <div
                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10"
                  >
                    <span class="text-sm font-medium text-primary">
                      {{ task.title.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ task.title }}</div>
                    <div v-if="task.description" class="text-xs text-muted-foreground truncate max-w-[200px]">
                      {{ task.description }}
                    </div>
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <Badge
                  :variant="getStatusVariant(task.status)"
                  class="capitalize"
                >
                  {{ task.status.replace('_', ' ') }}
                </Badge>
              </TableCell>
              <TableCell class="text-muted-foreground text-sm">
                {{ formatDate(task.due_date) }}
              </TableCell>
              <TableCell class="text-muted-foreground">
                {{ task.assignee?.name ?? 'Unassigned' }}
              </TableCell>
              <TableCell>
                <div v-if="task.taskable" class="flex items-center gap-2">
                  <Badge :variant="getTaskableTypeVariant(task.taskable.type)" class="text-xs">
                    {{ task.taskable.type }}
                  </Badge>
                  <span class="text-muted-foreground text-sm">{{ task.taskable.name }}</span>
                </div>
                <span v-else class="text-muted-foreground">—</span>
              </TableCell>
              <TableCell class="text-muted-foreground text-sm">
                {{ formatDate(task.updated_at) }}
              </TableCell>
              <TableCell>
                <ActionButtons
                  :show-edit="true"
                  :show-delete="true"
                  :on-edit="() => goToEdit(task.id)"
                  :on-delete="() => confirmDelete(task.id)"
                />
              </TableCell>
            </TableRow>
            <TableRow v-if="tasks.data.length === 0">
              <TableCell
                colspan="7"
                class="py-8 text-center text-muted-foreground"
              >
                No tasks found matching your filters.
              </TableCell>
            </TableRow>
          </TableBody>

          <!-- Table Footer with Pagination -->
          <TableFooter>
            <TableRow>
              <TableCell colspan="7">
                <div
                  v-if="tasks.meta?.last_page > 1"
                  class="flex flex-col items-center justify-between gap-4 py-4 sm:flex-row"
                >
                  <!-- Info -->
                  <div class="text-sm text-muted-foreground">
                    Showing
                    <span class="font-medium">{{ showingFrom }}</span>
                    to
                    <span class="font-medium">{{ showingTo }}</span>
                    of
                    <span class="font-medium">{{ total }}</span>
                    results
                  </div>

                  <!-- Pagination Controls -->
                  <nav
                    class="flex items-center overflow-hidden rounded-md border"
                  >
                    <!-- Prev -->
                    <button
                      class="px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                      :disabled="!tasks.links[0].url"
                      @click="goToPage(tasks.links[0].url)"
                    >
                      <ChevronLeft class="h-4 w-4" />
                    </button>

                    <!-- Page Numbers -->
                    <button
                      v-for="link in pageLinks"
                      :key="link.label"
                      class="border-l px-3 py-2 text-sm font-medium transition-colors"
                      @click="goToPage(link.url)"
                      :class="[
                        link.active
                          ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                          : 'text-muted-foreground hover:bg-muted',
                      ]"
                    >
                      {{ link.label }}
                    </button>

                    <!-- Next -->
                    <button
                      class="border-l px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                      :disabled="!tasks.links[tasks.links.length - 1].url"
                      @click="goToPage(tasks.links[tasks.links.length - 1].url)"
                    >
                      <ChevronRight class="h-4 w-4" />
                    </button>
                  </nav>
                </div>
              </TableCell>
            </TableRow>
          </TableFooter>
        </Table>
      </div>

      <!-- Delete Confirmation -->
      <ConfirmationDialog
        :show="showDeleteDialog"
        title="Delete Task"
        :description="`Are you sure you want to delete '${taskBeingDeleted?.title}'? This action cannot be undone.`"
        confirm-text="Delete Task"
        cancel-text="Cancel"
        variant="destructive"
        @confirm="deleteTask"
        @cancel="cancelDelete"
      />
    </div>
  </AppLayout>
</template>
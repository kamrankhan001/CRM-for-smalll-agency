<script setup lang="ts">
import { Pencil, Trash2, Eye } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

defineProps<{
  onEdit?: () => void;
  onDelete?: () => void;
  onView?: () => void;
  showView?: boolean;
  showEdit?: boolean;
  showDelete?: boolean;
  viewTooltip?: string;
  editTooltip?: string;
  deleteTooltip?: string;
}>();
</script>

<template>
  <TooltipProvider>
    <div class="flex justify-end space-x-2">
      <!-- View Button -->
      <Tooltip v-if="showView">
        <TooltipTrigger as-child>
          <Button
            variant="ghost"
            size="sm"
            @click="onView"
            class="h-8 w-8 p-0"
          >
            <Eye class="h-4 w-4" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          <p>{{ viewTooltip || 'View details' }}</p>
        </TooltipContent>
      </Tooltip>

      <!-- Edit Button -->
      <Tooltip v-if="showEdit">
        <TooltipTrigger as-child>
          <Button
            variant="ghost"
            size="sm"
            @click="onEdit"
            class="h-8 w-8 p-0"
          >
            <Pencil class="h-4 w-4" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          <p>{{ editTooltip || 'Edit' }}</p>
        </TooltipContent>
      </Tooltip>

      <!-- Delete Button -->
      <Tooltip v-if="showDelete">
        <TooltipTrigger as-child>
          <Button
            variant="ghost"
            size="sm"
            @click="onDelete"
            class="h-8 w-8 p-0 text-destructive hover:text-destructive"
          >
            <Trash2 class="h-4 w-4" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          <p>{{ deleteTooltip || 'Delete' }}</p>
        </TooltipContent>
      </Tooltip>
    </div>
  </TooltipProvider>
</template>
<script setup lang="ts">
import { Pencil, Trash2, Eye } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
  onEdit?: () => void;
  onDelete?: () => void;
  onView?: () => void;
  showView?: boolean;
  showEdit?: boolean;
  showDelete?: boolean;
}>();
</script>

<template>
  <div class="flex justify-end space-x-2">
    <Button
      v-if="showView"
      variant="ghost"
      size="sm"
      @click="onView"
      class="h-8 w-8 p-0"
    >
      <Eye class="h-4 w-4" />
    </Button>
    <Button
      v-if="showEdit"
      variant="ghost"
      size="sm"
      @click="onEdit"
      class="h-8 w-8 p-0"
    >
      <Pencil class="h-4 w-4" />
    </Button>
    <Button
      v-if="showDelete"
      variant="ghost"
      size="sm"
      @click="onDelete"
      class="h-8 w-8 p-0 text-destructive hover:text-destructive"
    >
      <Trash2 class="h-4 w-4" />
    </Button>
  </div>
</template>
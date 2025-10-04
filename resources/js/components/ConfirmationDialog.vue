<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

defineProps<{
  show: boolean;
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'default' | 'destructive';
}>();

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

function confirm() {
  emit('confirm');
}

function cancel() {
  emit('cancel');
}
</script>

<template>
  <Dialog :open="show">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          {{ description }}
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="flex space-x-2 justify-end">
        <Button variant="outline" @click="cancel">
          {{ cancelText || 'Cancel' }}
        </Button>
        <Button 
          :variant="variant === 'destructive' ? 'destructive' : 'default'" 
          @click="confirm"
        >
          {{ confirmText || 'Confirm' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
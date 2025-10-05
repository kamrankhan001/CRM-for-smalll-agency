<!-- components/LeadImportDialog.vue -->
<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, CheckCircle2, FileText, Download } from 'lucide-vue-next';

interface Props {
  show: boolean;
  isImporting?: boolean;
}

interface Emits {
  (e: 'update:show', value: boolean): void;
  (e: 'confirm', file: File): void;
  (e: 'download-sample'): void;
}

const props = withDefaults(defineProps<Props>(), {
  isImporting: false,
});

const emit = defineEmits<Emits>();

const importFile = ref<File | null>(null);
const error = ref<string>('');

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  error.value = '';
  
  if (target.files && target.files[0]) {
    const file = target.files[0];
    
    // Validate file type
    const allowedTypes = [
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'text/csv'
    ];
    
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
      error.value = 'Please select a valid Excel or CSV file';
      return;
    }
    
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
      error.value = 'File size must be less than 10MB';
      return;
    }
    
    importFile.value = file;
  }
};

const submitImport = () => {
  if (importFile.value) {
    emit('confirm', importFile.value);
  }
};

const closeDialog = () => {
  emit('update:show', false);
  // Reset form after dialog closes
  setTimeout(() => {
    importFile.value = null;
    error.value = '';
    const fileInput = document.getElementById('import-file') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
  }, 300);
};

const downloadSample = () => {
  emit('download-sample');
};

// Watch for show changes to reset form when opened
watch(() => props.show, (newVal) => {
  if (!newVal) {
    importFile.value = null;
    error.value = '';
  }
});
</script>

<template>
  <Dialog :open="show" @update:open="closeDialog">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Import Leads</DialogTitle>
        <DialogDescription>
          Upload an Excel file to import leads. Download the sample template to see the required format.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- File Upload -->
        <div class="space-y-2">
          <Label for="import-file">Select File</Label>
          <Input
            id="import-file"
            type="file"
            accept=".xlsx,.xls,.csv"
            @change="handleFileSelect"
            :disabled="isImporting"
          />
          <p class="text-xs text-muted-foreground">
            Supported formats: .xlsx, .xls, .csv (Max: 10MB)
          </p>
        </div>

        <!-- Selected File Info -->
        <div v-if="importFile" class="rounded-lg border bg-muted/50 p-3">
          <div class="flex items-center gap-2">
            <FileText class="h-4 w-4 text-green-600" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ importFile.name }}</p>
              <p class="text-xs text-muted-foreground">
                Size: {{ (importFile.size / 1024 / 1024).toFixed(2) }} MB
              </p>
            </div>
            <CheckCircle2 class="h-4 w-4 text-green-600" />
          </div>
        </div>

        <!-- Error Message -->
        <Alert v-if="error" variant="destructive">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Instructions -->
        <div class="rounded-lg bg-blue-50 p-3 border border-blue-200">
          <h4 class="text-sm font-medium text-blue-800 mb-2">Import Instructions:</h4>
          <ul class="text-xs text-blue-700 space-y-1">
            <li>• <strong>Name</strong> field is required</li>
            <li>• <strong>Status</strong> must be: new, contacted, qualified, or lost</li>
            <li>• <strong>Assigned To</strong> should be the exact username (leave empty for unassigned)</li>
            <li>• Existing leads with matching email/phone will be updated</li>
          </ul>
        </div>

        <!-- Download Sample -->
        <div class="flex justify-center">
          <Button
            variant="outline"
            @click="downloadSample"
            class="flex items-center gap-2"
            size="sm"
          >
            <Download class="h-4 w-4" />
            Download Sample Template
          </Button>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button
          variant="outline"
          @click="closeDialog"
          :disabled="isImporting"
        >
          Cancel
        </Button>
        <Button
          @click="submitImport"
          :disabled="!importFile || isImporting"
          :loading="isImporting"
        >
          <template v-if="isImporting">Importing...</template>
          <template v-else>Import Leads</template>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Upload, FileText, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next'

// Extend Window interface to include Echo
declare global {
    interface Window {
        Echo: any
    }
}

interface History {
    id: number
    file_name: string
    status: 'pending' | 'processing' | 'completed' | 'failed'
    created_at: string
    updated_at: string
    formatted_date: string
    time_ago: string
}

// Props from FileUploadController
interface Props {
    histories: {
        data: History[]
    }
}

const props = defineProps<Props>()

const fileInput = ref<HTMLInputElement | null>(null)
const isDragOver = ref(false)
const isUploading = ref(false)

// Use histories directly from props
const uploadedFiles = ref<History[]>(props.histories.data)

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    uploadFile(target.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
    uploadFile(event.dataTransfer.files[0])
  }
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
}

const uploadFile = async (file: File) => {
    if (!file) return

    isUploading.value = true
    console.log('📤 Uploading file:', file.name)
    
    const formData = new FormData()
    formData.append('file', file)

    try {
        const response = await fetch('/upload-file', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        })

        if (response.ok) {
            const data = await response.json()
            // append the new file to the top of the list
            uploadedFiles.value = data.histories
        }

    } catch (error) {
        console.error('Upload failed:', error)
        alert('Upload failed: Network error')
    } finally {
        isUploading.value = false
    }
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending':
            return Clock
        case 'processing':
            return AlertCircle
        case 'completed':
            return CheckCircle
        case 'failed':
            return XCircle
        default:
            return Clock
    }
}

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'pending':
            return 'secondary'
        case 'processing':
            return 'default'
        case 'completed':
            return 'default' // We'll add custom green styling
        case 'failed':
            return 'destructive'
        default:
            return 'secondary'
    }
}

const getStatusClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 border-green-200 hover:bg-green-200'
        case 'processing':
            return 'bg-blue-100 text-blue-800 border-blue-200 hover:bg-blue-200'
        default:
            return ''
    }
}

// Listen for real-time updates via Laravel Echo
onMounted(() => {
    // Check if Echo is available
    // Listen for file processing updates
    window.Echo.channel('file-processing')
        .listen('.file.processing', (e: any) => {
            console.log('⚙️ File processing:', e)
            // Find file by filename since processing event might not have fileId
            const fileIndex = uploadedFiles.value.findIndex((f: History) => f.file_name === e.fileName)
            if (fileIndex !== -1) {
                uploadedFiles.value[fileIndex].status = 'processing'
                uploadedFiles.value[fileIndex].updated_at = e.timestamp
            } else {
                // If file not in current list, it might be a new upload
                console.log('Processing file not found in current list:', e.fileName)
            }
        })

    // Listen for file completion and failures
    window.Echo.channel('file-uploaded')
        .listen('.file.completed', (e: any) => {
            console.log('📁 File completed:', e)
            // Try to find by ID first, then by filename as fallback
            let fileIndex = uploadedFiles.value.findIndex((f: History) => f.id === e.fileId)
            if (fileIndex === -1) {
                fileIndex = uploadedFiles.value.findIndex((f: History) => f.file_name === e.fileName)
            }
            
            if (fileIndex !== -1) {
                uploadedFiles.value[fileIndex].status = 'completed'
                uploadedFiles.value[fileIndex].updated_at = e.timestamp
            } else {
                // If file not found, refresh the page to get updated data
                console.log('Completed file not found, refreshing...')
                location.reload()
            }
        })
        .listen('.file.failed', (e: any) => {
            console.log('❌ File failed:', e)
            // Try to find by ID first, then by filename as fallback
            let fileIndex = uploadedFiles.value.findIndex((f: History) => f.id === e.fileId)
            if (fileIndex === -1) {
                fileIndex = uploadedFiles.value.findIndex((f: History) => f.file_name === e.fileName)
            }
            
            if (fileIndex !== -1) {
                uploadedFiles.value[fileIndex].status = 'failed'
                uploadedFiles.value[fileIndex].updated_at = e.timestamp
            }
            // Show error notification
            console.error('File processing failed:', e.error)
        })
    
    console.log('✅ Echo listeners configured')
})
</script>

<template>
  <Head title="Upload File" />

  <div class="min-h-screen bg-background p-4 md:p-8">
    <div class="mx-auto max-w-6xl space-y-6">
      <!-- Upload Section -->
      <Card>
        <CardContent class="p-6">
          <div
            class="relative border-2 border-dashed border-border rounded-lg p-8 text-center transition-colors"
            :class="{ 'border-primary bg-primary/5': isDragOver }"
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".csv,.xlsx,.xls"
              class="hidden"
              @change="handleFileSelect"
            >
            
            <div class="flex flex-col items-center space-y-4">
              <div class="p-4 rounded-full bg-muted">
                <Upload class="w-8 h-8 text-muted-foreground" />
              </div>
              <div class="space-y-2">
                <p class="text-lg font-medium">
                  {{ isDragOver ? 'Drop your file here' : 'Select file/Drag and drop' }}
                </p>
                <p class="text-sm text-muted-foreground">
                  Supports CSV, Excel files (max 10MB)
                </p>
              </div>
              <Button 
                @click="triggerFileInput"
                :disabled="isUploading"
                class="mt-4"
              >
                <Upload class="w-4 h-4 mr-2" />
                {{ isUploading ? 'Uploading...' : 'Upload File' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Files Table -->
      <Card>
        <CardHeader>
          <CardTitle>Upload History</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[200px]">
                  <div class="flex items-center space-x-2">
                    <Clock class="w-4 h-4" />
                    <span>Time</span>
                  </div>
                </TableHead>
                <TableHead>
                  <div class="flex items-center space-x-2">
                    <FileText class="w-4 h-4" />
                    <span>File Name</span>
                  </div>
                </TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="file in uploadedFiles" :key="file.id">
                    <TableCell>
                    <div class="space-y-1">
                        <div class="font-medium">{{ file.formatted_date }}</div>
                        <div class="text-sm text-muted-foreground">({{ file.time_ago }})</div>
                    </div>
                    </TableCell>
                    <TableCell>
                    <div class="flex items-center space-x-2">
                        <FileText class="w-4 h-4 text-muted-foreground" />
                        <span>{{ file.file_name }}</span>
                    </div>
                    </TableCell>
                    <TableCell>
                    <Badge 
                        :variant="getStatusVariant(file.status)" 
                        :class="[
                            'inline-flex items-center space-x-1',
                            getStatusClass(file.status)
                        ]"
                    >
                        <component :is="getStatusIcon(file.status)" class="w-3 h-3" />
                        <span class="capitalize">{{ file.status }}</span>
                    </Badge>
                    </TableCell>
                </TableRow>
                <TableRow v-if="uploadedFiles.length === 0">
                    <TableCell colspan="3" class="text-center text-muted-foreground py-8">
                    No files uploaded yet
                    </TableCell>
                </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
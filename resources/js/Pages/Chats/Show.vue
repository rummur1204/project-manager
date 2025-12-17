<script setup>
import { ref, nextTick, watch, onMounted, computed, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'

const props = defineProps({
  chats: Array,
  chat: Object,
  auth: Object,
  selectedChat: Number
})

const messagesContainer = ref(null)
const newMessage = ref('')
const showUserList = ref(false)
const users = ref([])
const showAttachmentMenu = ref(false)
const fileInput = ref(null)
const attachments = ref([])
const isUploading = ref(false)
const downloadedFiles = ref(new Set()) // Track downloaded files
const isScrolling = ref(false) // Track if user is manually scrolling
const autoScroll = ref(true) // Whether to auto-scroll to bottom
const showScrollToBottom = ref(false) // Show scroll to bottom button

// ====================
// SIMPLIFIED: Removed all event dispatchers
// ====================

// ====================
// Sort chats by most recent message
// ====================
const sortedChats = computed(() => {
  return [...props.chats].sort((a, b) => {
    const aTime = a.latest_message?.created_at ? new Date(a.latest_message.created_at) : new Date(a.created_at)
    const bTime = b.latest_message?.created_at ? new Date(b.latest_message.created_at) : new Date(b.created_at)
    return bTime - aTime // Descending (most recent first)
  })
})

// ====================
// Get chat display name
// ====================
const getChatName = (chat) => {
  if (chat.name) return chat.name
  if (chat.project?.title) return chat.project.title
  if (chat.type === 'private') {
    const otherUser = chat.users?.find(user => user.id !== auth.user.id)
    return otherUser?.name || `Private Chat`
  }
  return `Chat #${chat.id}`
}

// ====================
// Get chat description
// ====================
const getChatDescription = (chat) => {
  if (chat.project?.title) return chat.project.title
  if (chat.type === 'private') {
    const otherUser = chat.users?.find(user => user.id !== auth.user.id)
    return otherUser ? `Private chat with ${otherUser.name}` : 'Private Chat'
  }
  return 'Group Chat'
}

// ====================
// Get latest message preview
// ====================
const getLatestMessage = (chat) => {
  if (chat.latest_message) {
    if (chat.latest_message.file_path) {
      try {
        const files = Array.isArray(chat.latest_message.file_path) 
          ? chat.latest_message.file_path 
          : JSON.parse(chat.latest_message.file_path || '[]')
        
        if (files.length > 0) {
          const firstFile = files[0]
          const fileName = typeof firstFile === 'string' 
            ? firstFile.split('/').pop() 
            : (firstFile.original_name || firstFile.path?.split('/').pop() || 'File')
          return `📎 ${fileName}`
        }
      } catch (error) {
        console.error('Error parsing latest message files:', error)
      }
      return '📎 File'
    }
    const msg = chat.latest_message.message
    return msg && msg.length > 30 ? msg.substring(0, 30) + '...' : msg || '📎 File'
  }
  return 'No messages yet'
}

// ====================
// Get latest message time (for sidebar)
// ====================
const getLatestTime = (chat) => {
  if (chat.latest_message?.created_at) {
    const date = new Date(chat.latest_message.created_at)
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
    
    // Check if message was sent today
    if (messageDate.getTime() === today.getTime()) {
      // Sent today - show time only
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } 
    // Check if message was sent yesterday
    else if (messageDate.getTime() === today.getTime() - 24 * 60 * 60 * 1000) {
      return 'Yesterday'
    } 
    else {
      // Older than yesterday - show date
      return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
    }
  }
  return ''
}

// ====================
// Format time only (for messages)
// ====================
const formatTimeOnly = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

// ====================
// Format date for separator
// ====================
const formatDateForSeparator = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  
  if (messageDate.getTime() === today.getTime()) {
    return 'Today'
  } else if (messageDate.getTime() === today.getTime() - 24 * 60 * 60 * 1000) {
    return 'Yesterday'
  } else {
    const options = { weekday: 'long', month: 'long', day: 'numeric' }
    if (messageDate.getFullYear() !== today.getFullYear()) {
      options.year = 'numeric'
    }
    return date.toLocaleDateString([], options)
  }
}

// ====================
// Check if we need a date separator between messages
// ====================
const shouldShowDateSeparator = (messages, index) => {
  if (!messages || messages.length === 0 || index === 0) return true
  
  const currentMessage = new Date(messages[index].created_at)
  const previousMessage = new Date(messages[index - 1].created_at)
  
  // Compare dates (ignoring time)
  const currentDate = new Date(currentMessage.getFullYear(), currentMessage.getMonth(), currentMessage.getDate())
  const previousDate = new Date(previousMessage.getFullYear(), previousMessage.getMonth(), previousMessage.getDate())
  
  return currentDate.getTime() !== previousDate.getTime()
}

// ====================
// Get files from message
// ====================
const getMessageFiles = (msg) => {
  if (!msg.file_path) return []
  
  try {
    const files = Array.isArray(msg.file_path) ? msg.file_path : JSON.parse(msg.file_path || '[]')
    
    // Handle both old and new formats
    return files.map(file => {
      if (typeof file === 'string') {
        return {
          path: file,
          original_name: file.split('/').pop()
        }
      }
      return file
    })
  } catch (error) {
    console.error('Error parsing files:', error)
    return []
  }
}

// ====================
// Get file name
// ====================
const getFileName = (fileInfo) => {
  if (!fileInfo) return 'File'
  if (typeof fileInfo === 'string') {
    return fileInfo.split('/').pop()
  }
  return fileInfo.original_name || fileInfo.path?.split('/').pop() || 'File'
}

// ====================
// Get file icon based on type
// ====================
const getFileIcon = (filePath) => {
  const path = typeof filePath === 'string' ? filePath : filePath.path
  const extension = path.split('.').pop().toLowerCase()
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']
  const docExtensions = ['doc', 'docx']
  const pdfExtensions = ['pdf']
  const excelExtensions = ['xls', 'xlsx', 'csv']
  const pptExtensions = ['ppt', 'pptx']
  const zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz']
  
  if (imageExtensions.includes(extension)) {
    return '🖼️'
  } else if (docExtensions.includes(extension)) {
    return '📄'
  } else if (pdfExtensions.includes(extension)) {
    return '📕'
  } else if (excelExtensions.includes(extension)) {
    return '📊'
  } else if (pptExtensions.includes(extension)) {
    return '📽️'
  } else if (zipExtensions.includes(extension)) {
    return '📦'
  } else {
    return '📎'
  }
}

// ====================
// Check if file is an image
// ====================
const isImageFile = (filePath) => {
  const path = typeof filePath === 'string' ? filePath : filePath.path
  const extension = path.split('.').pop().toLowerCase()
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']
  return imageExtensions.includes(extension)
}

// ====================
// Create unique file ID for tracking downloads
// ====================
const getFileId = (msgId, filePath) => {
  return `${msgId}-${filePath}`
}

// ====================
// Check if file has been downloaded
// ====================
const isFileDownloaded = (msgId, filePath) => {
  const fileId = getFileId(msgId, filePath)
  return downloadedFiles.value.has(fileId)
}

// ====================
// Mark file as downloaded
// ====================
const markFileAsDownloaded = (msgId, filePath) => {
  const fileId = getFileId(msgId, filePath)
  downloadedFiles.value.add(fileId)
  
  // Store in localStorage for persistence across page reloads
  try {
    const stored = JSON.parse(localStorage.getItem('downloadedFiles') || '[]')
    if (!stored.includes(fileId)) {
      stored.push(fileId)
      localStorage.setItem('downloadedFiles', JSON.stringify(stored))
    }
  } catch (e) {
    console.error('Error saving to localStorage:', e)
  }
}

// ====================
// Handle file selection
// ====================
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  
  files.forEach(file => {
    // Validate file size (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
      alert(`File ${file.name} is too large. Maximum size is 10MB.`)
      return
    }
    
    // Validate file type
    const allowedTypes = [
      'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
      'application/pdf',
      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
    ]
    
    if (!allowedTypes.includes(file.type) && !file.type.startsWith('image/')) {
      alert(`File type ${file.type || 'unknown'} is not allowed.`)
      return
    }
    
    attachments.value.push({
      file,
      preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
      original_name: file.name
    })
  })
  
  showAttachmentMenu.value = false
  event.target.value = null // Reset input
}

// ====================
// Remove attachment
// ====================
const removeAttachment = (index) => {
  if (attachments.value[index].preview) {
    URL.revokeObjectURL(attachments.value[index].preview)
  }
  attachments.value.splice(index, 1)
}

// ====================
// Trigger file input
// ====================
const triggerFileInput = () => {
  fileInput.value.click()
}

// ====================
// Download file with tracking
// ====================
const downloadFile = (fileUrl, fileName, msgId, filePath) => {
  const link = document.createElement('a')
  link.href = fileUrl
  link.download = fileName
  document.body.appendChild(link)
  
  // Track download completion
  link.addEventListener('click', () => {
    markFileAsDownloaded(msgId, filePath)
  })
  
  // Also track when download actually happens
  link.addEventListener('load', () => {
    markFileAsDownloaded(msgId, filePath)
  })
  
  link.click()
  
  // Clean up
  setTimeout(() => {
    document.body.removeChild(link)
    markFileAsDownloaded(msgId, filePath)
  }, 100)
}

// ====================
// Open file with proper behavior
// ====================
const openFile = (fileUrl, isSender, fileName, msgId, filePath) => {
  if (isSender) {
    // For sender, always open in new tab
    window.open(fileUrl, '_blank')
  } else {
    // For recipient, check if already downloaded
    if (isFileDownloaded(msgId, filePath)) {
      // Already downloaded, open in new tab
      window.open(fileUrl, '_blank')
    } else {
      // First time, download it
      downloadFile(fileUrl, fileName, msgId, filePath)
    }
  }
}

// ====================
// Scroll to bottom function
// ====================
const scrollToBottom = () => {
  nextTick(() => {
    const el = messagesContainer.value
    if (el) {
      // Enable auto-scroll
      autoScroll.value = true
      showScrollToBottom.value = false
      
      // Smooth scroll to bottom
      el.scrollTo({
        top: el.scrollHeight,
        behavior: 'smooth'
      })
      
      // Set scroll position directly as fallback
      el.scrollTop = el.scrollHeight
    }
  })
}

// ====================
// Check if user is near the bottom
// ====================
const isNearBottom = () => {
  const el = messagesContainer.value
  if (!el) return true
  
  const threshold = 100 // pixels from bottom
  const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
  
  return distanceFromBottom <= threshold
}

// ====================
// Handle scroll events
// ====================
const handleScroll = () => {
  if (!messagesContainer.value) return
  
  const el = messagesContainer.value
  isScrolling.value = true
  
  // If user scrolls near the bottom, enable auto-scroll and hide button
  if (isNearBottom()) {
    autoScroll.value = true
    showScrollToBottom.value = false
  } else {
    autoScroll.value = false
    showScrollToBottom.value = true
  }
}

// ====================
// Force scroll to bottom (used when opening chat)
// ====================
const forceScrollToBottom = () => {
  nextTick(() => {
    const el = messagesContainer.value
    if (el) {
      // Immediate scroll without animation
      el.scrollTop = el.scrollHeight
      autoScroll.value = true
      showScrollToBottom.value = false
      isScrolling.value = false
    }
  })
}

// ====================
// SIMPLIFIED: Open chat - removed event dispatchers
// ====================
const openChat = (chatId) => {
  router.visit(`/chats/${chatId}`)
}

// ====================
// SIMPLIFIED: Send message - removed event dispatchers
// ====================
const sendMessage = async () => {
  if ((!newMessage.value.trim() && attachments.value.length === 0) || !props.chat) return
  
  isUploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('message', newMessage.value || '') // Always send empty string if no message
    
    // Add attachments
    attachments.value.forEach((attachment, index) => {
      formData.append(`attachments[${index}]`, attachment.file)
    })
    
    await router.post(`/chats/${props.chat.id}/messages`, formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // Reset form
        newMessage.value = ''
        attachments.value = []
        showAttachmentMenu.value = false
        
        // Scroll to bottom after sending
        forceScrollToBottom()
      },
      onError: (errors) => {
        if (errors.error) {
          alert(errors.error)
        } else {
          alert('Failed to send message. Please try again.')
        }
      }
    })
    
  } catch (error) {
    console.error('Error sending message:', error)
    alert('Failed to send message. Please try again.')
  } finally {
    isUploading.value = false
  }
}

// ====================
// Load downloaded files from localStorage on mount
// ====================
onMounted(() => {
  // Load previously downloaded files
  try {
    const stored = JSON.parse(localStorage.getItem('downloadedFiles') || '[]')
    stored.forEach(fileId => {
      downloadedFiles.value.add(fileId)
    })
  } catch (e) {
    console.error('Error loading from localStorage:', e)
  }
  
  // Force scroll to bottom when component mounts
  setTimeout(forceScrollToBottom, 100)
  
  // Add scroll event listener
  const el = messagesContainer.value
  if (el) {
    el.addEventListener('scroll', handleScroll)
  }
})

// Clean up on unmount
onUnmounted(() => {
  const el = messagesContainer.value
  if (el) {
    el.removeEventListener('scroll', handleScroll)
  }
  
  attachments.value.forEach(attachment => {
    if (attachment.preview) {
      URL.revokeObjectURL(attachment.preview)
    }
  })
})

// Watch for chat changes and scroll to bottom
watch(() => props.chat?.id, () => {
  // Reset states when changing chats
  autoScroll.value = true
  showScrollToBottom.value = false
  isScrolling.value = false
  
  // Scroll to bottom after a short delay to ensure DOM is updated
  nextTick(() => {
    setTimeout(forceScrollToBottom, 150)
  })
})

// Scroll to bottom when messages are added
watch(() => props.chat?.messages?.length, () => {
  // Only auto-scroll if user is near the bottom
  if (autoScroll.value) {
    forceScrollToBottom()
  } else {
    // If not auto-scrolling, show the scroll to bottom button
    showScrollToBottom.value = true
  }
})
</script>

<template>
  <Layout>
    <!-- Main Content with margin to account for navbar -->
    <div class="p-6 mt-6">
      <!-- Error Alert -->
      <div v-if="$page.props.errors.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline">{{ $page.props.errors.errors?.error || $page.props.errors.error }}</span>
      </div>
      
      <div class="flex h-[80vh] bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden transition-colors">

        <!-- Sidebar -->
        <div class="w-1/3 border-r border-teal-200 dark:border-teal-800/30 flex flex-col">
          <!-- Sidebar Header with Teal -->
          <div class="bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-5 py-4">
            <div class="flex justify-between items-center">
              <h2 class="text-lg font-semibold">Chats</h2>
              <span 
                v-if="chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) > 0"
                class="bg-gradient-to-br from-emerald-500 to-teal-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center ring-2 ring-white dark:ring-gray-800"
              >
                {{ chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) > 9 ? '9+' : chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) }}
              </span>
            </div>
          </div>

          <!-- Chat List -->
          <div class="flex-1 overflow-y-auto bg-gradient-to-b from-teal-50/30 to-white dark:from-teal-900/5 dark:to-gray-800">
            <div
              v-for="chat in sortedChats"
              :key="chat.id"
              @click="openChat(chat.id)"
              class="p-4 border-b border-teal-100 dark:border-teal-800/30 hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/20 dark:hover:to-emerald-900/20 cursor-pointer transition-all duration-200 relative"
              :class="{'bg-gradient-to-r from-teal-100 to-emerald-100 dark:from-teal-900/30 dark:to-emerald-900/30': chat.id === selectedChat}"
            >
              <!-- Unread badge -->
              <span 
                v-if="chat.unread_count > 0"
                class="absolute top-2 right-2 bg-gradient-to-br from-emerald-500 to-teal-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center ring-2 ring-white dark:ring-gray-800"
              >
                {{ chat.unread_count > 9 ? '9+' : chat.unread_count }}
              </span>
              
              <!-- Chat info -->
              <div class="flex justify-between items-start pr-6">
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-800 dark:text-gray-100 truncate">
                    {{ getChatName(chat) }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1">
                    {{ getLatestMessage(chat) }}
                  </p>
                </div>
                <span class="text-xs text-teal-600 dark:text-teal-400 whitespace-nowrap ml-2">
                  {{ getLatestTime(chat) }}
                </span>
              </div>
            </div>

            <div v-if="!chats.length" class="p-6 text-center text-gray-400 dark:text-gray-500">
              No chats available.
            </div>
          </div>
        </div>

        <!-- Chat Window -->
        <div v-if="chat" class="flex-1 flex flex-col relative">
          <!-- Chat Header with Teal -->
          <div class="bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-5 py-4">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="text-lg font-semibold">{{ getChatName(chat) }}</h2>
                <p class="text-sm opacity-90">{{ getChatDescription(chat) }}</p>
              </div>
            </div>
          </div>

          <!-- Messages Container -->
          <div 
            ref="messagesContainer" 
            class="flex-1 overflow-y-auto p-4 space-y-3 bg-gradient-to-b from-teal-50/20 to-white dark:from-teal-900/5 dark:to-gray-800"
            @scroll="handleScroll"
          >
            <div v-if="!chat.messages.length" class="text-gray-400 dark:text-gray-500 text-center py-6">
              No messages yet. Start the conversation!
            </div>

            <div v-for="(msg, index) in chat.messages" :key="msg.id">
              <!-- Date separator -->
              <div 
                v-if="shouldShowDateSeparator(chat.messages, index)"
                class="flex justify-center my-4"
              >
                <span class="px-3 py-1 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs rounded-full">
                  {{ formatDateForSeparator(msg.created_at) }}
                </span>
              </div>
              
              <!-- Message -->
              <div :class="['flex', msg.user_id === auth.user.id ? 'justify-end' : 'justify-start']">
                <div
                  :class="[ 
                    'p-3 rounded-xl max-w-xs shadow-sm', 
                    msg.user_id === auth.user.id 
                      ? 'bg-gradient-to-br from-teal-600 to-emerald-600 text-white' 
                      : 'bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 border border-teal-100 dark:border-teal-800/30 text-gray-800 dark:text-gray-100'
                  ]"
                >
                  <p class="text-sm font-semibold">{{ msg.user?.name || 'User' }}</p>
                  
                  <!-- File attachments -->
                  <div v-if="msg.file_path" class="mt-2 mb-2 space-y-2">
                    <div v-for="(fileInfo, fileIndex) in getMessageFiles(msg)" :key="fileIndex">
                      <div class="flex items-center space-x-2 p-2 bg-black/10 dark:bg-white/10 rounded-lg">
                        <span class="text-xl">{{ getFileIcon(fileInfo) }}</span>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium truncate">{{ getFileName(fileInfo) }}</p>
                          <div class="flex gap-2 mt-1">
                            <!-- For sender (you): Always view -->
                            <a 
                              v-if="msg.user_id === auth.user.id"
                              :href="'/storage/' + fileInfo.path" 
                              target="_blank" 
                              class="text-xs text-teal-300 hover:text-teal-200 hover:underline"
                            >
                              View
                            </a>
                            <!-- For recipient: Download or View based on download status -->
                            <template v-else>
                              <button 
                                v-if="!isFileDownloaded(msg.id, fileInfo.path)"
                                @click="downloadFile('/storage/' + fileInfo.path, getFileName(fileInfo), msg.id, fileInfo.path)"
                                class="text-xs text-teal-500 dark:text-teal-400 hover:text-teal-600 dark:hover:text-teal-300 hover:underline focus:outline-none"
                                type="button"
                              >
                                Download
                              </button>
                              <a 
                                v-else
                                :href="'/storage/' + fileInfo.path" 
                                target="_blank" 
                                class="text-xs text-teal-500 dark:text-teal-400 hover:text-teal-600 dark:hover:text-teal-300 hover:underline"
                              >
                                View
                              </a>
                            </template>
                            <!-- Always show save option -->
                            <a 
                              :href="'/storage/' + fileInfo.path" 
                              :download="getFileName(fileInfo)"
                              class="text-xs text-gray-400 hover:text-gray-300 hover:underline"
                              @click="markFileAsDownloaded(msg.id, fileInfo.path)"
                            >
                              Save
                            </a>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Image preview -->
                      <div v-if="isImageFile(fileInfo)" class="mt-2">
                        <img 
                          :src="'/storage/' + fileInfo.path" 
                          :alt="getFileName(fileInfo)"
                          class="max-w-full max-h-64 rounded-lg cursor-pointer hover:opacity-90 transition-opacity border border-teal-100 dark:border-teal-800/30"
                          @click="openFile('/storage/' + fileInfo.path, msg.user_id === auth.user.id, getFileName(fileInfo), msg.id, fileInfo.path)"
                        >
                      </div>
                    </div>
                  </div>
                  
                  <!-- Message text -->
                  <p v-if="msg.message" class="text-sm mt-2">{{ msg.message }}</p>
                  
                  <p class="text-xs text-teal-100 mt-1" v-if="msg.user_id === auth.user.id">
                    {{ formatTimeOnly(msg.created_at) }}
                  </p>
                  <p class="text-xs text-teal-600 dark:text-teal-400 mt-1" v-else>
                    {{ formatTimeOnly(msg.created_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Scroll to bottom button -->
          <transition name="fade">
            <button
              v-if="showScrollToBottom && chat.messages.length > 0"
              @click="scrollToBottom"
              class="absolute left-4 bottom-24 bg-gradient-to-br from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white p-3 rounded-full shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-110 z-10"
              style="box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </transition>

          <!-- Attachments Preview -->
          <div v-if="attachments.length > 0" class="border-t border-teal-200 dark:border-teal-800/30 p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/10 dark:to-emerald-900/10">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-teal-700 dark:text-teal-300">
                Attachments ({{ attachments.length }})
              </span>
              <button 
                @click="attachments = []" 
                class="text-xs text-red-500 hover:text-red-600"
                type="button"
              >
                Clear all
              </button>
            </div>
            <div class="flex flex-wrap gap-2">
              <div 
                v-for="(attachment, index) in attachments" 
                :key="index"
                class="relative group"
              >
                <div class="w-20 h-20 bg-gradient-to-br from-teal-100 to-emerald-100 dark:from-teal-900/30 dark:to-emerald-900/30 rounded-lg overflow-hidden border border-teal-200 dark:border-teal-800/30">
                  <!-- Image preview -->
                  <img 
                    v-if="attachment.preview" 
                    :src="attachment.preview" 
                    :alt="attachment.original_name"
                    class="w-full h-full object-cover"
                  >
                  <!-- Document icon -->
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <span class="text-2xl">{{ getFileIcon(attachment.original_name) }}</span>
                  </div>
                  <!-- Remove button -->
                  <button 
                    @click="removeAttachment(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                    type="button"
                  >
                    ×
                  </button>
                  <!-- File name -->
                  <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent text-white text-xs p-1 truncate">
                    {{ attachment.original_name }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Message Input -->
          <form @submit.prevent="sendMessage" class="flex items-center gap-2 border-t border-teal-200 dark:border-teal-800/30 p-3 relative bg-gradient-to-r from-white to-teal-50/50 dark:from-gray-800 dark:to-teal-900/10">
            <!-- Hidden file input -->
            <input 
              ref="fileInput"
              type="file" 
              multiple 
              class="hidden" 
              @change="handleFileSelect"
              accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.rar,.7z,.tar,.gz"
            />
            
            <!-- Attachment button with dropdown -->
            <div class="relative">
              <button 
                @click="showAttachmentMenu = !showAttachmentMenu"
                type="button"
                class="p-2 text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 transition-colors hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-lg"
                :class="{ 'text-teal-700 dark:text-teal-300 bg-teal-100 dark:bg-teal-900/30': showAttachmentMenu }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
              </button>
              
              <!-- Attachment menu dropdown -->
              <div 
                v-if="showAttachmentMenu" 
                class="absolute bottom-full left-0 mb-2 w-48 bg-gradient-to-b from-white to-teal-50 dark:from-gray-800 dark:to-teal-900/20 rounded-lg shadow-lg border border-teal-200 dark:border-teal-800/30 z-10"
              >
                <button 
                  @click="triggerFileInput"
                  type="button"
                  class="w-full px-4 py-2 text-left text-sm text-teal-700 dark:text-teal-300 hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30 flex items-center gap-2"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Document
                </button>
                <button 
                  @click="triggerFileInput"
                  type="button"
                  class="w-full px-4 py-2 text-left text-sm text-teal-700 dark:text-teal-300 hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30 flex items-center gap-2"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Photo & Video
                </button>
              </div>
            </div>
            
            <!-- Text input -->
            <input
              v-model="newMessage"
              type="text"
              placeholder="Type your message..."
              class="flex-1 border border-teal-200 dark:border-teal-800/30 rounded-xl p-3 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200"
              @keydown.enter.exact.prevent="sendMessage"
            />
            
            <!-- Send button -->
            <button
              type="submit"
              :disabled="isUploading || (!newMessage.trim() && attachments.length === 0)"
              class="bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white px-5 py-3 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-lg hover:shadow-xl"
            >
              <span v-if="isUploading">Sending...</span>
              <span v-else>Send</span>
              <svg v-if="!isUploading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              <svg v-if="isUploading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </form>
        </div>

        <!-- Empty State -->
        <div v-else class="flex-1 flex items-center justify-center text-gray-400 dark:text-gray-500 bg-gradient-to-b from-teal-50/20 to-white dark:from-teal-900/5 dark:to-gray-800">
          <div class="text-center">
            <div class="text-lg font-medium mb-2">Select a chat</div>
            <p class="text-sm">Choose a conversation from the sidebar to start messaging</p>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(13, 148, 136, 0.05);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #0d9488, #059669);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #0d9488, #047857);
}

.dark ::-webkit-scrollbar-track {
  background: rgba(19, 78, 74, 0.2);
}

.dark ::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #115e59, #064e3b);
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #0f766e, #065f46);
}
</style>
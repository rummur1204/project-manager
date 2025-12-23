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
const showAttachmentMenu = ref(false)
const fileInput = ref(null)
const attachments = ref([])
const isUploading = ref(false)
const downloadedFiles = ref(new Set())
const isScrolling = ref(false)
const autoScroll = ref(true)
const showScrollToBottom = ref(false)

// Search functionality
const searchQuery = ref('')
const isSearching = ref(false)

// Responsive state
const isMobile = ref(false)
const isTablet = ref(false)
const showChatList = ref(true)
const showChatWindow = ref(false)

// ====================
// Check screen size and update UI state
// ====================
const checkScreenSize = () => {
  const width = window.innerWidth
  isMobile.value = width < 768
  isTablet.value = width >= 768 && width < 1024
  
  if (isMobile.value) {
    if (props.chat?.id) {
      showChatList.value = false
      showChatWindow.value = true
    } else {
      showChatList.value = true
      showChatWindow.value = false
    }
  } else {
    showChatList.value = true
    showChatWindow.value = !!props.chat?.id
  }
}

// ====================
// Back to chat list (mobile)
// ====================
const backToChatList = () => {
  router.visit('/chats', {
    preserveScroll: true,
    preserveState: true
  })
}

// ====================
// Clear search
// ====================
const clearSearch = () => {
  searchQuery.value = ''
  isSearching.value = false
}

// ====================
// Toggle search
// ====================
const toggleSearch = () => {
  isSearching.value = !isSearching.value
  if (!isSearching.value) {
    clearSearch()
  }
}

// ====================
// Sort chats by most recent message
// ====================
const sortedChats = computed(() => {
  return [...props.chats].sort((a, b) => {
    const aTime = a.latest_message?.created_at ? new Date(a.latest_message.created_at) : new Date(a.created_at)
    const bTime = b.latest_message?.created_at ? new Date(b.latest_message.created_at) : new Date(b.created_at)
    return bTime - aTime
  })
})

// ====================
// Filtered chats based on search
// ====================
const filteredChats = computed(() => {
  if (!searchQuery.value.trim()) {
    return sortedChats.value
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return sortedChats.value.filter(chat => {
    if (getChatName(chat).toLowerCase().includes(query)) {
      return true
    }
    
    if (chat.project?.title?.toLowerCase().includes(query)) {
      return true
    }
    
    if (chat.type === 'private') {
      const otherUser = chat.users?.find(user => user.id !== auth.user.id)
      if (otherUser?.name?.toLowerCase().includes(query)) {
        return true
      }
    }
    
    if (chat.latest_message?.message?.toLowerCase().includes(query)) {
      return true
    }
    
    if (chat.latest_message?.file_path) {
      try {
        const files = Array.isArray(chat.latest_message.file_path) 
          ? chat.latest_message.file_path 
          : JSON.parse(chat.latest_message.file_path || '[]')
        
        return files.some(file => {
          const fileName = typeof file === 'string' 
            ? file.split('/').pop().toLowerCase()
            : (file.original_name || file.path?.split('/').pop() || '').toLowerCase()
          return fileName.includes(query)
        })
      } catch (error) {
        console.error('Error parsing files for search:', error)
      }
    }
    
    return false
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
    
    if (messageDate.getTime() === today.getTime()) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (messageDate.getTime() === today.getTime() - 24 * 60 * 60 * 1000) {
      return 'Yesterday'
    } else {
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
    if (file.size > 10 * 1024 * 1024) {
      alert(`File ${file.name} is too large. Maximum size is 10MB.`)
      return
    }
    
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
  event.target.value = null
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
  
  link.addEventListener('click', () => {
    markFileAsDownloaded(msgId, filePath)
  })
  
  link.addEventListener('load', () => {
    markFileAsDownloaded(msgId, filePath)
  })
  
  link.click()
  
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
    window.open(fileUrl, '_blank')
  } else {
    if (isFileDownloaded(msgId, filePath)) {
      window.open(fileUrl, '_blank')
    } else {
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
      autoScroll.value = true
      showScrollToBottom.value = false
      
      el.scrollTo({
        top: el.scrollHeight,
        behavior: 'smooth'
      })
      
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
  
  const threshold = 100
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
      el.scrollTop = el.scrollHeight
      autoScroll.value = true
      showScrollToBottom.value = false
      isScrolling.value = false
    }
  })
}

// ====================
// Open chat
// ====================
const openChat = (chatId) => {
  router.visit(`/chats/${chatId}`)
}

// ====================
// Send message
// ====================
const sendMessage = async () => {
  if ((!newMessage.value.trim() && attachments.value.length === 0) || !props.chat) return
  
  isUploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('message', newMessage.value || '')
    
    attachments.value.forEach((attachment, index) => {
      formData.append(`attachments[${index}]`, attachment.file)
    })
    
    await router.post(`/chats/${props.chat.id}/messages`, formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        newMessage.value = ''
        attachments.value = []
        showAttachmentMenu.value = false
        
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
  
  // Check screen size and set initial UI state
  checkScreenSize()
  window.addEventListener('resize', checkScreenSize)
  
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
  window.removeEventListener('resize', checkScreenSize)
  
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
  checkScreenSize()
  
  autoScroll.value = true
  showScrollToBottom.value = false
  isScrolling.value = false
  
  nextTick(() => {
    setTimeout(forceScrollToBottom, 150)
  })
})

// Scroll to bottom when messages are added
watch(() => props.chat?.messages?.length, () => {
  if (autoScroll.value) {
    forceScrollToBottom()
  } else {
    showScrollToBottom.value = true
  }
})
</script>

<template>
  <Layout>
    <!-- Fixed container that doesn't scroll -->
    <div class="fixed-container">
      <!-- Error Alert -->
      <div v-if="$page.props.errors.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 mx-4 md:mx-6" role="alert">
        <span class="block sm:inline">{{ $page.props.errors.errors?.error || $page.props.errors.error }}</span>
      </div>
      
      <!-- Main chat container - Fixed height with scrollable content inside -->
      <div class="chat-container">
        <!-- Sidebar - Always visible on desktop, conditional on mobile/tablet -->
        <div 
          v-if="showChatList"
          class="sidebar"
          :class="{'hidden md:flex': isMobile && props.chat?.id}"
        >
          <!-- Sidebar Header with Teal -->
          <div class="sidebar-header">
            <div class="flex items-center">
              <!-- Mobile back button -->
              <button 
                v-if="isMobile && props.chat?.id"
                @click="backToChatList"
                class="mobile-back-button"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
              </button>
              <div class="flex-1 flex justify-between items-center">
                <h2 class="sidebar-title">Chats</h2>
                <div class="flex items-center gap-3">
                  <span 
                    v-if="chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) > 0"
                    class="unread-badge"
                  >
                    {{ chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) > 9 ? '9+' : chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) }}
                  </span>
                  
                  <!-- Search Toggle Button -->
                  <button
                    @click="toggleSearch"
                    class="search-toggle " 
                    :class="{ 'active': isSearching }"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Search Bar -->
            <transition name="slide-down">
              <div v-if="isSearching" class="search-container">
                <div class="relative">
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search chats..."
                    class="search-input"
                    @keydown.esc="clearSearch"
                    autofocus
                  />
                  <div class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                  <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="clear-search"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <!-- Search Results Info -->
                <div v-if="searchQuery" class="search-results">
                  <span v-if="filteredChats.length === 0">No results found</span>
                  <span v-else>{{ filteredChats.length }} {{ filteredChats.length === 1 ? 'chat' : 'chats' }} found</span>
                </div>
              </div>
            </transition>
          </div>

          <!-- Chat List - Scrollable content -->
          <div class="chat-list-container">
            <!-- Search Results Header -->
            <div 
              v-if="searchQuery && filteredChats.length > 0"
              class="search-results-header"
            >
              <div class="flex items-center justify-between">
                <span class="search-results-text">
                  Search results for "{{ searchQuery }}"
                </span>
                <button
                  @click="clearSearch"
                  class="clear-search-button"
                >
                  Clear
                </button>
              </div>
            </div>
            
            <!-- No Search Results -->
            <div 
              v-if="searchQuery && filteredChats.length === 0"
              class="no-search-results"
            >
              <div class="no-results-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="no-results-text">No chats found for "{{ searchQuery }}"</p>
              <button
                @click="clearSearch"
                class="clear-search-link"
              >
                Clear search
              </button>
            </div>
            
            <!-- Chat Items -->
            <div
              v-for="chat in (searchQuery ? filteredChats : sortedChats)"
              :key="chat.id"
              @click="openChat(chat.id)"
              class="chat-item"
              :class="{
                'active': chat.id === selectedChat,
                'mobile-active': isMobile && chat.id === selectedChat
              }"
            >
              <!-- Unread badge -->
              <span 
                v-if="chat.unread_count > 0"
                class="chat-unread-badge"
              >
                {{ chat.unread_count > 9 ? '9+' : chat.unread_count }}
              </span>
              
              <!-- Chat info -->
              <div class="chat-info">
                <div class="flex-1">
                  <h3 class="chat-name">
                    {{ getChatName(chat) }}
                  </h3>
                  <p class="chat-preview">
                    {{ getLatestMessage(chat) }}
                  </p>
                </div>
                <span class="chat-time">
                  {{ getLatestTime(chat) }}
                </span>
              </div>
            </div>

            <!-- Empty State - No Chats -->
            <div v-if="!chats.length && !searchQuery" class="no-chats">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <p>No chats available.</p>
              <p class="text-sm mt-1">Start a new conversation from a project or user profile.</p>
            </div>
          </div>
        </div>

        <!-- Chat Window -->
        <div 
          v-if="showChatWindow"
          class="chat-window"
        >
          <!-- Chat Header with back button on mobile -->
          <div class="chat-header">
            <div class="flex items-center">
              <button 
                v-if="isMobile"
                @click="backToChatList"
                class="mobile-back-button"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
              </button>
              <div class="flex-1">
                <h2 class="chat-title">{{ getChatName(chat) }}</h2>
                <p class="chat-subtitle">{{ getChatDescription(chat) }}</p>
              </div>
            </div>
          </div>

          <!-- Messages Container - Scrollable content -->
          <div 
            ref="messagesContainer" 
            class="messages-container"
            @scroll="handleScroll"
          >
            <div v-if="!chat.messages.length" class="no-messages">
              No messages yet. Start the conversation!
            </div>

            <div v-for="(msg, index) in chat.messages" :key="msg.id">
              <!-- Date separator -->
              <div 
                v-if="shouldShowDateSeparator(chat.messages, index)"
                class="date-separator"
              >
                <span class="date-separator-text">
                  {{ formatDateForSeparator(msg.created_at) }}
                </span>
              </div>
              
              <!-- Message -->
              <div :class="['message-wrapper', msg.user_id === auth.user.id ? 'justify-end' : 'justify-start']">
                <div
                  :class="['message-bubble', msg.user_id === auth.user.id ? 'sender' : 'receiver']"
                  :style="{
                    maxWidth: isMobile ? '85%' : '60%'
                  }"
                >
                  <p class="message-sender">{{ msg.user?.name || 'User' }}</p>
                  
                  <!-- File attachments -->
                  <div v-if="msg.file_path" class="message-files">
                    <div v-for="(fileInfo, fileIndex) in getMessageFiles(msg)" :key="fileIndex">
                      <div class="file-container">
                        <span class="file-icon">{{ getFileIcon(fileInfo) }}</span>
                        <div class="file-info">
                          <p class="file-name">{{ getFileName(fileInfo) }}</p>
                          <div class="file-actions">
                            <a 
                              v-if="msg.user_id === auth.user.id"
                              :href="'/storage/' + fileInfo.path" 
                              target="_blank" 
                              class="file-action view-action"
                            >
                              View
                            </a>
                            <template v-else>
                              <button 
                                v-if="!isFileDownloaded(msg.id, fileInfo.path)"
                                @click="downloadFile('/storage/' + fileInfo.path, getFileName(fileInfo), msg.id, fileInfo.path)"
                                class="file-action download-action"
                                type="button"
                              >
                                Download
                              </button>
                              <a 
                                v-else
                                :href="'/storage/' + fileInfo.path" 
                                target="_blank" 
                                class="file-action view-action"
                              >
                                View
                              </a>
                            </template>
                            <a 
                              :href="'/storage/' + fileInfo.path" 
                              :download="getFileName(fileInfo)"
                              class="file-action save-action"
                              @click="markFileAsDownloaded(msg.id, fileInfo.path)"
                            >
                              Save
                            </a>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Image preview -->
                      <div v-if="isImageFile(fileInfo)" class="image-preview">
                        <img 
                          :src="'/storage/' + fileInfo.path" 
                          :alt="getFileName(fileInfo)"
                          class="image-file"
                          :class="isMobile ? 'max-h-48' : 'max-h-64'"
                          @click="openFile('/storage/' + fileInfo.path, msg.user_id === auth.user.id, getFileName(fileInfo), msg.id, fileInfo.path)"
                        >
                      </div>
                    </div>
                  </div>
                  
                  <!-- Message text -->
                  <p v-if="msg.message" class="message-text">{{ msg.message }}</p>
                  
                  <p class="message-time" :class="msg.user_id === auth.user.id ? 'sender-time' : 'receiver-time'">
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
              class="scroll-bottom-button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </transition>

          <!-- Attachments Preview -->
          <div v-if="attachments.length > 0" class="attachments-preview">
            <div class="attachments-header">
              <span class="attachments-title">
                Attachments ({{ attachments.length }})
              </span>
              <button 
                @click="attachments = []" 
                class="clear-attachments"
                type="button"
              >
                Clear all
              </button>
            </div>
            <div class="attachments-grid">
              <div 
                v-for="(attachment, index) in attachments" 
                :key="index"
                class="attachment-item"
              >
                <div class="attachment-preview">
                  <img 
                    v-if="attachment.preview" 
                    :src="attachment.preview" 
                    :alt="attachment.original_name"
                    class="attachment-image"
                  >
                  <div v-else class="attachment-icon">
                    <span class="icon-text">{{ getFileIcon(attachment.original_name) }}</span>
                  </div>
                  <button 
                    @click="removeAttachment(index)"
                    class="remove-attachment"
                    type="button"
                  >
                    ×
                  </button>
                  <div class="attachment-name">
                    {{ attachment.original_name }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Message Input -->
          <form @submit.prevent="sendMessage" class="message-input-form">
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
            <div class="attachment-button-wrapper">
              <button 
                @click="showAttachmentMenu = !showAttachmentMenu"
                type="button"
                class="attachment-button"
                :class="{ 'active': showAttachmentMenu }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
              </button>
              
              <!-- Attachment menu dropdown -->
              <div 
                v-if="showAttachmentMenu" 
                class="attachment-menu"
              >
                <button 
                  @click="triggerFileInput"
                  type="button"
                  class="attachment-menu-item"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Document
                </button>
                <button 
                  @click="triggerFileInput"
                  type="button"
                  class="attachment-menu-item"
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
              class="message-input"
              @keydown.enter.exact.prevent="sendMessage"
            />
            
            <!-- Send button -->
            <button
              type="submit"
              :disabled="isUploading || (!newMessage.trim() && attachments.length === 0)"
              class="send-button"
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

        <!-- Empty State for desktop when no chat is selected -->
        <div 
          v-if="!chat && !isMobile"
          class="empty-state"
        >
          <div class="empty-state-content">
            <div class="empty-state-title">Select a chat</div>
            <p class="empty-state-text">Choose a conversation from the sidebar to start messaging</p>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<style scoped>
/* Fixed container that holds everything */
.fixed-container {
  position: fixed;
  top: 64px; /* Height of navbar */
  left: 0;
  right: 0;
  bottom: 64px;
  display: flex;
  flex-direction: column;
  background: linear-gradient(to bottom, #f0f9ff, white);
}
@media (min-width: 1024px) {
  .fixed-container {
    left: 256px; /* Width of desktop sidebar */
  }
}
.dark .fixed-container {
  background: linear-gradient(to bottom, #0f172a, #1e293b);
}

/* Main chat container */
.chat-container {
  flex: 1;
  display: flex;
  margin: 0 1rem 1rem 1rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.dark .chat-container {
  background: #1e293b;
}

/* Sidebar */
.sidebar {
  width: 100%;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #e2e8f0;
}

.dark .sidebar {
  border-right-color: #334155;
}

@media (min-width: 768px) {
  .sidebar {
    width: 33.333333%;
  }
}

@media (min-width: 1024px) {
  .sidebar {
    width: 25%; /* Changed from 33.333333% to 25% for desktop */
  }
}

/* Sidebar Header */
.sidebar-header {
  padding: 1rem;
  background: linear-gradient(to right, #0d9488, #059669);
  color: white;
  flex-shrink: 0;
}

/* Mobile back button */
.mobile-back-button {
  margin-right: 0.75rem;
  padding: 0.25rem;
  color: white;
  transition: background-color 0.2s;
  border-radius: 9999px;
}

.mobile-back-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

/* Sidebar title */
.sidebar-title {
  font-size: 1.125rem;
  font-weight: 600;
}

/* Unread badge */
.unread-badge {
  background: linear-gradient(to bottom right, #10b981, #0d9488);
  color: white;
  font-size: 0.75rem;
  border-radius: 9999px;
  height: 1.5rem;
  width: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 2px white;
}

.dark .unread-badge {
  box-shadow: 0 0 0 2px #1e293b;
}

/* Search toggle */
.search-toggle {
  padding: 0.375rem;
  color: white;
  transition: all 0.2s;
  border-radius: 0.5rem;
}

.search-toggle:hover {
  color: #115e59;
  background-color: rgba(255, 255, 255, 0.1);
}

.search-toggle.active {
  color: #0d9488;
  background-color: rgba(255, 255, 255, 0.2);
}

/* Search container */
.search-container {
  margin-top: 1rem;
}

/* Search input */
.search-input {
  width: 100%;
  padding: 0.5rem 1rem 0.5rem 2.5rem;
  background-color: rgba(13, 148, 136, 0.3);
  border: 1px solid rgba(13, 148, 136, 0.5);
  border-radius: 0.5rem;
  color: white;
  outline: none;
}

.search-input::placeholder {
  color: #a7f3d0;
}

.search-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(110, 231, 183, 0.5);
  border-color: transparent;
}

/* Search icon */
.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 0.5rem;
  color: #a7f3d0;
}

/* Clear search */
.clear-search {
  position: absolute;
  right: 0.75rem;
  top: 0.5rem;
  color: #a7f3d0;
  transition: color 0.2s;
}

.clear-search:hover {
  color: white;
}

/* Search results */
.search-results {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #a7f3d0;
}

/* Chat list container - Scrollable */
.chat-list-container {
  flex: 1;
  overflow-y: auto;
  background: linear-gradient(to bottom, rgba(240, 253, 244, 0.3), white);
}

.dark .chat-list-container {
  background: linear-gradient(to bottom, rgba(6, 78, 59, 0.05), #1e293b);
}

/* Search results header */
.search-results-header {
  position: sticky;
  top: 0;
  z-index: 10;
  padding: 0.5rem 1rem;
  background: linear-gradient(to right, #f0fdf4, #dcfce7);
  border-bottom: 1px solid #bbf7d0;
}

.dark .search-results-header {
  background: linear-gradient(to right, rgba(6, 78, 59, 0.2), rgba(6, 95, 70, 0.2));
  border-bottom-color: #065f46;
}

.search-results-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #065f46;
}

.dark .search-results-text {
  color: #5eead4;
}

.clear-search-button {
  font-size: 0.75rem;
  color: #059669;
  transition: color 0.2s;
}

.clear-search-button:hover {
  color: #047857;
}

.dark .clear-search-button {
  color: #5eead4;
}

.dark .clear-search-button:hover {
  color: #99f6e4;
}

/* No search results */
.no-search-results {
  padding: 2rem;
  text-align: center;
}

.no-results-icon {
  color: #9ca3af;
  margin-bottom: 0.5rem;
}

.dark .no-results-icon {
  color: #6b7280;
}

.no-results-text {
  color: #6b7280;
}

.dark .no-results-text {
  color: #9ca3af;
}

.clear-search-link {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #0d9488;
  transition: color 0.2s;
}

.clear-search-link:hover {
  color: #0f766e;
}

.dark .clear-search-link {
  color: #5eead4;
}

/* Chat item */
.chat-item {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.dark .chat-item {
  border-bottom-color: #334155;
}

.chat-item:hover {
  background: linear-gradient(to right, #f0fdf4, #dcfce7);
}

.dark .chat-item:hover {
  background: linear-gradient(to right, rgba(6, 78, 59, 0.2), rgba(6, 95, 70, 0.2));
}

.chat-item.active {
  background: linear-gradient(to right, #d1fae5, #a7f3d0);
}

.dark .chat-item.active {
  background: linear-gradient(to right, rgba(6, 78, 59, 0.3), rgba(6, 95, 70, 0.3));
}

.chat-item.mobile-active:active {
  background: linear-gradient(to right, #d1fae5, #a7f3d0);
}

/* Chat unread badge */
.chat-unread-badge {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: linear-gradient(to bottom right, #10b981, #0d9488);
  color: white;
  font-size: 0.75rem;
  border-radius: 9999px;
  height: 1.25rem;
  width: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 2px white;
}

.dark .chat-unread-badge {
  box-shadow: 0 0 0 2px #1e293b;
}

/* Chat info */
.chat-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-right: 1.5rem;
}

/* Chat name */
.chat-name {
  font-weight: 600;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dark .chat-name {
  color: #f1f5f9;
}

/* Chat preview */
.chat-preview {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dark .chat-preview {
  color: #9ca3af;
}

/* Chat time */
.chat-time {
  font-size: 0.75rem;
  color: #0d9488;
  white-space: nowrap;
  margin-left: 0.5rem;
}

.dark .chat-time {
  color: #5eead4;
}

/* No chats */
.no-chats {
  padding: 1.5rem;
  text-align: center;
  color: #9ca3af;
}

.dark .no-chats {
  color: #6b7280;
}

/* Chat window */
.chat-window {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Chat header */
.chat-header {
  padding: 1rem;
  background: linear-gradient(to right, #0d9488, #059669);
  color: white;
  flex-shrink: 0;
}

/* Chat title */
.chat-title {
  font-size: 1.125rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Chat subtitle */
.chat-subtitle {
  font-size: 0.875rem;
  opacity: 0.9;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Messages container - Scrollable */
.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  background: linear-gradient(to bottom, rgba(240, 253, 244, 0.2), white);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.dark .messages-container {
  background: linear-gradient(to bottom, rgba(6, 78, 59, 0.05), #1e293b);
}

/* No messages */
.no-messages {
  color: #9ca3af;
  text-align: center;
  padding: 1.5rem;
}

.dark .no-messages {
  color: #6b7280;
}

/* Date separator */
.date-separator {
  display: flex;
  justify-content: center;
  margin: 1rem 0;
}

.date-separator-text {
  padding: 0.25rem 0.75rem;
  background-color: #d1fae5;
  color: #065f46;
  font-size: 0.75rem;
  border-radius: 9999px;
}

.dark .date-separator-text {
  background-color: #064e3b;
  color: #5eead4;
}

/* Message wrapper */
.message-wrapper {
  display: flex;
}

/* Message bubble */
.message-bubble {
  padding: 0.75rem;
  border-radius: 0.75rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.message-bubble.sender {
  background: linear-gradient(to bottom right, #0d9488, #059669);
  color: white;
}

.message-bubble.receiver {
  background: linear-gradient(to right, #f9fafb, white);
  border: 1px solid #d1fae5;
  color: #1f2937;
}

.dark .message-bubble.receiver {
  background: linear-gradient(to right, #374151, #4b5563);
  border-color: #065f46;
  color: #f1f5f9;
}

/* Message sender */
.message-sender {
  font-size: 0.875rem;
  font-weight: 600;
}

/* Message files */
.message-files {
  margin: 0.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* File container */
.file-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
}

.dark .file-container {
  background-color: rgba(255, 255, 255, 0.1);
}

/* File icon */
.file-icon {
  font-size: 1.5rem;
}

/* File info */
.file-info {
  flex: 1;
  min-width: 0;
}

/* File name */
.file-name {
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* File actions */
.file-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

/* File action */
.file-action {
  font-size: 0.75rem;
  transition: color 0.2s;
}

.file-action:hover {
  text-decoration: underline;
}

.view-action {
  color: #5eead4;
}

.download-action {
  color: #0d9488;
}

.save-action {
  color: #9ca3af;
}

/* Image preview */
.image-preview {
  margin-top: 0.5rem;
}

/* Image file */
.image-file {
  max-width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #d1fae5;
  cursor: pointer;
  transition: opacity 0.2s;
}

.dark .image-file {
  border-color: #065f46;
}

.image-file:hover {
  opacity: 0.9;
}

/* Message text */
.message-text {
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

/* Message time */
.message-time {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.sender-time {
  color: #a7f3d0;
}

.receiver-time {
  color: #0d9488;
}

.dark .receiver-time {
  color: #5eead4;
}

/* Scroll bottom button */
.scroll-bottom-button {
  position: absolute;
  left: 1rem;
  bottom: 5.5rem;
  background: linear-gradient(to bottom right, #0d9488, #059669);
  color: white;
  padding: 0.75rem;
  border-radius: 9999px;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 10;
}

.scroll-bottom-button:hover {
  background: linear-gradient(to bottom right, #0f766e, #047857);
  transform: scale(1.1);
}

/* Attachments preview */
.attachments-preview {
  border-top: 1px solid #d1fae5;
  padding: 0.75rem;
  background: linear-gradient(to right, #f0fdf4, #dcfce7);
  flex-shrink: 0;
}

.dark .attachments-preview {
  border-top-color: #065f46;
  background: linear-gradient(to right, rgba(6, 78, 59, 0.1), rgba(6, 95, 70, 0.1));
}

/* Attachments header */
.attachments-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

/* Attachments title */
.attachments-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #065f46;
}

.dark .attachments-title {
  color: #5eead4;
}

/* Clear attachments */
.clear-attachments {
  font-size: 0.75rem;
  color: #ef4444;
  transition: color 0.2s;
}

.clear-attachments:hover {
  color: #dc2626;
}

/* Attachments grid */
.attachments-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

/* Attachment item */
.attachment-item {
  position: relative;
  cursor: pointer;
}

/* Attachment preview */
.attachment-preview {
  width: 5rem;
  height: 5rem;
  background: linear-gradient(to bottom right, #d1fae5, #a7f3d0);
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid #bbf7d0;
  position: relative;
}

.dark .attachment-preview {
  background: linear-gradient(to bottom right, #064e3b, #065f46);
  border-color: #047857;
}

/* Attachment image */
.attachment-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Attachment icon */
.attachment-icon {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Icon text */
.icon-text {
  font-size: 2rem;
}

/* Remove attachment */
.remove-attachment {
  position: absolute;
  top: -0.5rem;
  right: -0.5rem;
  background-color: #ef4444;
  color: white;
  border-radius: 9999px;
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.attachment-item:hover .remove-attachment {
  opacity: 1;
}

/* Attachment name */
.attachment-name {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Message input form */
.message-input-form {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-top: 1px solid #d1fae5;
  padding: 0.75rem;
  background: linear-gradient(to right, white, rgba(240, 253, 244, 0.5));
  flex-shrink: 0;
}

.dark .message-input-form {
  border-top-color: #065f46;
  background: linear-gradient(to right, #1e293b, rgba(6, 78, 59, 0.1));
}

/* Attachment button wrapper */
.attachment-button-wrapper {
  position: relative;
}

/* Attachment button */
.attachment-button {
  padding: 0.5rem;
  color: #0d9488;
  transition: all 0.2s;
  border-radius: 0.5rem;
}

.attachment-button:hover {
  color: #115e59;
  background-color: #f0fdf4;
}

.attachment-button.active {
  color: #0d9488;
  background-color: #f0fdf4;
}

.dark .attachment-button:hover {
  background-color: rgba(6, 78, 59, 0.3);
}

.dark .attachment-button.active {
  background-color: rgba(6, 78, 59, 0.3);
}

/* Attachment menu */
.attachment-menu {
  position: absolute;
  bottom: 100%;
  left: 0;
  margin-bottom: 0.5rem;
  width: 12rem;
  background: linear-gradient(to bottom, white, #f0fdf4);
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #d1fae5;
  z-index: 10;
  overflow: hidden;
}

.dark .attachment-menu {
  background: linear-gradient(to bottom, #1e293b, rgba(6, 78, 59, 0.2));
  border-color: #065f46;
}

/* Attachment menu item */
.attachment-menu-item {
  width: 100%;
  padding: 0.5rem 1rem;
  text-align: left;
  font-size: 0.875rem;
  color: #065f46;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.attachment-menu-item:hover {
  background: linear-gradient(to right, #f0fdf4, #dcfce7);
}

.dark .attachment-menu-item {
  color: #5eead4;
}

.dark .attachment-menu-item:hover {
  background: linear-gradient(to right, rgba(6, 78, 59, 0.3), rgba(6, 95, 70, 0.3));
}

/* Message input */
.message-input {
  flex: 1;
  border: 1px solid #d1fae5;
  border-radius: 0.75rem;
  padding: 0.75rem;
  background: white;
  color: #1f2937;
  outline: none;
  transition: all 0.2s;
}

.dark .message-input {
  background: #374151;
  border-color: #065f46;
  color: #f1f5f9;
}

.message-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.5);
  border-color: transparent;
}

/* Send button */
.send-button {
  background: linear-gradient(to right, #0d9488, #059669);
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 0.75rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.send-button:hover:not(:disabled) {
  background: linear-gradient(to right, #0f766e, #047857);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Empty state */
.empty-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  background: linear-gradient(to bottom, rgba(240, 253, 244, 0.2), white);
}

.dark .empty-state {
  color: #6b7280;
  background: linear-gradient(to bottom, rgba(6, 78, 59, 0.05), #1e293b);
}

/* Empty state content */
.empty-state-content {
  text-align: center;
}

/* Empty state title */
.empty-state-title {
  font-size: 1.125rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

/* Empty state text */
.empty-state-text {
  font-size: 0.875rem;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  max-height: 200px;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
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

/* Responsive adjustments */
@media (max-width: 767px) {
  .fixed-container {
    top: 56px;
  }
  
  .chat-container {
    margin: 0;
    border-radius: 0;
    height: calc(100vh - 56px);
  }
  
  .sidebar {
    width: 100%;
  }
  
  .chat-window {
    width: 100%;
  }
  
  .scroll-bottom-button {
    bottom: 5rem;
  }
}
/* For tablets and desktop, remove bottom offset */
@media (min-width: 768px) {
  .fixed-container {
    bottom: 64px;
  }
}

/* For desktop only */
@media (min-width: 1024px) {
  .fixed-container {
    left: 256px; /* Desktop sidebar width */
    bottom: 0;
  }
  
  .chat-container {
    margin: 1rem 2rem 2rem 2rem; /* Adjust margins for desktop */
  }
}
</style>
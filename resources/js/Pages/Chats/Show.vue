<script setup>
import { ref, nextTick, watch, onMounted, computed } from 'vue'
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
    const msg = chat.latest_message.message
    return msg.length > 30 ? msg.substring(0, 30) + '...' : msg
  }
  return 'No messages yet'
}

// ====================
// Get latest message time
// ====================
const getLatestTime = (chat) => {
  if (chat.latest_message?.created_at) {
    const date = new Date(chat.latest_message.created_at)
    const now = new Date()
    const diffMs = now - date
    const diffHours = diffMs / (1000 * 60 * 60)
    
    if (diffHours < 24) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (diffHours < 48) {
      return 'Yesterday'
    } else {
      return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
    }
  }
  return ''
}

// ====================
// Scroll to bottom
// ====================
const scrollToBottom = () => {
  nextTick(() => {
    const el = messagesContainer.value
    if (el) el.scrollTop = el.scrollHeight
  })
}

// Scroll on mount and whenever messages change
onMounted(() => scrollToBottom())
watch(() => props.chat?.messages?.length, () => scrollToBottom())

// ====================
// Send message
// ====================
const sendMessage = () => {
  if (!newMessage.value.trim() || !props.chat) return
  router.post(`/chats/${props.chat.id}/messages`, { message: newMessage.value }, {
    preserveScroll: true,
    onSuccess: () => {
      newMessage.value = ''
      scrollToBottom()
    }
  })
}

// ====================
// Open chat
// ====================
const openChat = (chatId) => router.visit(`/chats/${chatId}`)
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="flex h-[80vh] bg-white dark:bg-gray-800 rounded-xl shadow mt-6 overflow-hidden transition-colors">

        <!-- Sidebar -->
        <div class="w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col">
          <div class="flex justify-between items-center p-3 bg-indigo-600 text-white">
            <h2 class="font-semibold">Chats</h2>
            <span 
              v-if="chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) > 0"
              class="bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center"
            >
              {{ chats.reduce((sum, chat) => sum + (chat.unread_count || 0), 0) }}
            </span>
          </div>

          <div class="flex-1 overflow-y-auto">
            <div
              v-for="chat in sortedChats"
              :key="chat.id"
              @click="openChat(chat.id)"
              class="p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-gray-700 cursor-pointer transition-colors relative"
              :class="{'bg-indigo-100 dark:bg-gray-700': chat.id === selectedChat}"
            >
              <!-- Unread badge -->
              <span 
                v-if="chat.unread_count > 0"
                class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
              >
                {{ chat.unread_count > 9 ? '9+' : chat.unread_count }}
              </span>
              
              <!-- Chat info -->
              <div class="flex justify-between items-start pr-6">
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-800 dark:text-gray-100 truncate">
                    {{ getChatName(chat) }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ getLatestMessage(chat) }}
                  </p>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap ml-2">
                  {{ getLatestTime(chat) }}
                </span>
              </div>
              
              <!-- Chat type/description -->
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ getChatDescription(chat) }}
              </p>
            </div>

            <div v-if="!chats.length" class="p-6 text-center text-gray-400 dark:text-gray-500">
              No chats available.
            </div>
          </div>
        </div>

        <!-- Chat Window -->
        <div v-if="chat" class="flex-1 flex flex-col">
          <!-- Header -->
          <div class="bg-indigo-600 text-white px-5 py-3 flex justify-between items-center">
            <h2 class="text-lg font-semibold">{{ getChatName(chat) }}</h2>
            <span class="text-sm opacity-80">{{ getChatDescription(chat) }}</span>
          </div>

          <!-- Messages -->
          <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
            <div v-if="!chat.messages.length" class="text-gray-400 dark:text-gray-500 text-center py-6">
              No messages yet. Start the conversation!
            </div>

            <div
              v-for="msg in chat.messages"
              :key="msg.id"
              :class="['flex', msg.user_id === auth.user.id ? 'justify-end' : 'justify-start']"
            >
              <div
                :class="[ 
                  'p-3 rounded-lg max-w-xs', 
                  msg.user_id === auth.user.id 
                    ? 'bg-indigo-600 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100'
                ]"
              >
                <p class="text-sm font-semibold">{{ msg.user?.name || 'User' }}</p>
                <p class="text-sm">{{ msg.message }}</p>
                <p class="text-xs text-gray-300 mt-1">{{ new Date(msg.created_at).toLocaleTimeString() }}</p>
              </div>
            </div>
          </div>

          <!-- Message Input -->
          <form @submit.prevent="sendMessage" class="flex items-center gap-2 border-t border-gray-200 dark:border-gray-700 p-3">
            <input
              v-model="newMessage"
              type="text"
              placeholder="Type your message..."
              class="flex-1 border rounded-lg p-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 focus:ring focus:ring-indigo-300 transition-colors"
            />
            <button
              type="submit"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Send
            </button>
          </form>
        </div>

        <!-- Empty State -->
        <div v-else class="flex-1 flex items-center justify-center text-gray-400 dark:text-gray-500">
          <div class="text-center">
            <div class="text-lg font-medium mb-2">Select a chat</div>
            <p class="text-sm">Choose a conversation from the sidebar to start messaging</p>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>
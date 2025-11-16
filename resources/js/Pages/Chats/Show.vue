<script setup>
import { ref, nextTick, watch, onMounted } from 'vue'
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
          </div>

          <div class="flex-1 overflow-y-auto">
            <div
              v-for="chat in chats"
              :key="chat.id"
              @click="openChat(chat.id)"
              class="p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
              :class="{'bg-indigo-100 dark:bg-gray-700': chat.id === selectedChat}"
            >
              <h3 class="font-semibold text-gray-800 dark:text-gray-100">
                {{ chat.name ?? chat.project?.title ?? `Chat #${chat.id}` }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ chat.project?.title ?? 'Private Chat' }}
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
            <h2 class="text-lg font-semibold">{{ chat.name || chat.project?.title }}</h2>
            <span class="text-sm opacity-80">{{ chat.project?.title ?? 'Private Chat' }}</span>
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
          Select a chat to start messaging
        </div>
      </div>
    </main>
  </Layout>
</template>

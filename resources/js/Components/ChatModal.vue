<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const emit = defineEmits(['close'])
const chats = ref([])
const loading = ref(true)
const error = ref(null)

const fetchChats = async () => {
  try {
    const response = await fetch('/chat/list')
    chats.value = await response.json()
  } catch (err) {
    error.value = 'Failed to load chats.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchChats)
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-lg overflow-hidden">
      <!-- Header -->
      <div class="flex justify-between items-center bg-indigo-600 text-white px-4 py-3">
        <h2 class="text-lg font-semibold">Project Chats</h2>
        <button @click="$emit('close')" class="text-xl leading-none">&times;</button>
      </div>

      <!-- Content -->
      <div class="p-4 max-h-[70vh] overflow-y-auto">
        <div v-if="loading" class="text-center py-6 text-gray-500">Loading...</div>
        <div v-else-if="error" class="text-center py-6 text-red-600">{{ error }}</div>
        <div v-else-if="chats.length === 0" class="text-center py-6 text-gray-400">No chats available</div>

        <div v-else class="space-y-3">
          <div
            v-for="chat in chats"
            :key="chat.id"
            @click="router.visit(`/chats/${chat.id}`)"
            class="p-4 bg-gray-100 hover:bg-indigo-100 cursor-pointer rounded-lg transition"
          >
          <h3 class="font-semibold text-gray-800">
  {{ chat.display_name }}
</h3>
<p class="text-gray-500 text-sm">
  {{ chat.type === 'group' ? `Project: ${chat.project?.title ?? '—'}` : 'Private chat' }}
</p>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

defineProps({
  project: Object,
})

const comment = ref('')
const form = useForm({ comment: '' })

const submitComment = () => {
  form.post(`/projects/${project.id}/comments`, {
    onSuccess: () => (form.comment = ''),
  })
}
</script>

<template>
  <div class="p-8 max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">{{ project.title }}</h1>
    <p class="text-gray-600 mb-4">{{ project.description }}</p>

    <div class="w-full bg-gray-200 rounded-full h-3 mb-6">
      <div
        class="bg-blue-600 h-3 rounded-full"
        :style="{ width: project.progress + '%' }"
      ></div>
    </div>

    <h2 class="text-xl font-semibold mb-3">Comments</h2>

    <form @submit.prevent="submitComment" class="flex gap-2 mb-4">
      <input
        v-model="form.comment"
        type="text"
        placeholder="Write a comment..."
        class="flex-1 border rounded p-2"
      />
      <button
        type="submit"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Send
      </button>
    </form>

    <div>
      <p v-for="(c, i) in project.comments" :key="i" class="border-b py-2">
        <strong>{{ c.user.name }}:</strong> {{ c.comment }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'
import { Trash2, Send, Edit2 } from 'lucide-vue-next'

const { props } = usePage()

// Computed props
const tasks = computed(() => props.tasks || [])
const project = computed(() => props.project || {})
const can = computed(() => props.auth?.can || {})
const authUser = computed(() => props.auth?.user || {})
const progress = computed(() => {
  const completed = tasks.value.filter(t => t.status === 'Completed').length
  const total = tasks.value.length || 1
  return Math.round((completed / total) * 100)
})

// Reactive objects for new comments and edits
const newComment = ref({})
const editTask = ref({})

// Methods
const addComment = async (taskId) => {
  if (!newComment.value[taskId]) return

  try {
    // Send the comment to the backend
    const response = await router.post(`/tasks/${taskId}/comments`, {
      message: newComment.value[taskId],
    }, { preserveScroll: true })

    // If your backend returns the new comment object
    const newCommentData = response?.props?.comment || {
      id: Date.now(), // temporary ID if backend doesn't return
      message: newComment.value[taskId],
      user: authUser.value,
    }

    // Push comment locally
    const task = tasks.value.find(t => t.id === taskId)
    if (task) {
      task.comments.push(newCommentData)
    }

    // Clear input
    newComment.value[taskId] = ''
  } catch (error) {
    console.error(error)
  }
}


const toggleStatus = async (task) => {
  try {
    task.status = task.status === 'Completed' ? 'In Progress' : 'Completed'
    await router.patch(`/projects/${project.value.id}/tasks/${task.id}/toggle`, {}, {
      preserveScroll: true,
      onSuccess: (page) => {
        if (page.props.progress !== undefined) project.value.progress = page.props.progress
      },
    })
  } catch (error) {
    console.error(error)
  }
}

const deleteTask = (taskId) => {
  if (!confirm('Are you sure you want to delete this task?')) return
  router.delete(`/projects/${project.value.id}/tasks/${taskId}`)
}

const startEdit = (task) => {
  editTask.value[task.id] = { ...task }
}

const saveEdit = async (taskId) => {
  const data = editTask.value[taskId]
  try {
    const response = await router.put(`/projects/${project.value.id}/tasks/${taskId}`, data, { preserveScroll: true })

    const taskIndex = tasks.value.findIndex(t => t.id === taskId)
    if (taskIndex !== -1) tasks.value[taskIndex] = { ...tasks.value[taskIndex], ...data }

    editTask.value[taskId] = null
    if (response.props.progress !== undefined) project.value.progress = response.props.progress
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="min-h-screen dark:bg-gray-900">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Tasks for "{{ project.title }}"
          </h1>
          <Link href="/projects" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
            ← Back to Projects
          </Link>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-3 mb-6">
          <div
            class="h-3 bg-green-500 rounded-full transition-all duration-500"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Progress: {{ progress }}%</p>

        <!-- Tasks -->
        <div v-if="tasks.length > 0" class="space-y-6">
          <div v-for="task in tasks"
     :key="task.id"
     class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 transition-colors">

  <!-- Header -->
  <div class="flex justify-between items-center mb-2">
    <div class="flex items-center gap-2">
      <input
        v-if="can['is_developer']"
        type="checkbox"
        :checked="task.status === 'Completed'"
        @change="toggleStatus(task)"
        class="w-5 h-5 text-green-600"
      />
      <h2
        :class="[
          'text-lg font-semibold',
          task.status === 'Completed'
            ? 'line-through text-gray-500 dark:text-gray-400'
            : 'text-gray-800 dark:text-gray-100'
        ]"
      >
        {{ task.title }}
      </h2>
    </div>
  </div>

  <!-- Description -->
  <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ task.description }}</p>

  <!-- Task Info -->
  <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
    {{ task.task_type }} | Weight: {{ task.weight }} | Status: {{ task.status }}
  </p>

  <!-- Comments -->
  <div class="mt-2 border-t border-gray-200 dark:border-gray-700 pt-4">
    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Comments</h4>

    <div
      v-for="comment in task.comments"
      :key="comment.id"
      class="bg-gray-50 dark:bg-gray-700 p-3 rounded mb-2 border dark:border-gray-600"
    >
      <p class="text-sm font-semibold dark:text-gray-100">{{ comment.user.name }}</p>
      <p class="text-sm dark:text-gray-100">{{ comment.message }}</p>
    </div>

    <!-- Add Comment -->
    <div
      v-if="authUser && (project.created_by === authUser.id || authUser.role === 'Super Admin')"
      class="flex flex-col gap-2 mt-3 bg-gray-50 dark:bg-gray-700 p-3 rounded transition-colors"
    >
      <textarea
        v-model="newComment[task.id]"
        rows="2"
        placeholder="Add a comment..."
        class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
      />
      <div class="flex justify-end">
        <button
          @click="addComment(task.id)"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-1 text-sm transition-colors"
        >
          <Send class="w-4 h-4" /> Post
        </button>
      </div>
    </div>
  </div>
</div>

        </div>

        <div v-else class="text-gray-500 dark:text-gray-400 text-center mt-20">
          No tasks yet for this project.
        </div>
      </div>
    </main>
  </Layout>
</template>

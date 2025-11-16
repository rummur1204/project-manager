<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'

const props = defineProps({
  project: Object,
})

const page = usePage()
const user = page.props.auth.user

const comments = ref(props.project.comments || [])
const newComment = ref('')

// Weighted progress
const calculateWeightedProgress = (tasks) => {
  const totalWeight = tasks.reduce((sum, t) => sum + (t.weight || 0), 0) || 1
  const completedWeight = tasks
    .filter(t => t.status === 'Completed')
    .reduce((sum, t) => sum + (t.weight || 0), 0)
  return Math.round((completedWeight / totalWeight) * 100)
}

// Developer helpers
const isAssignedDeveloper = computed(() => props.project.developers?.some(d => d.id === user.id))
const currentDeveloper = computed(() => props.project.developers?.find(d => d.id === user.id))
const hasAccepted = computed(() => currentDeveloper.value?.pivot?.accepted)

const acceptProject = () => {
  router.post(`/projects/${props.project.id}/accept`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (currentDeveloper.value) currentDeveloper.value.pivot.accepted = true
    },
  })
}

const declineProject = () => {
  router.post(`/projects/${props.project.id}/decline`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (currentDeveloper.value) currentDeveloper.value.pivot.accepted = false
    },
  })
}

// Dynamic project status
const projectStatus = computed(() => (hasAccepted.value ? 'In Progress' : 'Pending'))

// Tasks status
const getTaskStatus = (task) => {
  if (!isAssignedDeveloper.value) return task.status
  return hasAccepted.value ? task.status : 'Pending'
}

// Comments
const commentsContainer = ref(null)
const scrollToBottom = () => nextTick(() => {
  if (commentsContainer.value) commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight
})

const detectUrgency = (text) => {
  const lower = text.toLowerCase()
  if (lower.includes('urgent') || lower.includes('immediately') || lower.includes('asap') || lower.includes('critical')) return 'Critical'
  if (lower.includes('soon') || lower.includes('important') || lower.includes('issue')) return 'High'
  return 'Normal'
}

const urgencyColor = (urgency) => ({
  Critical: 'text-red-600 dark:text-red-400 font-semibold',
  High: 'text-orange-600 dark:text-orange-400 font-semibold',
  Normal: 'text-gray-600 dark:text-gray-300'
}[urgency])

const addComment = (e) => {
  e.preventDefault()
  if (!newComment.value.trim()) return alert('Please write a comment.')
  const urgency = detectUrgency(newComment.value)
  router.post(`/projects/${props.project.id}/comments`, { message: newComment.value, urgency }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      comments.value.push({
        id: Date.now(),
        user,
        message: newComment.value,
        urgency,
        created_at: new Date().toISOString()
      })
      newComment.value = ''
      scrollToBottom()
    },
  })
}
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="max-w-5xl mx-auto rounded-2xl transition-colors duration-300">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ project.title }}</h2>
          <Link href="/projects" class="text-indigo-600 dark:text-indigo-400 hover:underline">← Back</Link>
        </div>

        <p class="text-gray-700 dark:text-gray-300 mb-4">{{ project.description }}</p>

        <!-- Info -->
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300 mb-6">
          <p><strong>Status:</strong> {{ projectStatus }}</p>
          <p><strong>Due Date:</strong> {{ project.due_date }}</p>
          <p><strong>Client:</strong> {{ project.client?.name }}</p>
          <p><strong>Progress:</strong> {{ calculateWeightedProgress(project.tasks) }}%</p>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 h-4 rounded-full mb-8 overflow-hidden">
          <div
            class="bg-green-600 h-4 rounded-full transition-all duration-500"
            :style="{ width: calculateWeightedProgress(project.tasks) + '%' }"
          ></div>
        </div>

        <!-- Accept / Decline -->
        <div v-if="isAssignedDeveloper && !hasAccepted" class="mb-6 flex gap-3">
          <button @click="acceptProject" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            Accept
          </button>
          <button @click="declineProject" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
            Decline
          </button>
        </div>

        <!-- Developers -->
        <section class="mb-8">
          <h3 class="text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">Developers</h3>
          <ul class="list-disc pl-5 space-y-1">
            <li v-for="dev in project.developers" :key="dev.id" class="text-gray-700 dark:text-gray-300">
              {{ dev.name }}
              <span v-if="dev.pivot.accepted" class="text-green-600 dark:text-green-400 text-sm ml-2">(Accepted)</span>
              <span v-else class="text-gray-500 dark:text-gray-400 text-sm ml-2">(Pending)</span>
            </li>
          </ul>
        </section>

        <!-- Tasks -->
        <section class="mb-10">
          <h3 class="text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">Tasks</h3>
          <div
            v-for="task in project.tasks"
            :key="task.id"
            class="border border-gray-200 dark:border-gray-700 p-4 mb-3 rounded-lg hover:shadow transition bg-gray-50 dark:bg-gray-800"
          >
            <h4 class="font-medium text-gray-800 dark:text-gray-200">{{ task.title }}</h4>
            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ task.description }}</p>
            <p class="text-xs mt-1 text-indigo-600 dark:text-indigo-400">
              Type: {{ task.task_type }} |
              Status: {{ getTaskStatus(task) }} |
              Assigned to:
              <span v-if="task.developers?.length">
                {{ task.developers.map(dev => dev.name).join(', ') }}
              </span>
              <span v-else>None</span>
            </p>
          </div>

          <p v-if="!project.tasks.length" class="text-gray-500 dark:text-gray-400">No tasks available.</p>
        </section>

        <!-- Comments -->
        <section>
          <h3 class="text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">Comments</h3>

          <form @submit.prevent="addComment" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border dark:border-gray-700">
            <h4 class="font-medium mb-2 text-gray-800 dark:text-gray-200">Add a Comment</h4>
            <textarea
              v-model="newComment"
              placeholder="Write your comment..."
              class="w-full border dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded p-2 mb-3 resize-none"
            ></textarea>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
              Submit
            </button>
          </form>

          <div ref="commentsContainer" class="max-h-72 overflow-y-auto border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 space-y-4">
            <div v-for="comment in comments" :key="comment.id" class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg bg-white dark:bg-gray-800 shadow-sm">
              <p class="text-gray-800 dark:text-gray-100 text-sm">{{ comment.message }}</p>
              <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
                <strong>By:</strong> {{ comment.user?.name }} |
                <strong>Urgency:</strong>
                <span :class="urgencyColor(comment.urgency)">{{ comment.urgency }}</span> |
                {{ new Date(comment.created_at).toLocaleString() }}
              </p>
            </div>

            <p v-if="!comments.length" class="text-gray-500 dark:text-gray-400 text-center">No comments yet.</p>
          </div>
        </section>
      </div>
    </main>
  </Layout>
</template>

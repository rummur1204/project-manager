<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Edit, Trash2, ClipboardList, PlusCircle } from 'lucide-vue-next'
import Layout from '../Dashboard/Layout.vue'

const { props } = usePage()

const projects = ref(props.projects || [])

// Weighted progress
const calculateWeightedProgress = (tasks) => {
  const totalWeight = tasks.reduce((sum, t) => sum + (t.weight || 0), 0) || 1
  const completedWeight = tasks
    .filter(t => t.status === 'Completed')
    .reduce((sum, t) => sum + (t.weight || 0), 0)
  return Math.round((completedWeight / totalWeight) * 100)
}

const can = computed(() => props.auth?.can || {})
const user = computed(() => props.auth.user)

const userCan = (perm) => !!can.value[perm]

// Developer helpers
const isAssignedDeveloper = (project) => {
  return project.developers?.some(d => d.id === user.value.id) ?? false
}

const hasAccepted = (project) => {
  const dev = project.developers?.find(d => d.id === user.value.id)
  return dev?.pivot?.accepted ?? false
}

// Accept / Decline project
const acceptProject = (projectId) => {
  router.post(`/projects/${projectId}/accept`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const project = projects.value.find(p => p.id === projectId)
      if (project) {
        const dev = project.developers.find(d => d.id === user.value.id)
        if (dev) dev.pivot.accepted = true
      }
    }
  })
}

const declineProject = (projectId) => {
  router.post(`/projects/${projectId}/decline`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const project = projects.value.find(p => p.id === projectId)
      if (project) {
        const dev = project.developers.find(d => d.id === user.value.id)
        if (dev) dev.pivot.accepted = false
      }
    }
  })
}

const deleteProject = (id) => {
  if (confirm('Are you sure you want to delete this project?')) {
    router.delete(`/projects/${id}`)
  }
}

// Dynamic status
const getProjectStatus = (project) => {
  if (isAssignedDeveloper(project)) {
    return hasAccepted(project) ? 'In Progress' : 'Pending'
  }
  return project.status
}
</script>

<template>
  <Layout>
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Projects</h1>
      <Link
        v-if="userCan('create projects')"
        href="/projects/create"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition"
      >
        <PlusCircle class="w-5 h-5" /> New Project
      </Link>
    </div>

    <!-- Projects Grid -->
    <div v-if="projects.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="project in projects"
        :key="project.id"
        class="bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between cursor-pointer"
        @click="router.visit(`/projects/${project.id}`)"
      >
        <div>
          <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 truncate">
            {{ project.title }}
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Status: <span class="font-medium">{{ getProjectStatus(project) }}</span>
          </p>

          <p class="text-xs text-gray-400 mt-1">
            Due: {{ project.due_date || 'N/A' }}
          </p>

          <!-- Weighted Progress Bar -->
          <div class="mt-4">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-indigo-600 h-2 rounded-full transition-all"
                :style="{ width: calculateWeightedProgress(project.tasks) + '%' }"
              ></div>
            </div>
            <p class="text-xs text-right text-gray-500 dark:text-gray-400 mt-1">
              {{ calculateWeightedProgress(project.tasks) }}%
            </p>
          </div>
        </div>

        <!-- Buttons -->
        <div
          v-if="userCan('edit projects') || userCan('delete projects') || userCan('view projects') || isAssignedDeveloper(project)"
          class="flex justify-between items-center mt-5 pt-3 border-t border-gray-100 dark:border-gray-700"
          @click.stop
        >
          <div class="flex gap-2">
            <!-- Accept / Decline -->
            <template v-if="isAssignedDeveloper(project) && !hasAccepted(project)">
              <button
                @click="acceptProject(project.id)"
                class="flex items-center gap-1 bg-green-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-green-700 transition"
              >
                Accept
              </button>
              <button
                @click="declineProject(project.id)"
                class="flex items-center gap-1 bg-red-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-red-700 transition"
              >
                Decline
              </button>
            </template>

            <Link
              v-if="userCan('edit projects')"
              :href="`/projects/${project.id}/edit`"
              class="flex items-center gap-1 bg-blue-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-blue-700 transition"
            >
              <Edit class="w-4 h-4" /> Edit
            </Link>

            <button
              v-if="userCan('delete projects')"
              @click="deleteProject(project.id)"
              class="flex items-center gap-1 bg-red-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-red-700 transition"
            >
              <Trash2 class="w-4 h-4" /> Delete
            </button>
          </div>

          <Link
            v-if="userCan('view projects')"
            :href="`/projects/${project.id}/tasks`"
            class="flex items-center gap-1 bg-gray-700 text-white text-sm px-3 py-1.5 rounded-md hover:bg-gray-800 transition"
          >
            <ClipboardList class="w-4 h-4" /> Tasks
          </Link>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 mt-20">
      <ClipboardList class="w-12 h-12 mb-3 text-gray-400" />
      <p>No projects available yet.</p>
      <Link
        v-if="userCan('create projects')"
        href="/projects/create"
        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        Create your first project
      </Link>
    </div>
  </Layout>
</template>

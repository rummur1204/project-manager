<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Edit, Trash2, ClipboardList, PlusCircle } from 'lucide-vue-next'
import Layout from '../Dashboard/Layout.vue'

const { props } = usePage()

const projects = ref(props.projects || [])
const filteredProjects = ref([...props.projects || []])
const currentSearchQuery = ref('')
const searchActive = ref(false)

// Weighted progress calculation
const calculateWeightedProgress = (tasks) => {
  if (!tasks || tasks.length === 0) return 0
  const totalWeight = tasks.reduce((sum, t) => sum + (t.weight || 0), 0) || 1
  const completedWeight = tasks
    .filter(t => t.status === 'Completed')
    .reduce((sum, t) => sum + (t.weight || 0), 0)
  return Math.round((completedWeight / totalWeight) * 100)
}

// Update progress for a specific project
const updateProjectProgress = (projectId, newProgress, updatedTasks) => {
  const projectIndex = projects.value.findIndex(p => p.id === projectId)
  if (projectIndex !== -1) {
    // Update the project's tasks
    if (updatedTasks) {
      projects.value[projectIndex].tasks = updatedTasks
    }
    
    // Update filtered projects too
    const filteredIndex = filteredProjects.value.findIndex(p => p.id === projectId)
    if (filteredIndex !== -1) {
      if (updatedTasks) {
        filteredProjects.value[filteredIndex].tasks = updatedTasks
      }
    }
    
    // Trigger Vue reactivity
    projects.value = [...projects.value]
    filteredProjects.value = [...filteredProjects.value]
  }
}

// Listen for progress updates from task pages
const handleTaskProgressUpdate = (event) => {
  const { projectId, progress, tasks } = event.detail
  updateProjectProgress(projectId, progress, tasks)
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
    preserveState: false,
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
    router.delete(`/projects/${id}`,{ preserveState: false,})
  }
}

// Get project status with auto-completion at 100% progress
const getProjectStatus = (project) => {
  const progress = calculateWeightedProgress(project.tasks)
  if (progress >= 100) {
    return 'Completed'
  }
  
  if (isAssignedDeveloper(project)) {
    return hasAccepted(project) ? 'In Progress' : 'Pending'
  }
  
  return project.status || 'Pending'
}

// Get status color classes
const getStatusColor = (project) => {
  const status = getProjectStatus(project)
  const isDarkMode = document.documentElement.classList.contains('dark')
  
  switch (status.toLowerCase()) {
    case 'pending':
      return {
        bg: isDarkMode ? 'bg-red-900/20' : 'bg-red-50',
        border: isDarkMode ? 'border-red-800' : 'border-red-200',
        text: 'text-red-800 dark:text-red-300',
        progress: 'bg-red-600',
        statusBg: 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300',
        status: 'pending'
      }
    case 'in progress':
      return {
        bg: isDarkMode ? 'bg-yellow-900/20' : 'bg-yellow-50',
        border: isDarkMode ? 'border-yellow-800' : 'border-yellow-200',
        text: 'text-yellow-800 dark:text-yellow-300',
        progress: 'bg-yellow-600',
        statusBg: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
        status: 'in progress'
      }
    case 'completed':
      return {
        bg: isDarkMode ? 'bg-green-900/20' : 'bg-green-50',
        border: isDarkMode ? 'border-green-800' : 'border-green-200',
        text: 'text-green-800 dark:text-green-300',
        progress: 'bg-green-600',
        statusBg: 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
        status: 'completed'
      }
    default:
      return {
        bg: isDarkMode ? 'bg-gray-900/20' : 'bg-gray-50',
        border: isDarkMode ? 'border-gray-800' : 'border-gray-200',
        text: 'text-gray-800 dark:text-gray-300',
        progress: 'bg-gray-600',
        statusBg: 'bg-gray-100 dark:bg-gray-900/40 text-gray-800 dark:text-gray-300',
        status: 'unknown'
      }
  }
}

// Search handler
const handlePageSearch = (event) => {
  currentSearchQuery.value = event.detail.query
  searchActive.value = !!event.detail.query.trim()
  
  if (!currentSearchQuery.value.trim()) {
    filteredProjects.value = [...props.projects || []]
    return
  }
  
  const query = currentSearchQuery.value.toLowerCase().trim()
  filteredProjects.value = projects.value.filter(project => {
    return (
      project.title.toLowerCase().includes(query) ||
      (project.description && project.description.toLowerCase().includes(query)) ||
      (project.client && project.client.toLowerCase().includes(query)) ||
      (getProjectStatus(project).toLowerCase().includes(query)) ||
      (project.developers?.some(dev => dev.name.toLowerCase().includes(query)))
    )
  })
}

// Clear search filter
const clearSearchFilter = () => {
  currentSearchQuery.value = ''
  searchActive.value = false
  filteredProjects.value = [...projects.value]
}

// Set up event listeners
onMounted(() => {
  window.addEventListener('page-search', handlePageSearch)
  window.addEventListener('task-progress-updated', handleTaskProgressUpdate)
})

// Clean up
onUnmounted(() => {
  window.removeEventListener('page-search', handlePageSearch)
  window.removeEventListener('task-progress-updated', handleTaskProgressUpdate)
})
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

    <!-- Search Status Bar -->
    <div v-if="searchActive" class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-indigo-800 dark:text-indigo-300">
            🔍 Searching for: "<span class="font-bold">{{ currentSearchQuery }}</span>"
          </span>
          <span class="text-xs text-indigo-600 dark:text-indigo-400">
            ({{ filteredProjects.length }} of {{ projects.length }} projects)
          </span>
        </div>
        <button
          @click="clearSearchFilter"
          class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 underline"
        >
          Clear search
        </button>
      </div>
    </div>

    <!-- Projects Grid -->
    <div v-if="filteredProjects.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="project in filteredProjects"
        :key="project.id"
        class="rounded-xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between cursor-pointer border-2 relative"
        :class="[getStatusColor(project).bg, getStatusColor(project).border]"
        @click="router.visit(`/projects/${project.id}`)"
      >
        <!-- Auto-completed ribbon -->
        <div 
          v-if="calculateWeightedProgress(project.tasks) >= 100 && getProjectStatus(project) === 'Completed'"
          class="absolute -top-2 -right-2 bg-green-600 text-white text-xs font-bold px-3 py-1 rounded shadow-lg"
        >
          AUTO-COMPLETED
        </div>
        
        <div>
          <!-- Status Badge -->
          <div class="mb-3">
            <span 
              class="text-xs font-semibold px-3 py-1 rounded-full"
              :class="getStatusColor(project).statusBg"
            >
              {{ getProjectStatus(project) }}
              <span v-if="calculateWeightedProgress(project.tasks) >= 100 && getProjectStatus(project) === 'Completed'">
                🎯
              </span>
            </span>
          </div>
          
          <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 truncate">
            {{ project.title }}
          </h2>
          
          <p class="text-sm mt-2" :class="getStatusColor(project).text">
            <span class="font-medium">Progress:</span> {{ calculateWeightedProgress(project.tasks) }}%
          </p>

          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Due: {{ project.due_date || 'N/A' }}
          </p>

          <!-- Weighted Progress Bar -->
          <div class="mt-4">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all"
                :style="{ width: Math.min(calculateWeightedProgress(project.tasks), 100) + '%' }"
                :class="getStatusColor(project).progress"
              ></div>
            </div>
            <p class="text-xs text-right mt-1" :class="getStatusColor(project).text">
              {{ calculateWeightedProgress(project.tasks) }}%
              <span v-if="calculateWeightedProgress(project.tasks) >= 100" class="ml-1">✓</span>
            </p>
          </div>
        </div>

        <!-- Buttons -->
        <div
          v-if="userCan('edit projects') || userCan('delete projects') || userCan('view projects') || isAssignedDeveloper(project)"
          class="flex justify-between items-center mt-5 pt-3 border-t"
          :class="getStatusColor(project).border"
          @click.stop
        >
          <div class="flex gap-2">
            <!-- Accept / Decline - Only show if project is not completed -->
            <template v-if="getProjectStatus(project) !== 'Completed' && isAssignedDeveloper(project) && !hasAccepted(project)">
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

            <!-- Edit button - Only show for non-completed projects if user can edit -->
            <Link
              v-if="userCan('edit projects') && getProjectStatus(project) !== 'Completed'"
              :href="`/projects/${project.id}/edit`"
              class="flex items-center gap-1 bg-blue-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-blue-700 transition"
            >
              <Edit class="w-4 h-4" /> Edit
            </Link>

            <!-- For completed projects, show a disabled or different styled edit button -->
            <Link
              v-if="userCan('edit projects') && getProjectStatus(project) === 'Completed'"
              :href="`/projects/${project.id}/edit`"
              class="flex items-center gap-1 bg-gray-400 text-white text-sm px-3 py-1.5 rounded-md hover:bg-gray-500 transition"
              title="Edit completed project"
            >
              <Edit class="w-4 h-4" /> Review
            </Link>

            <button
              v-if="userCan('delete projects')"
              @click="deleteProject(project.id)"
              class="flex items-center gap-1 bg-red-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-red-700 transition"
            >
              <Trash2 class="w-4 h-4" /> Delete
            </button>
          </div>

          <!-- <Link
            v-if="userCan('view projects')"
            :href="`/projects/${project.id}/tasks`"
            class="flex items-center gap-1 bg-gray-700 text-white text-sm px-3 py-1.5 rounded-md hover:bg-gray-800 transition"
          >
            <ClipboardList class="w-4 h-4" /> Tasks
          </Link> -->
        </div>
      </div>
    </div>

    <!-- No Results Message -->
    <div v-else-if="searchActive" class="text-center py-12">
      <div class="text-gray-400 dark:text-gray-500 text-lg mb-4">
        No projects found matching "{{ currentSearchQuery }}"
      </div>
      <button
        @click="clearSearchFilter"
        class="text-indigo-600 dark:text-indigo-400 hover:underline"
      >
        Clear search to show all projects
      </button>
    </div>

    <!-- Original Empty State (when no projects at all) -->
    <div v-else-if="!projects.length" class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 mt-20">
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
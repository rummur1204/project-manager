<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Edit, Trash2, ClipboardList, PlusCircle, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import Layout from '../Dashboard/Layout.vue'

const { props } = usePage()

const projects = ref(props.projects?.data || [])
const filteredProjects = ref([...props.projects?.data || []])
const currentSearchQuery = ref('')
const searchActive = ref(false)

// Pagination properties
const currentPage = ref(props.projects?.current_page || 1)
const lastPage = ref(props.projects?.last_page || 1)
const perPage = ref(props.projects?.per_page || 6) // 2 rows × 3 cards = 6 projects per page

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
    filteredProjects.value = [...props.projects?.data || []]
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

// Pagination functions
const goToPage = (page) => {
  if (page < 1 || page > lastPage.value) return
  router.get(`/projects?page=${page}`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      currentPage.value = props.projects?.current_page || 1
      projects.value = props.projects?.data || []
      filteredProjects.value = [...projects.value]
    }
  })
}

const getPageNumbers = () => {
  const pages = []
  const maxVisiblePages = 5
  
  if (lastPage.value <= maxVisiblePages) {
    // Show all pages
    for (let i = 1; i <= lastPage.value; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    // Calculate start and end
    let start = Math.max(2, currentPage.value - 1)
    let end = Math.min(lastPage.value - 1, currentPage.value + 1)
    
    // Adjust if we're near the beginning
    if (currentPage.value <= 3) {
      end = 4
    }
    
    // Adjust if we're near the end
    if (currentPage.value >= lastPage.value - 2) {
      start = lastPage.value - 3
    }
    
    // Add ellipsis after first page if needed
    if (start > 2) {
      pages.push('...')
    }
    
    // Add middle pages
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    // Add ellipsis before last page if needed
    if (end < lastPage.value - 1) {
      pages.push('...')
    }
    
    // Always show last page
    if (lastPage.value > 1) {
      pages.push(lastPage.value)
    }
  }
  
  return pages
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
    <div class="mt-6">
      <!-- Header with New Project Button aligned right -->
      <div class="flex justify-end items-center mb-8">
        <Link
          v-if="userCan('create projects')"
          href="/projects/create"
          class="flex items-center gap-2 bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2 rounded-lg hover:from-teal-700 hover:to-emerald-700 transition-all duration-200"
        >
          <PlusCircle class="w-5 h-5" /> New Project
        </Link>
      </div>

      <!-- Search Status Bar -->
      <div v-if="searchActive" class="mb-6 p-4 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-lg border border-teal-100 dark:border-teal-800/30">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-teal-800 dark:text-teal-300">
              🔍 Searching for: "<span class="font-bold">{{ currentSearchQuery }}</span>"
            </span>
            <span class="text-xs text-teal-600 dark:text-teal-400">
              ({{ filteredProjects.length }} of {{ props.projects?.total || 0 }} projects)
            </span>
          </div>
          <button
            @click="clearSearchFilter"
            class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 underline"
          >
            Clear search
          </button>
        </div>
      </div>

      <!-- Projects Grid - Always 2 rows × 3 columns = 6 cards -->
      <div v-if="filteredProjects.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 min-h-[600px]">
        <div
          v-for="project in filteredProjects"
          :key="project.id"
          class="rounded-xl shadow hover:shadow-lg transition-all duration-300 p-5 flex flex-col justify-between cursor-pointer border-2 relative transform hover:-translate-y-1"
          :class="[getStatusColor(project).bg, getStatusColor(project).border]"
          @click="router.visit(`/projects/${project.id}`)"
        >
          <!-- Auto-completed ribbon -->
          <div 
            v-if="calculateWeightedProgress(project.tasks) >= 100 && getProjectStatus(project) === 'Completed'"
            class="absolute -top-2 -right-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs font-bold px-3 py-1 rounded shadow-lg"
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
                  class="flex items-center gap-1 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm px-3 py-1.5 rounded-md hover:from-green-700 hover:to-emerald-700 transition-all duration-200"
                >
                  Accept
                </button>
                <button
                  @click="declineProject(project.id)"
                  class="flex items-center gap-1 bg-gradient-to-r from-red-600 to-pink-600 text-white text-sm px-3 py-1.5 rounded-md hover:from-red-700 hover:to-pink-700 transition-all duration-200"
                >
                  Decline
                </button>
              </template>

              <!-- Edit button - Only show for non-completed projects if user can edit -->
              <Link
                v-if="userCan('edit projects') && getProjectStatus(project) !== 'Completed'"
                :href="`/projects/${project.id}/edit`"
                class="flex items-center gap-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm px-3 py-1.5 rounded-md hover:from-blue-700 hover:to-indigo-700 transition-all duration-200"
              >
                <Edit class="w-4 h-4" /> Edit
              </Link>

              <!-- For completed projects, show a disabled or different styled edit button -->
              <Link
                v-if="userCan('edit projects') && getProjectStatus(project) === 'Completed'"
                :href="`/projects/${project.id}/edit`"
                class="flex items-center gap-1 bg-gradient-to-r from-gray-400 to-gray-500 text-white text-sm px-3 py-1.5 rounded-md hover:from-gray-500 hover:to-gray-600 transition-all duration-200"
                title="Edit completed project"
              >
                <Edit class="w-4 h-4" /> Review
              </Link>

              <button
                v-if="userCan('delete projects')"
                @click="deleteProject(project.id)"
                class="flex items-center gap-1 bg-gradient-to-r from-red-600 to-pink-600 text-white text-sm px-3 py-1.5 rounded-md hover:from-red-700 hover:to-pink-700 transition-all duration-200"
              >
                <Trash2 class="w-4 h-4" /> Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Controls -->
      <div v-if="props.projects?.last_page > 1 && !searchActive" class="mt-8 flex justify-center items-center space-x-4">
        <!-- Previous Button -->
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="flex items-center gap-1 px-4 py-2 rounded-lg border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <ChevronLeft class="w-4 h-4" /> Previous
        </button>

        <!-- Page Numbers -->
        <div class="flex items-center space-x-2">
          <button
            v-for="page in getPageNumbers()"
            :key="page"
            @click="page !== '...' ? goToPage(page) : null"
            :disabled="page === '...'"
            class="w-10 h-10 flex items-center justify-center rounded-lg transition-all duration-200"
            :class="{
              'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg': page === currentPage,
              'border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20': page !== currentPage && page !== '...',
              'text-gray-400 dark:text-gray-500 cursor-default': page === '...'
            }"
          >
            {{ page }}
          </button>
        </div>

        <!-- Next Button -->
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === lastPage"
          class="flex items-center gap-1 px-4 py-2 rounded-lg border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next <ChevronRight class="w-4 h-4" />
        </button>
      </div>

      <!-- Page Info -->
      <div v-if="props.projects?.total > 0 && !searchActive" class="mt-4 text-center text-sm text-teal-600 dark:text-teal-400">
        Showing {{ props.projects?.from || 0 }} to {{ props.projects?.to || 0 }} of {{ props.projects?.total || 0 }} projects
      </div>

      <!-- No Results Message -->
      <div v-else-if="searchActive && filteredProjects.length === 0" class="text-center py-12">
        <div class="text-gray-400 dark:text-gray-500 text-lg mb-4">
          No projects found matching "{{ currentSearchQuery }}"
        </div>
        <button
          @click="clearSearchFilter"
          class="text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 hover:underline"
        >
          Clear search to show all projects
        </button>
      </div>

      <!-- Original Empty State (when no projects at all) -->
      <div v-else-if="!projects.length && !searchActive" class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 mt-20">
        <ClipboardList class="w-12 h-12 mb-3 text-gray-400" />
        <p>No projects available yet.</p>
        <Link
          v-if="userCan('create projects')"
          href="/projects/create"
          class="mt-4 px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-lg hover:from-teal-700 hover:to-emerald-700 transition-all duration-200"
        >
          Create your first project
        </Link>
      </div>
    </div>
  </Layout>
</template>
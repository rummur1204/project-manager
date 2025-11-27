<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Edit, Trash2, PlusCircle, CheckCircle, ChevronDown, ChevronUp } from 'lucide-vue-next'
import Layout from '../Dashboard/Layout.vue'

const { props } = usePage()

const activities = computed(() => props.activities || [])
const can = computed(() => props.auth?.can || {})
const user = computed(() => props.auth.user)

// Permission helper
const userCan = (perm) => !!can.value[perm]

// Description expansion states
const expandedDescriptions = ref({})

const toggleDescription = (id) => {
  expandedDescriptions.value[id] = !expandedDescriptions.value[id]
}

// Helpers
const isAssignedDeveloper = (activity) =>
  activity.developers?.some(dev => dev.id === user.value.id)

const hasAccepted = (activity) => {
  const dev = activity.developers?.find(d => d.id === user.value.id)
  return dev?.pivot?.accepted ?? false
}

const isCompleted = (activity) => activity.status === 'Completed'

// Determine status
const getActivityStatus = (activity) => {
  if (isCompleted(activity)) return "Completed"
  if (activity.developers?.some(dev => dev.pivot?.accepted)) return "In Progress"
  return "Pending"
}

const getStatusColor = (status) => {
  switch(status) {
    case 'Pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
    case 'In Progress': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'Completed': return 'bg-green-100 text-green-800 border-green-200';
    default: return 'bg-gray-100 text-gray-800 border-gray-200';
  }
}

// Button conditions
const canAcceptActivity = (activity) =>
  isAssignedDeveloper(activity) && !hasAccepted(activity) && !isCompleted(activity)

const canCompleteActivity = (activity) =>
  isAssignedDeveloper(activity) && hasAccepted(activity) && !isCompleted(activity)

// Actions
const acceptActivity = (id) => {
  router.post(`/activities/${id}/accept`, {}, {
    preserveScroll: true,
    preserveState: false,
  })
}

const completeActivity = (id) => {
  router.post(`/activities/${id}/complete`, {}, {
    preserveScroll: true,
    preserveState: false,
  })
}

const deleteActivity = (id) => {
  if (confirm('Are you sure you want to delete this activity?')) {
    router.delete(`/activities/${id}`,{ preserveState: false,})
  }
}
</script>

<template>
  <Layout>
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Activities</h1>
      <Link
        v-if="userCan('create activities')"
        href="/activities/create"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition"
      >
        <PlusCircle class="w-5 h-5" /> New Activity
      </Link>
    </div>

    <!-- Activities Grid -->
    <div v-if="activities.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition p-5 flex flex-col justify-between relative"
      >
        <!-- Main Content -->
        <div class="flex-1">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 truncate">
            {{ activity.title }}
          </h2>
          
          <div class="flex items-center justify-between mt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Type: <span class="font-medium">{{ activity.type?.name || '-' }}</span>
            </p>
            <span 
              :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(getActivityStatus(activity))]"
            >
              {{ getActivityStatus(activity) }}
            </span>
          </div>

          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Project: <span class="font-medium">{{ activity.project?.title || 'No Project' }}</span>
          </p>

          <p class="text-xs text-gray-400 mt-1">
            Due: {{ activity.due_date || 'N/A' }}
          </p>

          <!-- Description Section -->
          <div class="mt-3">
            <div class="flex items-center justify-between mb-1">
              <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300">Description:</h4>

              <button 
                v-if="activity.description && activity.description.length > 120"
                @click="toggleDescription(activity.id)"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1"
              >
                {{ expandedDescriptions[activity.id] ? 'Show Less' : 'View More' }}
                <component 
                  :is="expandedDescriptions[activity.id] ? ChevronUp : ChevronDown" 
                  class="w-3 h-3" 
                />
              </button>
            </div>

            <!-- Smooth expanding description -->
            <div
              class="overflow-hidden transition-all duration-300"
              :style="expandedDescriptions[activity.id] ? 'max-height: 600px;' : 'max-height: 70px;'"
            >
              <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">
                {{ activity.description || 'No description provided.' }}
              </p>
            </div>
          </div>

          <!-- Developers -->
          <div class="mt-3">
            <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Developers:</h4>

            <div class="flex flex-wrap gap-1">
              <span
                v-for="developer in activity.developers"
                :key="developer.id"
                class="px-2 py-1 rounded text-xs border"
                :class="developer.id === user.id 
                  ? 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700' 
                  : 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'"
              >
                {{ developer.name }}
                <span v-if="developer.id === user.id" class="ml-1">(You)</span>
                <span v-if="developer.pivot?.accepted" class="ml-1 text-green-600">✓</span>
              </span>

              <span v-if="activity.developers.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
                No developers assigned
              </span>
            </div>
          </div>
        </div>

        <!-- Buttons -->
        <div
          v-if="userCan('edit activities') || userCan('delete activities') || isAssignedDeveloper(activity)"
          class="flex justify-between items-center mt-5 pt-3 border-t border-gray-100 dark:border-gray-700"
        >
          <div class="flex gap-2">
            <button
              v-if="canAcceptActivity(activity)"
              @click="acceptActivity(activity.id)"
              class="flex items-center gap-1 bg-green-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-green-700 transition"
            >
              <CheckCircle class="w-4 h-4" /> Accept
            </button>

            <button
              v-if="canCompleteActivity(activity)"
              @click="completeActivity(activity.id)"
              class="flex items-center gap-1 bg-green-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-green-700 transition"
            >
              <CheckCircle class="w-4 h-4" /> Mark as Done
            </button>

            <Link
              v-if="userCan('edit activities')"
              :href="`/activities/${activity.id}/edit`"
              class="flex items-center gap-1 bg-blue-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-blue-700 transition"
            >
              <Edit class="w-4 h-4" /> Edit
            </Link>

            <button
              v-if="userCan('delete activities')"
              @click="deleteActivity(activity.id)"
              class="flex items-center gap-1 bg-red-600 text-white text-sm px-3 py-1.5 rounded-md hover:bg-red-700 transition"
            >
              <Trash2 class="w-4 h-4" /> Delete
            </button>
          </div>

          <div v-if="isAssignedDeveloper(activity)" class="text-xs text-gray-500 dark:text-gray-400">
            <span v-if="isCompleted(activity)">Completed</span>
            <span v-else-if="hasAccepted(activity)">In Progress</span>
            <span v-else>Pending your acceptance</span>
          </div>
        </div>

        <div 
          v-else
          class="flex justify-between items-center mt-5 pt-3 border-t border-gray-100 dark:border-gray-700"
        >
          <span class="text-xs text-gray-400 dark:text-gray-500">View only</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 mt-20">
      <p>No activities available yet.</p>
      <Link
        v-if="userCan('create activities')"
        href="/activities/create"
        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
      >
        Create your first activity
      </Link>
    </div>
  </Layout>
</template>

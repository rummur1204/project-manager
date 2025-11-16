<script setup>
import { ref, onMounted } from 'vue'
import { Head, usePage, Link } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const { props } = usePage()

// Use real props from backend
const stats = ref(props.stats)
const upcomingDeadlines = ref(props.upcomingDeadlines)
const recentActivity = ref(props.recentActivity)

// Chart.js setup
onMounted(() => {
  const ctx = document.getElementById('taskChart')
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Completed', 'Pending'],
      datasets: [
        {
          label: 'Tasks',
          data: [stats.value.completedTasks, stats.value.tasks - stats.value.completedTasks],
          backgroundColor: ['#4f46e5', '#cbd5e1'],
          borderWidth: 0
        }
      ]
    },
    options: {
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: { color: '#6b7280' }
        }
      }
    }
  })
})
</script>

<template>
  <Layout>
    <Head title="Dashboard" />

    <div class="space-y-8">
      <!-- Header -->
      <h1 class="text-2xl font-semibold">Dashboard Overview</h1>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-sm text-gray-500">Total Projects</h2>
          <p class="text-2xl font-bold mt-2 text-indigo-600">{{ stats.projects }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-sm text-gray-500">Total Tasks</h2>
          <p class="text-2xl font-bold mt-2 text-indigo-600">{{ stats.tasks }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-sm text-gray-500">Completed Tasks</h2>
          <p class="text-2xl font-bold mt-2 text-green-500">{{ stats.completedTasks }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-sm text-gray-500">Active Users</h2>
          <p class="text-2xl font-bold mt-2 text-blue-500">{{ stats.users }}</p>
        </div>
      </div>

      <!-- Chart + Upcoming deadlines -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-lg font-semibold mb-4">Task Completion Overview</h2>
          <canvas id="taskChart" height="140"></canvas>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
          <h2 class="text-lg font-semibold mb-4">Upcoming Deadlines</h2>
          <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            <li v-for="project in upcomingDeadlines" :key="project.title" class="py-3">
              <div class="flex items-center justify-between">
                <span>{{ project.title }}</span>
                <span class="text-sm text-gray-500">{{ project.due_date }}</span>
              </div>
            </li>
          </ul>
          <div class="mt-4 text-right">
            <Link href="/calendar" class="text-indigo-600 hover:underline text-sm">
              View Full Calendar →
            </Link>
          </div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold mb-4">Recent Activity</h2>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="activity in recentActivity" :key="activity.time" class="py-3 flex justify-between">
            <div>
              <span class="font-semibold text-indigo-600">{{ activity.user }}</span>
              <span class="ml-2">{{ activity.action }}</span>
            </div>
            <span class="text-sm text-gray-500">{{ activity.time }}</span>
          </li>
        </ul>
      </div>
    </div>
  </Layout>
</template>

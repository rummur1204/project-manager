<script setup>
import { ref, onMounted } from 'vue'
import { Head, usePage, Link } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const { props } = usePage()

const stats = ref(props.stats)
const upcomingDeadlines = ref(props.upcomingDeadlines)
const projectAnalytics = ref(props.projectAnalytics || [])
const userName = ref(props.userName || 'User')
const canViewAllProjects = ref(props.canViewAllProjects || false) // Add permission check

// Initialize charts
onMounted(() => {
  // Task Distribution Chart
  const taskCtx = document.getElementById('taskChart')
  if (taskCtx) {
    const completed = stats.value.completedTasks || 0
    const inProgress = stats.value.inProgressTasks || 0
    const pending = stats.value.pendingTasks || 0
    
    new Chart(taskCtx, {
      type: 'doughnut',
      data: {
        labels: ['Completed', 'In Progress', 'Pending'],
        datasets: [
          {
            label: 'Tasks',
            data: [completed, inProgress, pending],
            backgroundColor: ['#10b981', '#3b82f6', '#cbd5e1'],
            borderWidth: 0,
            hoverOffset: 15
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: { 
              color: '#6b7280',
              padding: 20,
              font: {
                size: 12
              },
              usePointStyle: true
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || ''
                const value = context.raw || 0
                const total = completed + inProgress + pending
                const percentage = total > 0 ? Math.round((value / total) * 100) : 0
                return `${label}: ${value} (${percentage}%)`
              }
            }
          }
        },
        cutout: '70%'
      }
    })
  }

  // Project Progress Bar Chart
  const projectCtx = document.getElementById('projectProgressChart')
  if (projectCtx && projectAnalytics.value.length > 0) {
    new Chart(projectCtx, {
      type: 'bar',
      data: {
        labels: projectAnalytics.value.map(p => {
          return p.title.length > 20 ? p.title.substring(0, 20) + '...' : p.title
        }),
        datasets: [
          {
            label: 'Progress %',
            data: projectAnalytics.value.map(p => p.progress || 0),
            backgroundColor: projectAnalytics.value.map(p => {
              if (p.progress >= 80) return '#10b981'
              if (p.progress >= 50) return '#f59e0b'
              return '#ef4444'
            }),
            borderRadius: 6,
            borderSkipped: false,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: value => value + '%',
              color: '#6b7280',
              font: {
                size: 11
              },
              stepSize: 25
            },
            grid: {
              color: 'rgba(0,0,0,0.05)'
            }
          },
          x: {
            ticks: {
              color: '#6b7280',
              maxRotation: 45,
              minRotation: 45,
              font: {
                size: 11
              }
            },
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const project = projectAnalytics.value[context.dataIndex]
                return [
                  `Progress: ${project.progress}%`,
                  `Tasks: ${project.completed_tasks}/${project.total_tasks}`,
                  `Status: ${project.status || 'Not started'}`
                ]
              }
            }
          }
        }
      }
    })
  }
})
</script>
<template>
  <Layout>
    <Head title="Dashboard" />

    <div class="mt-6">
    <div class="space-y-6">
      <!-- Personalized Header -->
      <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          Welcome back, <span class="text-teal-600 dark:text-teal-400">{{ userName }}</span>! 👋
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Here's your project performance overview</p>
      </div>

      <!-- Stats cards with improved UI -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Projects Card -->
        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/20 dark:to-gray-800 border border-indigo-100 dark:border-indigo-800 shadow-lg rounded-xl p-5 hover:shadow-xl transition-shadow duration-300">
          <div class="flex items-center">
            <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-xl mr-4">
              <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Projects</h2>
              <p class="text-3xl font-bold mt-1 text-indigo-700 dark:text-indigo-300">{{ stats.projects }}</p>
            </div>
          </div>
          <div class="mt-4 text-xs text-indigo-600 dark:text-indigo-400">
            <span class="inline-flex items-center">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
              </svg>
              Active portfolio
            </span>
          </div>
        </div>

        <!-- Tasks Card -->
        <div class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-800 border border-blue-100 dark:border-blue-800 shadow-lg rounded-xl p-5 hover:shadow-xl transition-shadow duration-300">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-xl mr-4">
              <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tasks</h2>
              <p class="text-3xl font-bold mt-1 text-blue-700 dark:text-blue-300">{{ stats.tasks }}</p>
            </div>
          </div>
          <div class="mt-4 text-xs text-blue-600 dark:text-blue-400">
            <span class="inline-flex items-center">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              All assignments
            </span>
          </div>
        </div>

        <!-- Completed Tasks Card -->
        <div class="bg-gradient-to-br from-green-50 to-white dark:from-green-900/20 dark:to-gray-800 border border-green-100 dark:border-green-800 shadow-lg rounded-xl p-5 hover:shadow-xl transition-shadow duration-300">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-xl mr-4">
              <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed Tasks</h2>
              <p class="text-3xl font-bold mt-1 text-green-700 dark:text-green-300">{{ stats.completedTasks }}</p>
            </div>
          </div>
          <div class="mt-4 text-xs text-green-600 dark:text-green-400">
            <span class="inline-flex items-center">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              {{ stats.tasks > 0 ? Math.round((stats.completedTasks / stats.tasks) * 100) : 0 }}% completion rate
            </span>
          </div>
        </div>
      </div>

      <!-- Chart + Project Progress -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Task Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
              </svg>
              Task Status Distribution
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ stats.tasks }} total tasks
            </span>
          </div>
          <div class="relative" style="height: 300px">
            <canvas id="taskChart"></canvas>
          </div>
        </div>

        <!-- Project Progress Chart -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Top Projects Progress
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              Top 5 by progress
            </span>
          </div>
          <div class="relative" style="height: 300px">
            <canvas id="projectProgressChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Permission-based Upcoming Deadlines Section -->
      <template v-if="canViewAllProjects">
        <!-- Admin/Full Access View -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              All Projects Upcoming Deadlines
            </h2>
            <div class="flex items-center space-x-4">
              <span class="px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                Admin View
              </span>
              <Link href="/projects" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors flex items-center">
                Manage All Projects
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </Link>
            </div>
          </div>
          
          <div v-if="upcomingDeadlines.length > 0">
            <div v-for="project in upcomingDeadlines" :key="project.id" 
                 class="mb-4 p-4 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-500 transition-all duration-300 last:mb-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-start justify-between">
                    <Link :href="`/projects/${project.id}`" 
                          class="text-lg font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                      {{ project.title }}
                    </Link>
                    <div class="flex items-center space-x-2 ml-4">
                      <span class="px-3 py-1 text-xs font-medium rounded-full"
                            :class="{
                              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': project.status === 'completed',
                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': project.status === 'in_progress',
                              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': project.status === 'pending',
                              'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !project.status
                            }">
                        {{ project.status ? project.status.replace('_', ' ') : 'Not Started' }}
                      </span>
                      <span v-if="project.client_name" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded">
                        Client: {{ project.client_name }}
                      </span>
                    </div>
                  </div>
                  
                  <div class="flex items-center mt-3 text-sm">
                    <div class="flex items-center text-gray-600 dark:text-gray-400 mr-4">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      {{ project.due_date }}
                    </div>
                    <div class="flex items-center" :class="project.days_left >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ project.days_left >= 0 ? project.days_left + ' days left' : Math.abs(project.days_left) + ' days overdue' }}
                    </div>
                  </div>
                  
                  <!-- Progress Bar -->
                  <div class="mt-4">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Project Progress</span>
                      <span class="text-lg font-bold" 
                            :class="{
                              'text-green-600 dark:text-green-400': project.progress >= 80,
                              'text-yellow-600 dark:text-yellow-400': project.progress >= 50 && project.progress < 80,
                              'text-red-600 dark:text-red-400': project.progress < 50
                            }">
                        {{ project.progress }}%
                      </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                      <div 
                        class="h-3 rounded-full transition-all duration-500"
                        :class="{
                          'bg-gradient-to-r from-green-500 to-green-600': project.progress >= 80,
                          'bg-gradient-to-r from-yellow-500 to-yellow-600': project.progress >= 50 && project.progress < 80,
                          'bg-gradient-to-r from-red-500 to-red-600': project.progress < 50
                        }"
                        :style="{ width: project.progress + '%' }">
                      </div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                      <span>{{ project.completed_tasks || 0 }} of {{ project.total_tasks || 0 }} tasks completed</span>
                      <span v-if="project.total_tasks > 0">
                        {{ Math.round((project.completed_tasks / project.total_tasks) * 100) }}% task completion
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
              <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Upcoming Deadlines</h3>
            <p class="text-gray-500 dark:text-gray-400">No projects have deadlines within the next 7 days.</p>
          </div>
        </div>
      </template>

      <template v-else>
        <!-- Regular User View -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              My Upcoming Deadlines
            </h2>
            <Link href="/projects" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors flex items-center">
              View My Projects
              <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </Link>
          </div>
          
          <div v-if="upcomingDeadlines.length > 0">
            <div v-for="project in upcomingDeadlines" :key="project.id" 
                 class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-white dark:from-blue-900/10 dark:to-gray-700 border border-blue-100 dark:border-blue-800 rounded-xl hover:border-blue-300 dark:hover:border-blue-500 transition-all duration-300 last:mb-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-start justify-between">
                    <Link :href="`/projects/${project.id}`" 
                          class="text-lg font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                      {{ project.title }}
                    </Link>
                    <span class="px-3 py-1 text-xs font-medium rounded-full ml-4"
                          :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': project.status === 'completed',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': project.status === 'in_progress',
                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': project.status === 'pending',
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !project.status
                          }">
                      {{ project.status ? project.status.replace('_', ' ') : 'Not Started' }}
                    </span>
                  </div>
                  
                  <div class="flex items-center mt-3 text-sm">
                    <div class="flex items-center text-gray-600 dark:text-gray-400 mr-4">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      {{ project.due_date }}
                    </div>
                    <div class="flex items-center" :class="project.days_left >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ project.days_left >= 0 ? project.days_left + ' days left' : Math.abs(project.days_left) + ' days overdue' }}
                    </div>
                  </div>
                  
                  <!-- Progress Bar -->
                  <div class="mt-4">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">My Project Progress</span>
                      <span class="text-lg font-bold" 
                            :class="{
                              'text-green-600 dark:text-green-400': project.progress >= 80,
                              'text-yellow-600 dark:text-yellow-400': project.progress >= 50 && project.progress < 80,
                              'text-red-600 dark:text-red-400': project.progress < 50
                            }">
                        {{ project.progress }}%
                      </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                      <div 
                        class="h-3 rounded-full transition-all duration-500"
                        :class="{
                          'bg-gradient-to-r from-green-500 to-green-600': project.progress >= 80,
                          'bg-gradient-to-r from-yellow-500 to-yellow-600': project.progress >= 50 && project.progress < 80,
                          'bg-gradient-to-r from-red-500 to-red-600': project.progress < 50
                        }"
                        :style="{ width: project.progress + '%' }">
                      </div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                      <span>{{ project.completed_tasks || 0 }} of {{ project.total_tasks || 0 }} tasks completed</span>
                      <span v-if="project.total_tasks > 0">
                        {{ Math.round((project.completed_tasks / project.total_tasks) * 100) }}% task completion
                      </span>
                    </div>
                  </div>
                  
                  <!-- My Role in this Project -->
                  <div v-if="project.my_role" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <span class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      My Role: <span class="font-medium ml-1">{{ project.my_role }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 mb-4">
              <svg class="w-8 h-8 text-blue-400 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Upcoming Deadlines</h3>
            <p class="text-gray-500 dark:text-gray-400">You don't have any projects with upcoming deadlines.</p>
            <Link href="/projects" class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
              Browse available projects
              <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </Link>
          </div>
        </div>
      </template>
    </div>
    </div>
  </Layout>
</template>


<script setup>
import { Link } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue';

const props = defineProps({
  project: Object
})
</script>

<template>
    <Layout>
  <div class="min-h-screen bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold">{{ project.title }}</h2>
        <Link href="/projects" class="text-indigo-600">← Back</Link>
      </div>

      <p class="text-gray-600 mb-4">{{ project.description }}</p>

      <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 mb-6">
        <p><strong>Status:</strong> {{ project.status }}</p>
        <p><strong>Due Date:</strong> {{ project.due_date }}</p>
        <p><strong>Client:</strong> {{ project.client?.name }}</p>
        <p><strong>Progress:</strong> {{ project.progress }}%</p>
      </div>

      <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6">
        <div class="h-2.5 rounded-full bg-indigo-600" :style="{ width: `${project.progress}%` }"></div>
      </div>

      <h3 class="text-lg font-semibold mb-2">Developers</h3>
      <ul class="list-disc pl-5 mb-6">
        <li v-for="dev in project.developers" :key="dev.id">{{ dev.name }}</li>
      </ul>

      <h3 class="text-lg font-semibold mb-2">Tasks</h3>
      <div v-if="project.tasks.length">
        <ul class="divide-y">
          <li v-for="task in project.tasks" :key="task.id" class="py-3 flex justify-between items-center">
            <div>
              <p class="font-medium">{{ task.title }}</p>
              <p class="text-xs text-gray-500">{{ task.task_type }}</p>
            </div>
            <span class="text-sm text-gray-600">{{ task.status }}</span>
          </li>
        </ul>
      </div>
      <div v-else class="text-gray-400">No tasks yet.</div>
    </div>
  </div>
  </Layout>
</template>

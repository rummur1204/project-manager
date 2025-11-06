<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

const projects = ref([
  {
    id: 1,
    title: 'Website Redesign',
    description: 'Full revamp of the client landing page.',
    progress: 70,
  },
  {
    id: 2,
    title: 'Mobile App',
    description: 'Cross-platform app for e-commerce client.',
    progress: 45,
  },
])
</script>

<template>
  <AdminLayout>
   
     <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Projects</h1>
      <button
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        + New Project
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="project in projects"
        :key="project.id"
        class="bg-white rounded-xl shadow p-5 hover:shadow-lg transition"
      >
        <h2 class="text-lg font-semibold mb-1">{{ project.title }}</h2>
        <p class="text-gray-600 mb-3">{{ project.description }}</p>

        <div class="w-full bg-gray-200 h-3 rounded-lg overflow-hidden mb-3">
          <div
            class="bg-blue-600 h-3"
            :style="{ width: project.progress + '%' }"
          ></div>
        </div>

        <button class="text-blue-600 hover:underline text-sm">View Tasks</button>

        <div class="mt-4 flex justify-between text-sm">
          <button class="text-yellow-600 hover:underline">Edit</button>
          <button class="text-red-600 hover:underline">Delete</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  projects: Array
})
</script>

<template>
  <div class="p-8">
    <h1 class="text-3xl font-bold mb-4">Projects</h1>

    <div v-if="projects.length" class="grid md:grid-cols-2 gap-4">
      <div
        v-for="project in projects"
        :key="project.id"
        class="border rounded-lg p-4 shadow hover:shadow-md transition"
      >
        <h2 class="text-xl font-semibold">{{ project.title }}</h2>
        <p class="text-gray-600">{{ project.description }}</p>
        <p class="text-sm mt-2 text-gray-500">Progress: {{ project.progress }}%</p>
      </div>
    </div>

    <p v-else class="text-gray-500">No projects found.</p>
  </div>
</template>

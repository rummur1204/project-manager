<script setup>
import Layout from './Layout.vue'
import { usePage } from '@inertiajs/vue3'

const { props } = usePage()
const user = props.auth.user
const projects = props.projects || []
</script>

<template>
  <Layout>
    <div>
      <h1 class="text-2xl font-bold text-gray-800 mb-6">
        Welcome back, {{ user.name }}!
      </h1>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="project in projects" :key="project.id" class="bg-white shadow rounded-lg p-5">
          <h2 class="text-lg font-semibold text-gray-700">{{ project.title }}</h2>
          <p class="text-gray-500 text-sm mt-1">{{ project.description }}</p>
          <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
              <span>Status: {{ project.status }}</span>
              <span>{{ project.progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full" :style="{ width: project.progress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { PlusCircle, Edit, Trash2, ClipboardList } from 'lucide-vue-next'
import Layout from '../Dashboard/Layout.vue'

const { props } = usePage()
const projects = computed(() => props.projects || [])
const can = computed(() => props.auth?.can || {}) // Permissions from Laravel
</script>

<template>
  <Layout>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Projects</h1>
      <Link
        v-if="can['create projects']"
        href="/projects/create"
        class="flex items-center gap-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
      >
        <PlusCircle class="w-5 h-5" /> New Project
      </Link>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <div
        v-for="project in projects"
        :key="project.id"
        @click="router.visit(`/projects/${project.id}`)"
        class="bg-white shadow rounded-lg p-4 cursor-pointer hover:shadow-lg transition"
      >
        <h2 class="text-lg font-semibold text-gray-800">{{ project.title }}</h2>
        <p class="text-sm text-gray-500 mb-2">{{ project.status }}</p>
        <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
          <div
            class="bg-blue-600 h-2 rounded-full"
            :style="{ width: project.progress + '%' }"
          ></div>
        </div>

        <div class="flex justify-between items-center mt-3">
          <Link
            v-if="can['edit projects']"
            :href="`/projects/${project.id}/edit`"
            class="text-blue-600 hover:underline text-sm flex items-center gap-1"
            @click.stop
          >
            <Edit class="w-4 h-4" /> Edit
          </Link>

          <Link
            v-if="can['delete projects']"
            as="button"
            method="delete"
            :href="`/projects/${project.id}`"
            class="text-red-600 hover:underline text-sm flex items-center gap-1"
            @click.stop
          >
            <Trash2 class="w-4 h-4" /> Delete
          </Link>

          <Link 
          
            class="text-green-600 hover:underline text-sm flex items-center gap-1"
            :href="`/projects/${project.id}/tasks`"
            @click.stop
          >
            <ClipboardList class="w-4 h-4" /> View Tasks
          </Link>
        </div>
      </div>
    </div>
  </div>
  </Layout>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'


const form = useForm({
  name: ''
})
</script>

<template>
    <Layout>
  <div class="max-w-md">
    <h2 class="text-xl font-semibold mb-4">New Activity Type</h2>

    <form @submit.prevent="form.post('/settings/activity-types')">
      <input
        v-model="form.name"
        type="text"
        class="border p-2 w-full mb-3"
        placeholder="Activity Type Name"
      />

      <span class="text-red-600" v-if="form.errors.name">
        {{ form.errors.name }}
      </span>

      <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
        Save
      </button>
    </form>
  </div>
  </Layout>
</template>

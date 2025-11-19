<script setup>
import { router } from '@inertiajs/vue3'

defineProps({
  items: Array
})
</script>

<template>
  <div>
    <div class="flex justify-between mb-4">
      <h2 class="text-xl font-semibold">Activity Types</h2>

      <button
        @click="router.visit('/settings/activity-types/create')"
        class="bg-blue-600 text-white px-4 py-2 rounded"
      >New Type</button>
    </div>

    <table class="w-full">
      <thead>
        <tr class="border-b">
          <th class="text-left p-2">Name</th>
          <th class="text-left p-2">Actions</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="a in items" :key="a.id" class="border-b">
          <td class="p-2">{{ a.name }}</td>
          <td class="p-2">
            <button @click="router.visit(`/settings/activity-types/${a.id}/edit`)"
              class="text-blue-600 mr-3">Edit</button>

            <button @click="router.delete(`/settings/activity-types/${a.id}`)"
              class="text-red-600">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

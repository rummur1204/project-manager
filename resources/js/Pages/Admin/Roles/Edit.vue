<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'

const props = defineProps({ role: Object, permissions: Array })
const form = useForm({ name: props.role.name, permissions: props.role.permissions.map(p => p.name) })
const submit = () => form.put(`/admin/roles/${props.role.id}`)
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-100">Edit Role: {{ form.name }}</h1>

      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6 transition-colors max-w-lg">
        <div>
          <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">Role Name</label>
          <input v-model="form.name" type="text" placeholder="Enter role name"
            class="w-full border rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 focus:ring focus:ring-blue-200" />
          <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
          <label class="block font-semibold mb-2 text-gray-700 dark:text-gray-300">Assigned Permissions</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <label v-for="permission in permissions" :key="permission.id" class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700 p-2 rounded-lg">
              <input type="checkbox" :value="permission.name" v-model="form.permissions" />
              <span class="text-gray-800 dark:text-gray-100">{{ permission.name }}</span>
            </label>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-4">
          <Link href="/admin/roles" class="text-gray-600 dark:text-gray-300 hover:underline">Cancel</Link>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Update Role</button>
        </div>
      </form>
    </main>
  </Layout>
</template>

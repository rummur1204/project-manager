<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'

const props = defineProps({
  role: Object,
  permissions: Array
})

const form = useForm({
  name: props.role.name,
  permissions: props.role.permissions.map(p => p.name)
})

const submit = () => {
  form.put(`/admin/roles/${props.role.id}`)
}
</script>
<template>
  <Layout>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-6">Edit Role: {{ form.name }}</h1>

    <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-6">
      <!-- Role Name -->
      <div>
        <label class="block font-semibold mb-2">Role Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="Enter role name"
          class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200"
        />
        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
          {{ form.errors.name }}
        </div>
      </div>

      <!-- Permissions -->
      <div>
        <label class="block font-semibold mb-2">Assigned Permissions</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <label
            v-for="permission in permissions"
            :key="permission.id"
            class="flex items-center space-x-2 bg-gray-50 p-2 rounded-lg"
          >
            <input
              type="checkbox"
              :value="permission.name"
              v-model="form.permissions"
            />
            <span>{{ permission.name }}</span>
          </label>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex items-center justify-end space-x-4">
        <Link
          href="/admin/roles"
          class="text-gray-600 hover:underline"
        >
          Cancel
        </Link>
        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          Update Role
        </button>
      </div>
    </form>
  </div>
  </Layout>
</template>



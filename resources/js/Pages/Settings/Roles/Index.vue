<script setup>
import { Link } from '@inertiajs/vue3'
// import Layout from '@/Pages/Dashboard/Layout.vue'

defineProps({ roles: Array })
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="flex justify-between items-center mb-4">
        <!-- <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Roles</h1> -->
        <Link href="/settings/roles/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">+ New Role</Link>
      </div>

      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow transition-colors">
        <table class="w-full border">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="p-2 text-left text-gray-800 dark:text-gray-100">Name</th>
              <th class="p-2 text-left text-gray-800 dark:text-gray-100">Permissions</th>
              <th class="p-2 text-right text-gray-800 dark:text-gray-100">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in roles" :key="role.id" class="border-t">
              <td class="p-2 text-gray-800 dark:text-gray-100">{{ role.name }}</td>
              <td class="p-2">
                <span v-for="p in role.permissions" :key="p.id" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-2 py-1 rounded text-sm mr-1">
                  {{ p.name }}
                </span>
              </td>
              <td class="p-2 text-right">
                <Link :href="`/settings/roles/${role.id}/edit`" class="text-blue-600 dark:text-blue-400 mr-2">Edit</Link>
                <Link :href="`/settings/roles/${role.id}`" method="delete" as="button" class="text-red-600 dark:text-red-400">Delete</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </Layout>
</template>

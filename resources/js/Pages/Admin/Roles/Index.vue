<script setup>
import { Link } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'

defineProps({ roles: Array })
</script>
<template>
  <Layout>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Roles</h1>
    <Link href="/admin/roles/create" class="bg-blue-600 text-white px-4 py-2 rounded">+ New Role</Link>

    <table class="w-full mt-6 border">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left">Name</th>
          <th class="p-2 text-left">Permissions</th>
          <th class="p-2 text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="role in roles" :key="role.id" class="border-t">
          <td class="p-2">{{ role.name }}</td>
          <td class="p-2">
            <span v-for="p in role.permissions" :key="p.id" class="bg-gray-200 px-2 py-1 rounded text-sm mr-1">
              {{ p.name }}
            </span>
          </td>
          <td class="p-2 text-right">
            <Link :href="`/admin/roles/${role.id}/edit`" class="text-blue-600 mr-2">Edit</Link>
            <Link :href="`/admin/roles/${role.id}`" method="delete" as="button" class="text-red-600">Delete</Link>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </Layout>
</template>



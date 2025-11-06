<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
const page = usePage()
const user = computed(() => page.props.auth.user || null)
const roles = computed(() => page.props.auth.roles || [])
const permissions = computed(() => page.props.auth.permissions || [])
const props = defineProps({ test: String })
console.log(props.test)
const hasRole = (r) => roles.value.includes(r)
const hasPermission = (p) => permissions.value.includes(p)
</script>

<template>
  <AuthenticatedLayout>
    <template #page-title>
      <h1 class="text-2xl font-bold">Dashboard</h1>
    </template>

    <div>
      <p class="mb-4">Welcome back, {{ user?.name }}!</p>

      <div class="grid grid-cols-3 gap-4">
        <div class="p-4 border rounded">
          <h3 class="font-semibold">Projects</h3>
          <p v-if="hasPermission('view projects')">You can view projects</p>
          <p v-else>You don't have access to projects.</p>
        </div>

        <div class="p-4 border rounded">
          <h3 class="font-semibold">Users</h3>
          <p v-if="hasPermission('view users') || hasRole('Super Admin')">Manage users</p>
          <p v-else>Restricted</p>
        </div>

        <div class="p-4 border rounded">
          <h3 class="font-semibold">Roles</h3>
          <p v-if="hasPermission('view roles') || hasRole('Super Admin')">Manage roles</p>
          <p v-else>Restricted</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


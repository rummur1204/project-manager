<script setup>
import { ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'
import { Users, Shield, Sparkles } from 'lucide-vue-next'

// Local tab state (NO backend reload)
const tab = ref('users')

// Tab switch function
const switchTab = (name) => {
  tab.value = name
}

// Props from backend
const page = usePage()
const users = page.props.users
const roles = page.props.roles
const activityTypes = page.props.activityTypes
</script>

<template>
  <Layout>
    <div class="max-w-6xl mx-auto">
      <h1 class="text-2xl font-semibold mb-6">Settings</h1>

      <!-- Tabs -->
      <div class="flex border-b border-gray-300 dark:border-gray-700 mb-6">
        <button
          @click="switchTab('users')"
          :class="[
            'px-6 py-3 -mb-px border-b-2',
            tab === 'users'
              ? 'border-indigo-600 text-indigo-600 font-semibold'
              : 'border-transparent text-gray-600 dark:text-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Users class="w-4 h-4" /> Users
          </div>
        </button>

        <button
          @click="switchTab('roles')"
          :class="[
            'px-6 py-3 -mb-px border-b-2',
            tab === 'roles'
              ? 'border-indigo-600 text-indigo-600 font-semibold'
              : 'border-transparent text-gray-600 dark:text-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Shield class="w-4 h-4" /> Roles
          </div>
        </button>

        <button
          @click="switchTab('activitytypes')"
          :class="[
            'px-6 py-3 -mb-px border-b-2',
            tab === 'activitytypes'
              ? 'border-indigo-600 text-indigo-600 font-semibold'
              : 'border-transparent text-gray-600 dark:text-gray-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <Sparkles class="w-4 h-4" /> Activity Types
          </div>
        </button>
      </div>

      <!-- USERS TAB -->
      <div v-if="tab === 'users'" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div class="flex justify-between mb-4">
          <h2 class="text-lg font-semibold">Manage Users</h2>

          <Link
            href="/settings/users/create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            + New User
          </Link>
        </div>

        <table class="w-full">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="p-2 text-left">Name</th>
              <th class="p-2 text-left">Email</th>
              <th class="p-2 text-left">Role</th>
              <th class="p-2 text-left">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="u in users"
              :key="u.id"
              class="border-b hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="p-2">{{ u.name }}</td>
              <td class="p-2">{{ u.email }}</td>
              <td class="p-2">
                <span v-if="u.roles.length">{{ u.roles[0].name }}</span>
                <span v-else class="text-gray-400">No Role</span>
              </td>
              <td class="p-2 space-x-3">
                <Link
                  :href="`/settings/users/${u.id}/edit`"
                  class="text-blue-600 hover:underline"
                >
                  Edit
                </Link>

                <button
                  @click="router.delete(`/settings/users/${u.id}`)"
                  class="text-red-600 hover:underline"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ROLES TAB -->
      <div v-if="tab === 'roles'" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div class="flex justify-between mb-4">
          <h2 class="text-lg font-semibold">Manage Roles</h2>

          <Link
            href="/settings/roles/create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            + New Role
          </Link>
        </div>

        <table class="w-full">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="p-2 text-left">Role Name</th>
              <th class="p-2 text-left">Permissions</th>
              <th class="p-2 text-left">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="role in roles"
              :key="role.id"
              class="border-b hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="p-2">{{ role.name }}</td>

              <td class="p-2 text-sm">
                <span v-if="role.permissions.length">
                  {{ role.permissions.map(p => p.name).join(', ') }}
                </span>
                <span v-else class="text-gray-400">No permissions</span>
              </td>

              <td class="p-2 space-x-3">
                <Link
                  :href="`/settings/roles/${role.id}/edit`"
                  class="text-blue-600 hover:underline"
                >
                  Edit
                </Link>

                <button
                  @click="router.delete(`/settings/roles/${role.id}`)"
                  class="text-red-600 hover:underline"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ACTIVITY TYPES TAB -->
      <div v-if="tab === 'activitytypes'" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div class="flex justify-between mb-4">
          <h2 class="text-lg font-semibold">Activity Types</h2>

          <Link
            href="/settings/activity-types/create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            + New Activity Type
          </Link>
        </div>

        <table class="w-full">
          <thead>
            <tr class="border-b dark:border-gray-700">
              <th class="p-2 text-left">Name</th>
              <th class="p-2 text-left">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="at in activityTypes"
              :key="at.id"
              class="border-b hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="p-2">{{ at.name }}</td>

              <td class="p-2 space-x-3">
                <Link
                  :href="`/settings/activity-types/${at.id}/edit`"
                  class="text-blue-600 hover:underline"
                >
                  Edit
                </Link>

                <button
                  @click="router.delete(`/settings/activity-types/${at.id}`)"
                  class="text-red-600 hover:underline"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </Layout>
</template>

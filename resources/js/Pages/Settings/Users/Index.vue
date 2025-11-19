<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
// import Layout from '@/Pages/Dashboard/Layout.vue'
import { PlusCircle, Edit, Trash2 } from 'lucide-vue-next'

const page = usePage()
const users = computed(() => page.props.users || [])
const search = ref('')



const filteredUsers = computed(() => {
  if (!search.value) return users.value
  return users.value.filter(u =>
    u.name.toLowerCase().includes(search.value.toLowerCase()) ||
    u.email.toLowerCase().includes(search.value.toLowerCase()) ||
    u.role.toLowerCase().includes(search.value.toLowerCase())
  )
})

const logout = () => router.post('/logout')
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="flex justify-between items-center mb-4">
        <!-- <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Users</h1> -->
        <button
  @click="router.visit('/settings/users/create?tab=users')"
  class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
>
  <PlusCircle class="w-5 h-5" /> New User
</button>

      </div>

      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow transition-colors">
        <table class="min-w-full">
          <thead>
            <tr class="border-b bg-gray-50 dark:bg-gray-700">
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Role</th>
              <th class="text-left px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="user in filteredUsers"
              :key="user.id"
              class="border-t hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ user.name }}</td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ user.email }}</td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ user.role }}</td>
              <td class="px-4 py-2 space-x-2">
               <button
  @click="router.visit(`/settings/users/${user.id}/edit?tab=users`)"
  class="text-blue-600 hover:underline"
>
  <Edit class="w-4 h-4" />
</button>

                <button
  @click="router.delete(`/settings/users/${user.id}`, { preserveScroll: true, onSuccess: () => router.visit('/settings?tab=users') })"
  class="text-red-600 hover:underline"
>
  <Trash2 class="w-4 h-4" />
</button>

              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="users.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
          No users found.
        </div>
      </div>
    </main>
  </Layout>
</template>

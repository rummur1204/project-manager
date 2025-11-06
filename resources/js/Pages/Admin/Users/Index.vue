<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'
import { PlusCircle, Edit, Trash2, Search, User } from 'lucide-vue-next'

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
  <div class="flex h-screen bg-gray-100">
    <!-- SIDEBAR -->
    <!-- <aside class="w-64 bg-white shadow-lg">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-gray-800">Super Admin</h2>
      </div>
      <nav class="p-4 space-y-2">
        <a href="/dashboard" class="block px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700">Dashboard</a>
        <a href="/projects" class="block px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700">Projects</a>
        <a href="/admin/users" class="block px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-semibold">Users</a>
        <a href="/profile" class="block px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700">Profile</a>
        <button @click="logout" class="w-full text-left px-4 py-2 rounded-lg hover:bg-blue-50 text-red-600">Logout</button>
      </nav>
    </aside> -->

    <!-- MAIN -->
    <div class="flex-1 flex flex-col">
      <!-- TOP NAV -->
      <header class="bg-white shadow p-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Users</h1>
        <div class="flex items-center gap-4">
          <div class="relative">
            <input
              v-model="search"
              type="text"
              placeholder="Search users..."
              class="border rounded-lg pl-10 pr-4 py-2 w-64 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
            <Search class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
          </div>
          <button
            @click="router.visit('/admin/users/create')"
            class="flex items-center gap-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
          >
            <PlusCircle class="w-5 h-5" /> New User
          </button>
        </div>
      </header>

      <!-- USERS TABLE -->
      <main class="p-6 overflow-y-auto flex-1">
        <div class="bg-white p-4 rounded-lg shadow">
          <table class="min-w-full">
            <thead>
              <tr class="border-b bg-gray-50">
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
                class="border-t hover:bg-gray-50 transition"
              >
                <td class="px-4 py-2">{{ user.name }}</td>
                <td class="px-4 py-2">{{ user.email }}</td>
                <td class="px-4 py-2">{{ user.role }}</td>
                <td class="px-4 py-2 space-x-2">
                  <button
                    @click="router.visit(`/admin/users/${user.id}/edit`)"
                    class="text-blue-600 hover:underline"
                  >
                    Edit
                  </button>
                  <button
                    @click="router.delete(`/admin/users/${user.id}`)"
                    class="text-red-600 hover:underline"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="users.length === 0" class="text-center text-gray-500 py-8">
            No users found.
          </div>
        </div>
      </main>
    </div>
  </div>
  </Layout>
</template>

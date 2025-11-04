<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const sidebarItems = [
  { name: 'Projects', route: 'admin.dashboard' },
  { name: 'Developers', route: 'admin.developers' },
  { name: 'Clients', route: 'admin.clients' },
]

const showChat = ref(false)

function logout() {
  router.post(route('logout'))
}
</script>

<template>
  <div class="flex h-screen bg-gray-100">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg flex flex-col">
      <div class="p-4 text-2xl font-bold text-blue-600 border-b">Super Admin</div>
      <nav class="flex-1 p-3 space-y-2">
        <Link
          v-for="item in sidebarItems"
          :key="item.name"
          :href="route(item.route)"
          class="block px-3 py-2 rounded-lg hover:bg-blue-50 text-gray-700"
          :class="{ 'bg-blue-100 font-semibold text-blue-700': $page.component.includes(item.name) }"
        >
          {{ item.name }}
        </Link>
      </nav>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col">

      <!-- Top Navbar -->
      <header class="h-16 bg-white flex items-center justify-between px-6 shadow">
        <div class="flex items-center space-x-4">
          <Link :href="route('admin.dashboard')" class="font-semibold text-gray-800">Dashboard</Link>
          
          <input
            type="text"
            placeholder="Search..."
            class="border rounded-lg px-3 py-1 focus:outline-none focus:ring w-64"
          />
        </div>

        <div class="flex items-center space-x-4">
          <button @click="showChat = !showChat" class="text-gray-600 hover:text-blue-600">💬</button>
          <div class="relative">
            <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600">
              <span>Profile</span>
            </button>
          </div>
          <button @click="logout" class="text-red-600 hover:underline">Logout</button>
        </div>
      </header>

      <!-- Main page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

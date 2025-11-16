<script setup>
import { ref, computed} from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import {
  LayoutDashboard, FolderKanban, ClipboardList,
  CalendarDays, ActivitySquare, Settings, Users, Shield, User,
  MessageSquare, Bell, Sun, Moon, LogOut
} from 'lucide-vue-next'

const darkMode = ref(false)
const toggleDark = () => {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark', darkMode.value)
}

const page = usePage()
const user = computed(() => page.props.auth?.user || { name: '', email: '', roles: [], permissions: [] })
const can = computed(() => {
  const perms = page.props.auth.user?.permissions || []
  return {
    'view users': perms.includes('view users'),
    'view roles': perms.includes('view roles'),
  }
})

const logout = () => router.post('/logout')
const goToChats = () => router.visit('/chats')

const openSidebar = ref(true)
</script>

<template>
  

  <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Sidebar -->
    <aside
      v-if="openSidebar"
      class="  w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4 flex flex-col"
    >
      <!-- App Name -->
      <div class="flex items-center gap-2 mb-8">
        <LayoutDashboard class="w-6 h-6 text-indigo-500" />
        <h1 class="font-bold text-xl">Project Manager</h1>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 space-y-2 overflow-y-auto">
        <Link href="/dashboard" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
          <LayoutDashboard class="w-5 h-5" /> Dashboard
        </Link>
        <Link href="/projects" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
          <FolderKanban class="w-5 h-5" /> Projects
        </Link>
        
        <Link href="/calendar" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
          <CalendarDays class="w-5 h-5" /> Calendar
        </Link>
        <Link href="/activity" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
          <ActivitySquare class="w-5 h-5" /> Activity Log
        </Link>

        <!-- Settings Dropdown (Permission-Controlled) -->
       
          <details v-if="can['view users'] || can['view roles']" class="group">
            <summary class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900">
              <Settings class="w-5 h-5" /> Settings
            </summary>
            <div class="ml-6 mt-1 space-y-1">
              <Link v-if="can['view users']" href="/admin/users" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
                <Users class="w-5 h-5" /> Users
              </Link>
              <Link v-if="can['view roles']" href="/admin/roles" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
                <Shield class="w-5 h-5" /> Roles
              </Link>
            </div>
          </details>
       

        <Link href="/profile" class="flex items-center gap-2 p-2 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900">
          <User class="w-5 h-5" /> Profile
        </Link>
      </nav>

      <!-- Logout -->
      <button
        @click="logout"
        class="flex items-center gap-2 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900 text-red-600 dark:text-red-400 mt-4"
      >
        <LogOut class="w-5 h-5" /> Logout
      </button>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col ml-0 ">
      <!-- Top Navbar -->
      <header class="fixed top-0 left-0 md:left-64 right-0 flex items-center justify-between px-6 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-10">
        <div class="flex items-center gap-3">
          <button @click="openSidebar = !openSidebar" class="md:hidden text-gray-500 hover:text-indigo-500">
            ☰
          </button>
          <div class="text-lg font-semibold">Dashboard</div>
        </div>

        <div class="flex items-center gap-4">
          <!-- Search -->
          <input type="text" placeholder="Search..." class="px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 focus:outline-none text-sm" />

          <!-- Chat -->
          <button @click="goToChats">
            <MessageSquare class="w-5 h-5 cursor-pointer hover:text-indigo-500" />
          </button>

          <!-- Notifications -->
          <Bell class="w-5 h-5 cursor-pointer hover:text-indigo-500" />

          <!-- Theme Toggle -->
          <button @click="toggleDark">
            <Sun v-if="!darkMode" class="w-5 h-5" />
            <Moon v-else class="w-5 h-5" />
          </button>

          <!-- User Dropdown -->
          <div class="relative group">
            <button class="flex items-center gap-2">
              <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" class="w-8 h-8 rounded-full" />
            </button>
            <div class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg hidden group-hover:block">
              <Link href="/profile" class="block px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900">Profile</Link>
              <Link href="/settings/users" class="block px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900">Settings</Link>
              <button @click="logout" class="w-full text-left px-4 py-2 hover:bg-red-50 dark:hover:bg-red-900 text-red-600 dark:text-red-400">Logout</button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Body -->
      <main class="flex-1 pt-16 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
summary::-webkit-details-marker {
  display: none;
}
</style>

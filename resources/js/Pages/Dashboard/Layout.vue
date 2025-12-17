<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { 
  LayoutDashboard, FolderKanban, ClipboardList,
  CalendarDays, ActivitySquare, Settings, 
  Users, Shield, User, MessageSquare, 
  Bell, Sun, Moon, LogOut, Search, X,
  Home,
  GitBranch,
  FolderOpen,
  Clock,
  PieChart
} from 'lucide-vue-next'

const page = usePage()
const darkMode = ref(false)
const openSidebar = ref(true)

// Get current route path
const currentPath = computed(() => page.url)

// Check if a nav item is active
const isActive = (path) => {
  // Exact match for dashboard
  if (path === '/dashboard' && currentPath.value === '/dashboard') return true
  
  // For other pages, check if current path starts with the nav path
  // This handles nested routes like /projects/1, /projects/create, etc.
  if (path !== '/dashboard' && currentPath.value.startsWith(path)) return true
  
  return false
}

// Initialize dark mode from localStorage or system preference
onMounted(() => {
  // Check localStorage first
  const savedDarkMode = localStorage.getItem('darkMode')
  if (savedDarkMode !== null) {
    darkMode.value = savedDarkMode === 'true'
  } else {
    // Fallback to system preference
    darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  
  // Apply dark mode class
  document.documentElement.classList.toggle('dark', darkMode.value)
})

// ====================
// FIXED: Get unread count from page props - it updates with Inertia!
// ====================
const unreadCount = computed(() => {
  // This will update when Inertia visits new pages
  return page.props.unreadCount || 0
})

// Search functionality
const searchQuery = ref('')

// Get user info
const user = computed(() => page.props.auth?.user || { name: '', email: '', roles: [], permissions: [] })
const can = computed(() => {
  const perms = page.props.auth.user?.permissions || []
  return {
    'view users': perms.includes('view users'),
    'view roles': perms.includes('view roles'),
    'view events': perms.includes('view events'),
    'view projects': perms.includes('view projects'),
  }
})

// Helper functions
const toggleDark = () => {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark', darkMode.value)
  // Save preference to localStorage
  localStorage.setItem('darkMode', darkMode.value)
}

const goToChats = () => {
  router.visit('/chats')
}

const logout = () => router.post('/logout')

// Search functions
const emitSearchQuery = () => {
  // Emit the search query to the current page component
  window.dispatchEvent(new CustomEvent('page-search', {
    detail: { query: searchQuery.value }
  }))
}

const clearSearch = () => {
  searchQuery.value = ''
  emitSearchQuery() // Emit empty query to clear filter
}

// Clear search when page changes
watch(() => page.url, () => {
  searchQuery.value = ''
  emitSearchQuery() // Clear any active filters on page change
})

// Optional: Add keyboard shortcut to focus search (Cmd/Ctrl + K)
onMounted(() => {
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault()
      document.querySelector('.search-input')?.focus()
    }
    
    // Escape key clears search
    if (e.key === 'Escape' && searchQuery.value) {
      clearSearch()
    }
  })
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-teal-50/30 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <!-- Sidebar -->
    <aside
      v-if="openSidebar"
      class="w-64 bg-gradient-to-b from-teal-50 to-white dark:from-teal-900/10 dark:to-gray-800 border-r border-teal-100 dark:border-teal-800/30 p-4 flex flex-col transition-all duration-300"
    >
      <!-- App Name -->
      <div class="flex items-center gap-2 mb-8 pl-2">
        <div class="p-2 bg-gradient-to-br from-teal-500 to-emerald-500 rounded-xl">
          <PieChart class="w-6 h-6 text-white" />
        </div>
        <div>
          <h1 class="font-bold text-xl bg-gradient-to-r from-teal-600 to-emerald-600 bg-clip-text text-transparent">
            ProjectFlow
          </h1>
          <p class="text-xs text-teal-500 dark:text-teal-400">Project Management</p>
        </div>
      </div>

      <!-- User Profile Card -->
      <div class="mb-6 p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-xl border border-teal-100 dark:border-teal-800/30">
        <div class="flex items-center gap-3">
          <div class="relative">
            <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" 
                 class="w-10 h-10 rounded-full ring-2 ring-teal-200 dark:ring-teal-700" />
            <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border border-white dark:border-gray-800"></div>
          </div>
          <div class="flex-1">
            <p class="font-medium text-gray-800 dark:text-white truncate">{{ user.name }}</p>
            <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ user.email }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 space-y-1 overflow-y-auto">
        <Link href="/dashboard" 
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/dashboard')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white'
                  : 'hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30'
              ]">
          <div :class="[
            'p-2 rounded-lg transition-colors',
            isActive('/dashboard')
              ? 'bg-white/20'
              : 'bg-teal-100 dark:bg-teal-900/40 group-hover:bg-gradient-to-br group-hover:from-teal-500 group-hover:to-emerald-500'
          ]">
            <Home :class="[
              'w-5 h-5 transition-colors',
              isActive('/dashboard')
                ? 'text-white'
                : 'text-teal-600 dark:text-teal-400 group-hover:text-white'
            ]" />
          </div>
          <span :class="[
            'font-medium transition-colors',
            isActive('/dashboard')
              ? 'text-white'
              : 'text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300'
          ]">Dashboard</span>
        </Link>
        
        <Link v-if="can['view projects']" 
              href="/projects" 
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/projects')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white'
                  : 'hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30'
              ]">
          <div :class="[
            'p-2 rounded-lg transition-colors',
            isActive('/projects')
              ? 'bg-white/20'
              : 'bg-teal-100 dark:bg-teal-900/40 group-hover:bg-gradient-to-br group-hover:from-teal-500 group-hover:to-emerald-500'
          ]">
            <FolderOpen :class="[
              'w-5 h-5 transition-colors',
              isActive('/projects')
                ? 'text-white'
                : 'text-teal-600 dark:text-teal-400 group-hover:text-white'
            ]" />
          </div>
          <span :class="[
            'font-medium transition-colors',
            isActive('/projects')
              ? 'text-white'
              : 'text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300'
          ]">Projects</span>
        </Link>
        
        <Link v-if="can['view events']"
              href="/calendar" 
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/calendar')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white'
                  : 'hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30'
              ]">
          <div :class="[
            'p-2 rounded-lg transition-colors',
            isActive('/calendar')
              ? 'bg-white/20'
              : 'bg-teal-100 dark:bg-teal-900/40 group-hover:bg-gradient-to-br group-hover:from-teal-500 group-hover:to-emerald-500'
          ]">
            <Clock :class="[
              'w-5 h-5 transition-colors',
              isActive('/calendar')
                ? 'text-white'
                : 'text-teal-600 dark:text-teal-400 group-hover:text-white'
            ]" />
          </div>
          <span :class="[
            'font-medium transition-colors',
            isActive('/calendar')
              ? 'text-white'
              : 'text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300'
          ]">Calendar</span>
        </Link>
        
        <Link v-if="can['view users'] || can['view roles']"
              href="/settings"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/settings')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white'
                  : 'hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30'
              ]">
          <div :class="[
            'p-2 rounded-lg transition-colors',
            isActive('/settings')
              ? 'bg-white/20'
              : 'bg-teal-100 dark:bg-teal-900/40 group-hover:bg-gradient-to-br group-hover:from-teal-500 group-hover:to-emerald-500'
          ]">
            <Settings :class="[
              'w-5 h-5 transition-colors',
              isActive('/settings')
                ? 'text-white'
                : 'text-teal-600 dark:text-teal-400 group-hover:text-white'
            ]" />
          </div>
          <span :class="[
            'font-medium transition-colors',
            isActive('/settings')
              ? 'text-white'
              : 'text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300'
          ]">Settings</span>
        </Link>

        <Link href="/profile" 
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/profile')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white'
                  : 'hover:bg-gradient-to-r hover:from-teal-50 hover:to-emerald-50 dark:hover:from-teal-900/30 dark:hover:to-emerald-900/30'
              ]">
          <div :class="[
            'p-2 rounded-lg transition-colors',
            isActive('/profile')
              ? 'bg-white/20'
              : 'bg-teal-100 dark:bg-teal-900/40 group-hover:bg-gradient-to-br group-hover:from-teal-500 group-hover:to-emerald-500'
          ]">
            <User :class="[
              'w-5 h-5 transition-colors',
              isActive('/profile')
                ? 'text-white'
                : 'text-teal-600 dark:text-teal-400 group-hover:text-white'
            ]" />
          </div>
          <span :class="[
            'font-medium transition-colors',
            isActive('/profile')
              ? 'text-white'
              : 'text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300'
          ]">Profile</span>
        </Link>
      </nav>

      <!-- Bottom Section -->
      <div class="mt-6 pt-6 border-t border-teal-100 dark:border-teal-800/30">
        <button
          @click="logout"
          class="flex items-center justify-center gap-3 p-3 w-full rounded-xl hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 dark:hover:from-red-900/30 dark:hover:to-pink-900/30 text-red-600 dark:text-red-400 group transition-all duration-200"
        >
          <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-lg group-hover:bg-gradient-to-br group-hover:from-red-500 group-hover:to-pink-500 transition-colors">
            <LogOut class="w-5 h-5 group-hover:text-white" />
          </div>
          <span class="font-medium">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col ml-0">
      <!-- Top Navbar -->
      <header class="fixed top-0 left-0 md:left-64 right-0 flex items-center justify-between px-6 py-3 bg-gradient-to-r from-white/80 to-teal-50/80 dark:from-gray-800 dark:to-teal-900/20 backdrop-blur-sm border-b border-teal-100 dark:border-teal-800/30 z-10">
        <div class="flex items-center gap-3">
          <button @click="openSidebar = !openSidebar" 
                  class="md:hidden p-2 text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors">
            ☰
          </button>
          <div class="hidden md:flex items-center gap-2 text-sm text-teal-600 dark:text-teal-400">
            <GitBranch class="w-4 h-4" />
            <span>Project Management System</span>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <!-- Chat with badge -->
          <button @click="goToChats" class="relative p-2 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors group">
            <MessageSquare class="w-5 h-5 text-teal-600 dark:text-teal-400 group-hover:text-teal-700 dark:group-hover:text-teal-300" />
            <span 
              v-if="unreadCount > 0"
              class="absolute -top-1 -right-1 bg-gradient-to-br from-emerald-500 to-teal-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center ring-2 ring-white dark:ring-gray-800"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <!-- Theme Toggle -->
          <button @click="toggleDark" 
                  class="p-2 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors group">
            <Sun v-if="!darkMode" class="w-5 h-5 text-teal-600 dark:text-teal-400 group-hover:text-teal-700 dark:group-hover:text-teal-300" />
            <Moon v-else class="w-5 h-5 text-teal-600 dark:text-teal-400 group-hover:text-teal-700 dark:group-hover:text-teal-300" />
          </button>

          <!-- User Info -->
          <div class="flex items-center gap-3 p-2 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors">
            <div class="text-right">
              <p class="text-sm font-medium text-gray-800 dark:text-white">{{ user.name }}</p>
              <p class="text-xs text-teal-600 dark:text-teal-400">
                {{ user.roles?.[0] ? user.roles[0].replace('-', ' ') : 'User' }}
              </p>
            </div>
            <div class="relative">
              <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" 
                   class="w-8 h-8 rounded-full ring-2 ring-teal-200 dark:ring-teal-700" />
              <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-gradient-to-br from-emerald-400 to-teal-400 rounded-full border border-white dark:border-gray-800"></div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Body -->
      <main class="flex-1 pt-16 p-6 overflow-y-auto bg-gradient-to-b from-teal-50/20 to-white dark:from-gray-900 dark:to-teal-900/5">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
summary::-webkit-details-marker {
  display: none;
}

/* Custom scrollbar for sidebar */
aside nav::-webkit-scrollbar {
  width: 6px;
}

aside nav::-webkit-scrollbar-track {
  background: rgba(153, 246, 228, 0.1);
  border-radius: 10px;
}

aside nav::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #0d9488, #059669);
  border-radius: 10px;
}

aside nav::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #0d9488, #047857);
}

/* Dark mode styles */
.dark aside nav::-webkit-scrollbar-track {
  background: rgba(19, 78, 74, 0.3);
}

.dark aside nav::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #115e59, #064e3b);
}

.dark aside nav::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #0f766e, #065f46);
}

/* Gradient text animation */
@keyframes gradient-shift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

.bg-gradient-text {
  background: linear-gradient(90deg, #0d9488, #059669, #0d9488);
  background-size: 200% auto;
  animation: gradient-shift 3s ease-in-out infinite;
}

/* Smooth transitions */
* {
  transition: background-color 0.3s ease, border-color 0.3s ease;
}
</style>
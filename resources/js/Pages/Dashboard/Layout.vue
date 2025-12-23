<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { 
  LayoutDashboard, FolderKanban, ClipboardList,
  CalendarDays, ActivitySquare, Settings, 
  Users, Shield, User, MessageSquare, 
  Bell, Sun, Moon, LogOut, Search,
  Home,
  GitBranch,
  FolderOpen,
  Clock,
  PieChart,
  Menu,
  X as XIcon
} from 'lucide-vue-next'

const page = usePage()
const darkMode = ref(false)
const openSidebar = ref(false)
const isMobile = ref(false)
const isTablet = ref(false)

// Get current route path
const currentPath = computed(() => page.url)

// Check if a nav item is active
const isActive = (path) => {
  if (path === '/dashboard' && currentPath.value === '/dashboard') return true
  if (path !== '/dashboard' && currentPath.value.startsWith(path)) return true
  return false
}

// Initialize dark mode from localStorage or system preference
onMounted(() => {
  checkScreenSize()
  
  const savedDarkMode = localStorage.getItem('darkMode')
  if (savedDarkMode !== null) {
    darkMode.value = savedDarkMode === 'true'
  } else {
    darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  
  document.documentElement.classList.toggle('dark', darkMode.value)
  window.addEventListener('resize', checkScreenSize)
  updateSidebarState()
})

// Check screen size
const checkScreenSize = () => {
  const width = window.innerWidth
  isMobile.value = width < 768
  isTablet.value = width >= 768 && width < 1024
  updateSidebarState()
}

// Update sidebar state based on screen size
const updateSidebarState = () => {
  if (isMobile.value || isTablet.value) {
    openSidebar.value = false
  } else {
    openSidebar.value = true
  }
}

// Check if we should show mobile bottom nav
const showMobileNav = computed(() => {
  return isMobile.value || isTablet.value
})

// Toggle sidebar for mobile & tablet
const toggleSidebar = () => {
  if (isMobile.value || isTablet.value) {
    openSidebar.value = !openSidebar.value
  }
}

// Close sidebar on mobile/tablet when clicking a link
const handleNavClick = () => {
  if (isMobile.value || isTablet.value) {
    openSidebar.value = false
  }
}

// Get unread count
const unreadCount = computed(() => {
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
  localStorage.setItem('darkMode', darkMode.value)
}

const goToChats = () => {
  router.visit('/chats')
}

const logout = () => router.post('/logout')

// Search functions
const emitSearchQuery = () => {
  window.dispatchEvent(new CustomEvent('page-search', {
    detail: { query: searchQuery.value }
  }))
}

const clearSearch = () => {
  searchQuery.value = ''
  emitSearchQuery()
}

// Clear search when page changes
watch(() => page.url, () => {
  searchQuery.value = ''
  emitSearchQuery()
  if (isMobile.value || isTablet.value) {
    openSidebar.value = false
  }
})

// Optional: Add keyboard shortcut to focus search (Cmd/Ctrl + K)
onMounted(() => {
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault()
      document.querySelector('.search-input')?.focus()
    }
    
    if (e.key === 'Escape' && searchQuery.value) {
      clearSearch()
    }
  })
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-teal-50/30 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <!-- Desktop Sidebar Only (not for mobile/tablet) -->
    <aside
      v-if="!isMobile && !isTablet && openSidebar"
      class="w-64 h-full bg-gradient-to-b from-teal-50 to-white dark:from-teal-900/10 dark:to-gray-800 border-r border-teal-100 dark:border-teal-800/30 flex flex-col transition-all duration-300"
    >
      <!-- App Name -->
      <div class="flex items-center gap-2 p-4 border-b border-teal-100 dark:border-teal-800/30">
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
      <div class="p-4">
        <div class="p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-xl border border-teal-100 dark:border-teal-800/30">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" 
                   class="w-10 h-10 rounded-full ring-2 ring-teal-200 dark:ring-teal-700" />
              <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border border-white dark:border-gray-800"></div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-800 dark:text-white truncate">{{ user.name }}</p>
              <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ user.email }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
        <Link href="/dashboard" 
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/dashboard')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/projects')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/calendar')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/settings')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/profile')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
      <div class="p-4 pt-6 border-t border-teal-100 dark:border-teal-800/30 mt-auto">
       
        
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

    <!-- Mobile & Tablet Sidebar Overlay -->
    <div v-if="(isMobile || isTablet) && openSidebar" 
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"
         @click="openSidebar = false">
    </div>

    <!-- Mobile & Tablet Sidebar Drawer -->
    <aside v-if="(isMobile || isTablet) && openSidebar"
           class="fixed top-0 left-0 h-full w-64 bg-gradient-to-b from-teal-50 to-white dark:from-teal-900/10 dark:to-gray-800 border-r border-teal-100 dark:border-teal-800/30 flex flex-col z-50 transition-transform duration-300 transform lg:hidden"
           @click.stop>
      <!-- Mobile & Tablet Header -->
      <div class="flex items-center justify-between p-4 border-b border-teal-100 dark:border-teal-800/30">
        <div class="flex items-center gap-2">
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
        <button @click="openSidebar = false" class="p-2 text-teal-600 dark:text-teal-400 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl">
          <XIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- User Profile Card -->
      <div class="p-4">
        <div class="p-3 bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20 rounded-xl border border-teal-100 dark:border-teal-800/30">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" 
                   class="w-10 h-10 rounded-full ring-2 ring-teal-200 dark:ring-teal-700" />
              <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border border-white dark:border-gray-800"></div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-800 dark:text-white truncate">{{ user.name }}</p>
              <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ user.email }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile & Tablet Navigation -->
      <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
        <Link href="/dashboard" 
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/dashboard')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/projects')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/calendar')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/settings')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
              @click="handleNavClick"
              :class="[
                'flex items-center gap-3 p-3 rounded-xl group transition-all duration-200',
                isActive('/profile')
                  ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-lg'
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
      <div class="p-4 pt-6 border-t border-teal-100 dark:border-teal-800/30 mt-auto">
        
        
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
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top Navbar -->
      <header class="sticky top-0 flex items-center justify-between px-4 md:px-6 py-3 bg-gradient-to-r from-white/80 to-teal-50/80 dark:from-gray-800 dark:to-teal-900/20 backdrop-blur-sm border-b border-teal-100 dark:border-teal-800/30 z-30">
        <div class="flex items-center gap-3">
          <!-- Mobile & Tablet Menu Button -->
          <button v-if="isMobile || isTablet" 
                  @click="toggleSidebar" 
                  class="p-2 text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors">
            <Menu v-if="!openSidebar" class="w-5 h-5" />
            <XIcon v-else class="w-5 h-5" />
          </button>
          
          <!-- Desktop Menu Button - Only shows on desktop when sidebar is closed -->
          <button v-else-if="!openSidebar" 
                  @click="openSidebar = true" 
                  class="p-2 text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors">
            <Menu class="w-5 h-5" />
          </button>
          
          <div class="hidden lg:flex items-center gap-2 text-sm text-teal-600 dark:text-teal-400">
            <GitBranch class="w-4 h-4" />
            <span>Project Management System</span>
          </div>
          
          <!-- Mobile & Tablet App Name -->
          <div class="lg:hidden flex items-center gap-2">
            <div class="p-1.5 bg-gradient-to-br from-teal-500 to-emerald-500 rounded-lg">
              <PieChart class="w-4 h-4 text-white" />
            </div>
            <span class="font-bold text-lg bg-gradient-to-r from-teal-600 to-emerald-600 bg-clip-text text-transparent">
              ProjectFlow
            </span>
          </div>
        </div>

        <div class="flex items-center gap-3">
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

          <!-- Desktop User Info -->
          <div class="hidden lg:flex items-center gap-3 p-2 hover:bg-teal-100 dark:hover:bg-teal-900/30 rounded-xl transition-colors">
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
          
          <!-- Mobile & Tablet User Avatar -->
          <div class="lg:hidden">
            <div class="relative">
              <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + user.name" 
                   class="w-8 h-8 rounded-full ring-2 ring-teal-200 dark:ring-teal-700" />
              <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-gradient-to-br from-emerald-400 to-teal-400 rounded-full border border-white dark:border-gray-800"></div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Body -->
      <main class="flex-1 overflow-y-auto bg-gradient-to-b from-teal-50/20 to-white dark:from-gray-900 dark:to-teal-900/5">
        <slot />
      </main>

      <!-- Mobile & Tablet Bottom Navigation -->
      <nav v-if="showMobileNav" class="sticky bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-teal-100 dark:border-teal-800/30 py-2 px-4 flex justify-between items-center z-30 lg:hidden">
        <Link href="/dashboard"
              @click="handleNavClick"
              :class="[
                'flex flex-col items-center p-2 rounded-xl transition-all duration-200',
                isActive('/dashboard')
                  ? 'text-teal-600 dark:text-teal-400'
                  : 'text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400'
              ]">
          <Home :class="[
            'w-5 h-5 mb-1',
            isActive('/dashboard')
              ? 'text-teal-600 dark:text-teal-400'
              : 'text-gray-400 dark:text-gray-500'
          ]" />
          <span class="text-xs font-medium">Home</span>
        </Link>
        
        <Link v-if="can['view projects']"
              href="/projects"
              @click="handleNavClick"
              :class="[
                'flex flex-col items-center p-2 rounded-xl transition-all duration-200',
                isActive('/projects')
                  ? 'text-teal-600 dark:text-teal-400'
                  : 'text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400'
              ]">
          <FolderOpen :class="[
            'w-5 h-5 mb-1',
            isActive('/projects')
              ? 'text-teal-600 dark:text-teal-400'
              : 'text-gray-400 dark:text-gray-500'
          ]" />
          <span class="text-xs font-medium">Projects</span>
        </Link>
        
        <Link v-if="can['view events']"
              href="/calendar"
              @click="handleNavClick"
              :class="[
                'flex flex-col items-center p-2 rounded-xl transition-all duration-200',
                isActive('/calendar')
                  ? 'text-teal-600 dark:text-teal-400'
                  : 'text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400'
              ]">
          <Clock :class="[
            'w-5 h-5 mb-1',
            isActive('/calendar')
              ? 'text-teal-600 dark:text-teal-400'
              : 'text-gray-400 dark:text-gray-500'
          ]" />
          <span class="text-xs font-medium">Calendar</span>
        </Link>
        
        <Link href="/profile"
              @click="handleNavClick"
              :class="[
                'flex flex-col items-center p-2 rounded-xl transition-all duration-200',
                isActive('/profile')
                  ? 'text-teal-600 dark:text-teal-400'
                  : 'text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400'
              ]">
          <User :class="[
            'w-5 h-5 mb-1',
            isActive('/profile')
              ? 'text-teal-600 dark:text-teal-400'
              : 'text-gray-400 dark:text-gray-500'
          ]" />
          <span class="text-xs font-medium">Profile</span>
        </Link>
        
        <button @click="toggleSidebar"
                class="flex flex-col items-center p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-all duration-200">
          <Menu class="w-5 h-5 mb-1" />
          <span class="text-xs font-medium">More</span>
        </button>
      </nav>
    </div>
  </div>
</template>

<style scoped>
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

/* Ensure main content has proper padding on mobile/tablet */
main {
  padding-bottom: 80px; /* Space for bottom navigation */
}

/* Bottom navigation active state */
nav a.active {
  color: #0d9488;
  background-color: rgba(13, 148, 136, 0.1);
}

nav a.active svg {
  color: #0d9488;
}

.dark nav a.active {
  color: #5eead4;
  background-color: rgba(94, 234, 212, 0.1);
}

.dark nav a.active svg {
  color: #5eead4;
}

/* Tablet specific adjustments */
@media (min-width: 768px) and (max-width: 1023px) {
  /* Make bottom nav more spacious for tablets */
  nav {
    padding: 12px 24px;
  }
  
  nav button, nav a {
    min-width: 60px;
  }
  
  nav .text-xs {
    font-size: 11px;
  }
}

/* Desktop only */
@media (min-width: 1024px) {
  main {
    padding-bottom: 0; /* No bottom nav padding on desktop */
  }
  
  /* Fix the gap between sidebar and top nav */
  .flex > div:last-child {
    margin-left: 0;
  }
  
  aside {
    border-right: 1px solid rgb(204, 251, 241);
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
  
  .dark aside {
    border-right: 1px solid rgba(19, 78, 74, 0.5);
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
  }
}
</style>
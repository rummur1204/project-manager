<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { FolderKanban, User, LogOut, LayoutDashboard, Search, MessageSquare, UserCircle } from 'lucide-vue-next'

const logout = () => router.post('/logout')
const page = usePage()

const user = page.props.auth?.user || {
  name: '',
  email: '',
  roles: [],
  permissions: []
}
</script>

<template>
  <div class="flex h-screen bg-gray-50">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-md flex flex-col">
      <div class="p-6 border-b flex items-center justify-center">
        <h2 class="text-2xl font-bold text-blue-700">PM System</h2>
      </div>

      <nav class="flex-1 p-4 space-y-2 text-gray-700">
        <Link href="/dashboard" class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50"
          :class="{ 'bg-blue-100 text-blue-700 font-semibold': $page.url === '/dashboard' }">
          <LayoutDashboard class="w-5 h-5" /> Dashboard
        </Link>

        <Link  href="/projects"
         class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50"
          :class="{ 'bg-blue-100 text-blue-700 font-semibold': $page.url.startsWith('/projects') }">
          <FolderKanban class="w-5 h-5" /> Projects
        </Link>

        <!-- Only show Users link if user has 'manage users' permission -->
        <Link v-if="user?.permissions?.includes( 'view users','create users','edit users', 'delete users')" href="/admin/users"
          class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50"
          :class="{ 'bg-blue-100 text-blue-700 font-semibold': $page.url.startsWith('/users') }">
          <User class="w-5 h-5" /> Users
        </Link>

         <Link v-if="user?.permissions?.includes( 'view roles','create roles','edit roles', 'delete roles')" href="/admin/roles"
          class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50"
          :class="{ 'bg-blue-100 text-blue-700 font-semibold': $page.url.startsWith('/roles') }">
          <User class="w-5 h-5" /> Roles
        </Link>

        <Link href="/profile" class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50"
          :class="{ 'bg-blue-100 text-blue-700 font-semibold': $page.url.startsWith('/profile') }">
          <UserCircle class="w-5 h-5" /> Profile
        </Link>
      </nav>

      <div class="p-4 border-t">
        <button @click="logout"
          class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
          <LogOut class="w-5 h-5" /> Logout
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col">
      <!-- TOP NAVBAR -->
      <header class="bg-white shadow p-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
          <h1 class="text-xl font-semibold text-gray-700">Dashboard</h1>
          <div class="relative">
            <input type="text" placeholder="Search..." class="border rounded-lg px-3 py-1 pl-8 focus:ring focus:ring-blue-100" />
            <Search class="w-4 h-4 absolute left-2 top-2.5 text-gray-400" />
          </div>
        </div>
        <button class="flex items-center gap-2 text-gray-600 hover:text-blue-600">
          <MessageSquare class="w-5 h-5" /> Chat
        </button>
      </header>

      <main class="p-6 overflow-y-auto flex-1">
        <slot />
      </main>
    </div>
  </div>
</template>

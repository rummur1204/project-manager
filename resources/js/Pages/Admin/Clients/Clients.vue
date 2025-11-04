<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { User, MessageSquare, Search, PlusCircle } from 'lucide-vue-next'

const page = usePage()

const clients = computed(() => page.props.clients || [])
const filters = ref({
  q: page.props.filters?.q ?? ''
})

const search = () => {
  router.get('/admin/clients', { q: filters.value.q }, { preserveState: true, replace: true })
}

const logout = () => router.post('/logout')
</script>


<template>
  <div class="flex h-screen bg-gray-100">
    <!-- ========== SIDEBAR ========== -->
    <aside class="w-64 bg-white shadow-lg">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-gray-800">Super Admin</h2>
      </div>
      <nav class="p-4 space-y-2">
        <a
          href="/admin/dashboard"
          class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3h18v4H3zM3 17h18v4H3zM3 10h18v4H3z" />
          </svg>
          Projects
        </a>

        <a
          href="/admin/developers"
          class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700"
        >
          <User class="w-5 h-5" />
          Developers
        </a>

        <a
          href="/admin/clients"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-semibold"
        >
          <User class="w-5 h-5" />
          Clients
        </a>
      </nav>
    </aside>

    <!-- ========== MAIN AREA ========== -->
    <div class="flex-1 flex flex-col">
      <!-- ======= TOP NAVBAR ======= -->
      <header class="bg-white shadow p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <h1 class="text-2xl font-semibold text-gray-800">Clients</h1>
          <button
           @click="router.visit(route('admin.clients.create'))"
            class="flex items-center gap-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
          >
            <PlusCircle class="w-5 h-5" />
            New Client
          </button>
        </div>

        <div class="flex items-center gap-4">
          <div class="relative">
            <input
              v-model="filters.q"
              @input="search"
              type="text"
              placeholder="Search clients..."
              class="border rounded-lg pl-10 pr-4 py-2 w-64 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
            <Search class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
          </div>

          <button class="p-2 text-gray-600 hover:text-blue-600">
            <MessageSquare class="w-6 h-6" />
          </button>

          <div class="relative group">
            <button class="flex items-center gap-2">
              <img
                src="https://ui-avatars.com/api/?name=Admin"
                alt="Profile"
                class="w-8 h-8 rounded-full border"
              />
            </button>
            <div
              class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition"
            >
              <a
                href="/profile"
                class="block px-4 py-2 text-gray-700 hover:bg-gray-100"
              >Profile</a>
              <button
                @click="logout"
                class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- ======= MAIN CONTENT ======= -->
      <main class="p-6 overflow-y-auto flex-1">
        <div class="bg-white p-4 rounded-lg shadow">
          <table class="min-w-full">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left px-4 py-2">Name</th>
                <th class="text-left px-4 py-2">Email</th>
                <th class="text-left px-4 py-2">Projects</th>
                <th class="text-left px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="client in clients"
                :key="client.id"
                class="border-t hover:bg-gray-50 transition"
              >
                <td class="px-4 py-2">{{ client.name }}</td>
                <td class="px-4 py-2">{{ client.email }}</td>
                <td class="px-4 py-2">{{ client.project_title || '—' }}</td>
                <td class="px-4 py-2 space-x-2">
                  <button
                    class="text-blue-600 hover:underline"
                    @click="router.visit(`/admin/clients/${client.id}/edit`)"
                  >
                    Edit
                  </button>
                  <button
                    class="text-red-600 hover:underline"
                    @click="router.delete(`/admin/clients/${client.id}`)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="clients.length === 0" class="text-center text-gray-500 py-8">
            No clients found.
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 8px;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>

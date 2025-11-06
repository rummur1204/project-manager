<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
// import { useHead } from '@vueuse/head'
const page = usePage()
const user = computed(() => page.props.auth.user || null)
const roles = computed(() => page.props.auth.roles || [])
const permissions = computed(() => page.props.auth.permissions || [])

const hasRole = (r) => roles.value.includes(r)
const hasPermission = (p) => permissions.value.includes(p)
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
</script>
<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-100 p-4">
      <div class="mb-6">
        <h2 class="text-lg font-bold">MyApp</h2>
        <p class="text-sm">Hello, {{ user?.name }}</p>
      </div>

      <nav>
        <ul>
          <li class="mb-2"><inertia-link :href="route('dashboard')" class="block p-2 rounded hover:bg-gray-200">Dashboard</inertia-link></li>

          <li v-if="hasPermission('view projects')" class="mb-2"><inertia-link :href="route('projects.index')" class="block p-2 rounded hover:bg-gray-200">Projects</inertia-link></li>

          <!-- Roles & Users only to those who can manage users or are Super Admin -->
          <li v-if="hasPermission('view users') || hasRole('Super Admin')" class="mb-2"><inertia-link :href="route('users.index')" class="block p-2 rounded hover:bg-gray-200">Users</inertia-link></li>

          <li v-if="hasPermission('view roles') || hasRole('Super Admin')" class="mb-2"><inertia-link :href="route('roles.index')" class="block p-2 rounded hover:bg-gray-200">Roles</inertia-link></li>

        </ul>
      </nav>
    </aside>

    <!-- Main -->
    <div class="flex-1">
      <!-- Topbar -->
      <header class="bg-white shadow p-4 flex justify-between items-center">
        <div>
          <slot name="page-title"></slot>
        </div>

        <div class="flex items-center gap-4">
          <span class="text-sm">{{ user?.email }}</span>

          <form :action="route('logout')" method="post">
            <input type="hidden" name="_token" :value="csrf">
            <button type="submit" class="py-1 px-3 border rounded">Logout</button>
          </form>
        </div>
      </header>

      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>



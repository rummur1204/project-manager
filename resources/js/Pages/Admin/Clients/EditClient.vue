<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const submit = () => {
  form.post(route('admin.clients.store'), {
    onSuccess: () => router.visit('/admin/clients')
  })
}
</script>

<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-gray-800">Super Admin</h2>
      </div>
      <nav class="p-4 space-y-2">
        <a href="/admin/dashboard" class="block px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700">
          Projects
        </a>
        <a href="/admin/developers" class="block px-4 py-2 rounded-lg hover:bg-blue-50 text-gray-700">
          Developers
        </a>
        <a href="/admin/clients" class="block px-4 py-2 rounded-lg bg-blue-100 text-blue-700 font-semibold">
          Clients
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <header class="bg-white shadow p-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Edit Client</h1>
      </header>

      <main class="p-6 flex justify-center items-start">
        <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-lg">
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-1">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full border px-3 py-2 rounded focus:ring focus:ring-blue-300"
                placeholder="Enter client name"
              />
              <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Email</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full border px-3 py-2 rounded focus:ring focus:ring-blue-300"
                placeholder="Enter email"
              />
              <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Password</label>
              <input
                v-model="form.password"
                type="password"
                class="w-full border px-3 py-2 rounded focus:ring focus:ring-blue-300"
                placeholder="Enter password"
              />
              <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Confirm Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full border px-3 py-2 rounded focus:ring focus:ring-blue-300"
                placeholder="Confirm password"
              />
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="form.processing"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                Save Client
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  </div>
</template>

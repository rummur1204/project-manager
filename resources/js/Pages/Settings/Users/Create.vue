<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import Layout from '@/Pages/Dashboard/Layout.vue'

const props = defineProps({ roles: Array })
const form = useForm({
  name: '',
  email: '',
  password: '',
  role: props.roles?.[0] || ''
})
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Add New User</h1>
        <button @click="router.visit('/settings')" class="flex items-center gap-1 text-gray-600 dark:text-gray-300 hover:text-blue-600">
          <ArrowLeft class="w-5 h-5" /> Back
        </button>
      </div>

      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md max-w-lg mx-auto transition-colors">
        <form @submit.prevent="form.post('/settings/users')">
          <div class="space-y-4">
            <div>
              <label class="block text-gray-700 dark:text-gray-300 font-medium">Name</label>
              <input v-model="form.name" type="text" class="w-full border rounded-lg p-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required />
            </div>
            <div>
              <label class="block text-gray-700 dark:text-gray-300 font-medium">Email</label>
              <input v-model="form.email" type="email" class="w-full border rounded-lg p-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required />
            </div>
            <div>
              <label class="block text-gray-700 dark:text-gray-300 font-medium">Password</label>
              <input v-model="form.password" type="password" class="w-full border rounded-lg p-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required />
            </div>
            <div>
              <label class="block text-gray-700 dark:text-gray-300 font-medium">Role</label>
              <select v-model="form.role" class="w-full border rounded-lg p-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option v-for="role in props.roles" :key="role" :value="role">{{ role }}</option>
              </select>
            </div>
          </div>

          <button type="submit" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
            Create User
          </button>
        </form>
      </div>
    </main>
  </Layout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import Layout from '@/Pages/Dashboard/Layout.vue'

const props = defineProps({ user: Object, roles: Array })
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  role: props.user.role
})
</script>

<template>
  <Layout>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <header class="bg-white shadow p-4 flex justify-between items-center">
      <h1 class="text-xl font-semibold text-gray-800">Edit User</h1>
      <button @click="router.visit('/admin/users')" class="flex items-center gap-1 text-gray-600 hover:text-blue-600">
        <ArrowLeft class="w-5 h-5" /> Back
      </button>
    </header>

    <main class="flex-1 p-6">
      <div class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
        <form @submit.prevent="form.put(`/admin/users/${props.user.id}`)">
          <div class="space-y-4">
            <div>
              <label class="block text-gray-700 font-medium">Name</label>
              <input v-model="form.name" type="text" class="w-full border rounded-lg p-2" required />
            </div>
            <div>
              <label class="block text-gray-700 font-medium">Email</label>
              <input v-model="form.email" type="email" class="w-full border rounded-lg p-2" required />
            </div>
            <div>
              <label class="block text-gray-700 font-medium">Password (optional)</label>
              <input v-model="form.password" type="password" class="w-full border rounded-lg p-2" />
            </div>
            <div>
              <label class="block text-gray-700 font-medium">Role</label>
              <select v-model="form.role" class="w-full border rounded-lg p-2">
                <option v-for="role in props.roles" :key="role" :value="role">{{ role }}</option>
              </select>
            </div>
          </div>

          <button
            type="submit"
            class="mt-6 w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
          >
            Update User
          </button>
        </form>
      </div>
    </main>
  </div>
  </Layout>
</template>

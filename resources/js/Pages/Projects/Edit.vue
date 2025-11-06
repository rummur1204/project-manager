<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'

const props = defineProps({
  project: Object,
  clients: Array,
  developers: Array,
})
const form = useForm({
  title: props.project.title || '',
  description: props.project.description || '',
  client_id: props.project.client_id || '',
  developers: props.project.users?.map(u => u.id) || [],
  tasks: props.project.tasks || [],
})

// const form = useForm({
//   title: props.project.title,
//   description: props.project.description,
//   client_id: props.project.client_id,
//   developer_ids: props.project.developers.map(d => d.id),
//   due_date: props.project.due_date,
//   status: props.project.status,
// })
</script>

<template>
    <Layout>
  <div class="min-h-screen bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">
      <h2 class="text-2xl font-semibold mb-4">Edit Project</h2>

      <form @submit.prevent="form.put(`/projects/${props.project.id}`)">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium">Title</label>
            <input v-model="form.title" type="text" class="w-full border rounded-lg p-2 mt-1" required />
          </div>

          <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea v-model="form.description" class="w-full border rounded-lg p-2 mt-1" rows="3"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium">Client</label>
            <select v-model="form.client_id" class="w-full border rounded-lg p-2 mt-1" required>
              <option value="">Select client</option>
              <option v-for="c in props.clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">Developers</label>
            <div class="flex flex-wrap gap-2 mt-2">
              <label v-for="d in props.developers" :key="d.id" class="flex items-center gap-2">
                <input type="checkbox" :value="d.id" v-model="form.developer_ids" />
                {{ d.name }}
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium">Status</label>
            <select v-model="form.status" class="w-full border rounded-lg p-2 mt-1">
              <option>Pending</option>
              <option>In Progress</option>
              <option>Completed</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">Due Date</label>
            <input type="date" v-model="form.due_date" class="w-full border rounded-lg p-2 mt-1" required />
          </div>

          <div class="flex justify-end mt-6">
            <Link href="/projects" class="px-4 py-2 bg-gray-200 rounded-lg mr-2">Cancel</Link>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  </Layout>
</template>

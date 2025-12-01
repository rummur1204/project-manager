<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'

const props = defineProps({
  project: Object,
  clients: Array,
  developers: Array,
})

// Initialize form with project data
const form = useForm({
  title: props.project.title || '',
  description: props.project.description || '',
  client_id: props.project.client_id || '',
  developer_ids: props.project.developer_ids || [],
  due_date: props.project.due_date || '',
  status: props.project.status || 'Pending',
  tasks: props.project.tasks?.map(t => ({
    ...t,
    developer_ids: t.developer_ids || [],
    weight: Number(t.weight) || 0,
  })) || [],
  github_links: props.project.github_links?.length ? props.project.github_links : [''],
})

const showTaskModal = ref(false)
const editingTaskIndex = ref(null)
const newTask = ref({
  title: '',
  description: '',
  task_type: 'Gathering',
  assign_to: [],
  weight: 0,
})

// Available developers helpers (project-level)
const availableDevelopers = (index) => {
  return props.developers.filter(
    d => !form.developer_ids.includes(d.id) || form.developer_ids[index] === d.id
  )
}

const availableTaskDevelopers = computed(() =>
  props.developers.filter(d => form.developer_ids.includes(d.id))
)

// Normalize task weights to total 100%
const normalizedTasks = computed(() => {
  const total = form.tasks.reduce((sum, t) => sum + Number(t.weight || 0), 0)
  if (total === 0) return form.tasks.map(t => ({ ...t, weight: 0 }))
  return form.tasks.map(t => ({
    ...t,
    weight: Number(((t.weight / total) * 100).toFixed(2)),
  }))
})

// Show total weight
const totalWeight = computed(() => {
  return normalizedTasks.value.reduce((sum, t) => sum + Number(t.weight), 0).toFixed(2)
})

// Watch: Remove developers from tasks if removed from project
watch(() => form.developer_ids, (newDevs, oldDevs) => {
  const removed = oldDevs.filter(id => !newDevs.includes(id))
  if (!removed.length) return

  form.tasks.forEach(task => {
    task.developer_ids = task.developer_ids?.filter(id => newDevs.includes(id)) || []
  })
})

// Add/remove project-level developer slots
const addDeveloper = () => form.developer_ids.push('')
const removeDeveloper = (index) => form.developer_ids.splice(index, 1)

// Add or update task
const addTask = () => {
  if (!newTask.value.title.trim()) {
    alert('Task title is required')
    return
  }

  const taskData = {
    title: newTask.value.title,
    description: newTask.value.description,
    task_type: newTask.value.task_type,
    weight: Number(newTask.value.weight) || 0,
    developer_ids: newTask.value.assign_to,
  }

  if (editingTaskIndex.value !== null) {
    // Update existing task
    form.tasks[editingTaskIndex.value] = taskData
  } else {
    // Add new task
    form.tasks.push(taskData)
  }

  closeTaskModal()
}

watch(() => newTask.value.weight, (val) => {
  if (val > 5) newTask.value.weight = 5;
  if (val < 1) newTask.value.weight = 1;
});
// Remove task
const removeTask = (index) => form.tasks.splice(index, 1)

// Open/close task modal
const openTaskModal = (task = null, index = null) => {
  if (task) {
    editingTaskIndex.value = index
    newTask.value = {
      title: task.title,
      description: task.description,
      task_type: task.task_type,
      assign_to: [...task.developer_ids],
      weight: task.weight,
    }
  } else {
    editingTaskIndex.value = null
    newTask.value = { title: '', description: '', task_type: 'Gathering', assign_to: [], weight: 0 }
  }
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  editingTaskIndex.value = null
  newTask.value = { title: '', description: '', task_type: 'Gathering', assign_to: [], weight: 0 }
}

// GitHub links helpers
const addGithubLink = () => form.github_links.push('')
const removeGithubLink = (index) => form.github_links.splice(index, 1)

// Submit project
const submit = () => {
  form.put(route('projects.update', props.project.id), {
    data: {
      ...form.data(),
      tasks: normalizedTasks.value,
      github_links: form.github_links.filter(Boolean).map(s => s.trim()),
    },
    onSuccess: () => {
      alert('Project updated successfully!')
    },
  })
}
</script>

<template>
  <Layout>
    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow p-6">
      <h1 class="text-2xl font-bold mb-6 dark:text-gray-100">Edit Project</h1>

      <!-- Title -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200">Title</label>
        <input v-model="form.title" type="text" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100" />
      </div>

      <!-- Description -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200">Description</label>
        <textarea v-model="form.description" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100"></textarea>
      </div>

      <!-- Client -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200">Client</label>
        <select v-model="form.client_id" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100">
          <option disabled value="">Select a client</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- Project Developers -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200 mb-1">Developers</label>
        <div v-for="(id, index) in form.developer_ids" :key="index" class="flex gap-2 mb-2">
          <select v-model="form.developer_ids[index]" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100">
            <option disabled value="">Select a developer</option>
            <option v-for="d in availableDevelopers(index)" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
          <button @click="removeDeveloper(index)" class="text-red-500 hover:underline">Remove</button>
        </div>
        <button @click="addDeveloper" class="text-blue-600 dark:text-blue-400 hover:underline">+ Add Developer</button>
      </div>

      <!-- Due Date -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200">Due Date</label>
        <input type="date" v-model="form.due_date" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100" />
      </div>

      <!-- GitHub Links -->
      <div class="mb-4">
        <label class="block font-medium dark:text-gray-200 mb-2">Repository Links</label>

        <div v-for="(link, idx) in form.github_links" :key="idx" class="flex gap-2 mb-2 items-center">
          <input
            v-model="form.github_links[idx]"
            placeholder="https://github.com/owner/repo"
            type="url"
            class="flex-1 border rounded p-2 dark:bg-gray-700 dark:text-gray-100"
          />
          <button type="button" @click="removeGithubLink(idx)" class="text-red-500 hover:underline">Remove</button>
        </div>

        <button type="button" @click="addGithubLink" class="text-blue-600 dark:text-blue-400 hover:underline">
          + Add repository link
        </button>

        <p class="text-sm text-gray-500 mt-2">Optional — add one or more GitHub repository URLs.</p>
      </div>

      <!-- Tasks -->
      <!-- <div class="mb-4">
        <label class="block font-medium dark:text-gray-200 mb-2">Tasks</label>
        <div v-if="form.tasks.length" class="space-y-2 mb-3">
          <div v-for="(t, i) in normalizedTasks" :key="i" class="border rounded p-2 bg-gray-50 dark:bg-gray-700">
            <div class="flex justify-between items-center">
              <span class="dark:text-gray-200">{{ t.title }} ({{ t.task_type }}) - Weight: {{ t.weight }}%</span>
              <div class="flex gap-2">
                <button @click="openTaskModal(form.tasks[i], i)" class="text-blue-500 text-sm">Edit</button>
                <button @click="removeTask(i)" class="text-red-500 text-sm">Remove</button>
              </div>
            </div>
            <div class="mt-1 text-sm dark:text-gray-300">
              Assigned Developers:
              <span v-if="!t.developer_ids.length">None</span>
              <span v-for="devId in t.developer_ids" :key="devId">
                {{ props.developers.find(d => d.id === devId)?.name }}
              </span>
            </div>
          </div>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
          Total (Auto Calculated): <strong>{{ totalWeight }}%</strong>
        </p>

        <button type="button" class="text-blue-600 dark:text-blue-400 hover:underline" @click="openTaskModal()">
          + Add Task
        </button>
      </div> -->

      <!-- Task Modal -->
      <!-- <div v-if="showTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
          <h2 class="text-lg font-semibold mb-3 dark:text-gray-100">{{ editingTaskIndex !== null ? 'Edit Task' : 'Add Task' }}</h2>

          <label class="block font-medium dark:text-gray-200">Title</label>
          <input v-model="newTask.title" type="text" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-gray-100" />

          <label class="block font-medium dark:text-gray-200">Description</label>
          <textarea v-model="newTask.description" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-gray-100"></textarea>

          <label class="block font-medium dark:text-gray-200">Task Type</label>
          <select v-model="newTask.task_type" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-gray-100">
            <option value="Gathering">Gathering</option>
            <option value="Design">Design</option>
            <option value="Development">Development</option>
            <option value="Testing">Testing</option>
            <option value="Deployment">Deployment</option>
            <option value="Maintenance">Maintenance</option>
          </select>

          <label class="block font-medium dark:text-gray-200">Weight (%)</label>
          <input v-model.number="newTask.weight" type="number" min="1" max="5" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-gray-100" />

          <label class="block font-medium dark:text-gray-200 mb-2">Assign Developers</label>
          <div v-if="availableTaskDevelopers.length" class="space-y-1 mb-4">
            <div v-for="dev in availableTaskDevelopers" :key="dev.id" class="flex items-center gap-2">
              <input type="checkbox" :value="dev.id" v-model="newTask.assign_to" class="rounded" />
              <span class="dark:text-gray-300">{{ dev.name }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400 mb-3">No developers selected for this project yet.</p>

          <div class="flex justify-end gap-2 mt-4">
            <button @click="closeTaskModal" class="text-gray-500 dark:text-gray-300">Cancel</button>
            <button @click="addTask" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">Save Task</button>
          </div>
        </div>
      </div> -->

      <!-- Submit -->
      <div class="mt-6">
        <button @click="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" :disabled="form.processing">
          Update Project
        </button>
      </div>
    </div>
  </Layout>
</template>

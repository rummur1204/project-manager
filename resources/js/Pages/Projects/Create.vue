<script setup> 
import { ref, computed } from 'vue'
 import { useForm, router } from '@inertiajs/vue3' 
 import Layout from '../Dashboard/Layout.vue' 
 
 const props = defineProps({ clients: Array, developers: Array, }) 
 
 const form = useForm({ 
  title: '',
   description: '',
    client_id: '',
     developer_ids: [], 
     due_date: '',
      tasks: [], 
    }) 
    
const showTaskModal = ref(false) 
const newTask = ref({
   title: '',
    description: '', 
    task_type: 'Gathering',
     assign_to: [], 
     weight: 0, // raw input, not percent
    }) 
    
    // ✅ When saving a project, convert raw weights to percentages 
  const normalizedTasks = computed(() => { 
    const total = form.tasks.reduce((sum, t) => sum + Number(t.weight || 0), 0)
     if (total === 0) return form.tasks.map(t => ({ ...t, weight: 0 }))
     
     return form.tasks.map(t => ({ ...t, weight: Number(((t.weight / total) * 100).toFixed(2)), })) }) 
  // ✅ Show total normalized weight (should be 100%) 
  const totalWeight = computed(() => {
     const total = form.tasks.reduce((sum, t) => sum + Number(t.weight || 0), 0) 
     if (total === 0)
      return 0 
    return normalizedTasks.value.reduce((sum, t) => sum + Number(t.weight || 0), 0).toFixed(2) }) 
    // 🧑‍💻 Filter available project-level developers 
    const availableDevelopers = (index) => { 
      return props.developers.filter( d => !form.developer_ids.includes(d.id) || form.developer_ids[index] === d.id ) } 
      // Only show project’s selected developers for tasks 
    const availableTaskDevelopers = computed(() => props.developers.filter(d => form.developer_ids.includes(d.id)) ) 
  // Add/remove project-level developer slots 
  const addDeveloper = () => form.developer_ids.push('')
  const removeDeveloper = (index) => form.developer_ids.splice(index, 1) 
  // Add task 
  const addTask = () => {
     if (!newTask.value.title.trim()) { 
      alert('Task title is required') 
      return 
    } 
    form.tasks.push({
       title: newTask.value.title,
       description: newTask.value.description,
       task_type: newTask.value.task_type, 
       weight: Number(newTask.value.weight) || 0, // raw weight input 
       developer_ids: newTask.value.assign_to,
      }) 
  closeTaskModal()
 } 
 // Remove task 
 const removeTask = (index) => form.tasks.splice(index, 1) 
 // Close modal 
 const closeTaskModal = () => { 
  showTaskModal.value = false 
  newTask.value = {
     title: '',
      description: '', 
      task_type: 'Gathering', 
      assign_to: [],
       weight: 0 
      } 
  } 
  // ✅ Submit project (send normalized weights) 
  const submit = () => {
     if (!form.tasks.length) {
       alert('Please add at least one task.')
        return 
      } 
      const payload = {
        title: form.title,
        description: form.description,
        client_id: form.client_id,
        developer_ids: form.developer_ids,
        due_date: form.due_date,
        tasks: normalizedTasks.value, // ✅ normalized weights
    };
     router.post(route('projects.store'), payload, {
    onSuccess: () => {
      alert('Project created successfully!');
      form.reset();
    },
  });
       } 
      </script>



<template>
  <Layout>
    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow p-6">
      <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-100">Create New Project</h1>

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
  <input
    type="date"
    v-model="form.due_date"
    class="w-full border rounded p-2 dark:bg-gray-700 dark:text-gray-100"
  />
</div>

      <!-- Tasks -->
   <!-- Tasks -->
<div class="mb-4">
  <label class="block font-medium dark:text-gray-200 mb-2">Tasks</label>

  <div v-if="form.tasks.length" class="space-y-2 mb-3">
    <div v-for="(t, i) in normalizedTasks" :key="i" class="border rounded p-2 bg-gray-50 dark:bg-gray-700">
      <div class="flex justify-between items-center">
        <span class="dark:text-gray-200">
          {{ t.title }} ({{ t.task_type }}) - Weight: {{ t.weight }}%
        </span>
        <button @click="removeTask(i)" class="text-red-500 text-sm">Remove</button>
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

  <button type="button" class="text-blue-600 dark:text-blue-400 hover:underline" @click="showTaskModal = true">
    + Add Task
  </button>
</div>



      <!-- Task Modal -->
      <div v-if="showTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
    <h2 class="text-lg font-semibold mb-3 dark:text-gray-100">Add Task</h2>

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
    <input v-model.number="newTask.weight" type="number" min="0" max="100" class="w-full border rounded p-2 mb-3 dark:bg-gray-700 dark:text-gray-100" />

    <label class="block font-medium dark:text-gray-200 mb-2">Assign Developers</label>
    <div v-if="availableTaskDevelopers.length" class="space-y-1 mb-4">
      <div
        v-for="dev in availableTaskDevelopers"
        :key="dev.id"
        class="flex items-center gap-2"
      >
        <input
          type="checkbox"
          :value="dev.id"
          v-model="newTask.assign_to"
          class="rounded"
        />
        <span class="dark:text-gray-300">{{ dev.name }}</span>
      </div>
    </div>
    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mb-3">
      No developers selected for this project yet.
    </p>

    <div class="flex justify-end gap-2 mt-4">
      <button @click="closeTaskModal" class="text-gray-500 dark:text-gray-300">Cancel</button>
      <button @click="addTask" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
        Add Task
      </button>
    </div>
  </div>
</div>


      <!-- Submit -->
      <div class="mt-6">
        <button @click="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" :disabled="form.processing">
          Create Project
        </button>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'




const { props } = usePage()
const events = ref(props.events || [])
const projects = ref(props.projects || [])

// Create form
const newEvent = ref({
  title: '',
  description: '',
  start_date: '',
  end_date: '',
  project_id: null,
  task_id: null,
})

// Edit modal
const showEditModal = ref(false)
const editEvent = ref({
  event_id: null,
  title: '',
  description: '',
  start_date: '',
  end_date: '',
  project_id: null,
  task_id: null,
})

// When backend props change (Inertia refresh), update local events
watch(() => props.events, (v) => { events.value = v || [] })

// Calendar handlers
const handleDateClick = (info) => {
  // optional: quick create with clicked date
  newEvent.value.start_date = info.dateStr
  newEvent.value.end_date = info.dateStr
}

const handleEventClick = (info) => {
  const ev = info.event
  const extended = ev.extendedProps || {}
  // only open edit for manual events (type === 'event') — project events are read-only
  if (extended.type === 'event') {
    editEvent.value = {
      event_id: extended.event_id,
      title: ev.title,
      description: extended.description || '',
      start_date: ev.startStr || ev.start,
      end_date: ev.endStr || ev.end,
      project_id: extended.project_id || null,
      task_id: extended.task_id || null,
    }
    showEditModal.value = true
  }
}

// create new event
const createEvent = () => {
  router.post(route('calendar.store'), newEvent.value, {
    preserveScroll: true,
    onSuccess: () => {
      Object.assign(newEvent.value, {
        title: '',
        description: '',
        start_date: '',
        end_date: '',
        project_id: null,
      })
    }
  })
}



// save edit
const saveEvent = () => {
  // event id is the DB id stored in editEvent.event_id
  router.put(`/calendar/${editEvent.value.event_id}`, {
    title: editEvent.value.title,
    description: editEvent.value.description,
    start_date: editEvent.value.start_date,
    end_date: editEvent.value.end_date,
    project_id: editEvent.value.project_id,
    task_id: editEvent.value.task_id,
  }, {
    onSuccess: () => { showEditModal.value = false }
  })
}

// delete event
const deleteEvent = () => {
  router.delete(`/calendar/${editEvent.value.event_id}`, {
    onSuccess: () => { showEditModal.value = false }
  })
}
</script>

<template>
  <Layout>
    <h1 class="text-2xl font-semibold mb-4">Calendar</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Calendar -->
      <div class="md:col-span-2 bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
     <FullCalendar
  :plugins="[dayGridPlugin, interactionPlugin]"
  initial-view="dayGridMonth"
  :events="events"
  @dateClick="handleDateClick"
  @eventClick="handleEventClick"
  :height="700"
/>


      </div>

      <!-- Right column: Create form + projects quick list -->
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow space-y-4">
        <h2 class="font-semibold">Add Event</h2>

        <input v-model="newEvent.title" type="text" placeholder="Title" class="w-full border rounded p-2" />
        <textarea v-model="newEvent.description" placeholder="Description" class="w-full border rounded p-2"></textarea>

        <div class="grid grid-cols-2 gap-2">
          <input v-model="newEvent.start_date" type="date" class="border rounded p-2" />
          <input v-model="newEvent.end_date" type="date" class="border rounded p-2" />
        </div>

        <select v-model="newEvent.project_id" class="w-full border rounded p-2">
          <option :value="null">No project</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.title }}</option>
        </select>

        <div class="flex justify-end">
          <button @click="createEvent" class="bg-indigo-600 text-white px-3 py-1.5 rounded">Create</button>
        </div>
      </div>
    </div>

    <!-- Edit modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg w-96">
        <h2 class="text-lg font-semibold mb-3">Edit Event</h2>

        <input v-model="editEvent.title" class="w-full border rounded p-2 mb-2" />
        <textarea v-model="editEvent.description" class="w-full border rounded p-2 mb-2"></textarea>
        <div class="grid grid-cols-2 gap-2 mb-2">
          <input v-model="editEvent.start_date" type="date" class="border rounded p-2" />
          <input v-model="editEvent.end_date" type="date" class="border rounded p-2" />
        </div>

        <select v-model="editEvent.project_id" class="w-full border rounded p-2 mb-3">
          <option :value="null">No project</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.title }}</option>
        </select>

        <div class="flex justify-end gap-2">
          <button @click="showEditModal = false" class="px-3 py-1 bg-gray-300 rounded">Cancel</button>
          <button @click="deleteEvent" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
          <button @click="saveEvent" class="px-3 py-1 bg-indigo-600 text-white rounded">Save</button>
        </div>
      </div>
    </div>
  </Layout>
</template>

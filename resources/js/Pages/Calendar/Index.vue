<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { usePage, router, Head } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

// ─────────────── Props from Laravel ───────────────
const { props } = usePage()
const events = ref(props.events ?? [])
const projects = props.projects ?? []

// ─────────────── Calendar ref ───────────────
const calendarRef = ref(null)

// ─────────────── Modal state ───────────────
const showModal = ref(false)
const form = ref({
  title: '',
  description: '',
  start_date: '',
  end_date: '',
  project_id: '',
  task_id: '',
  type: 'normal',
  color: '#3788d8'
})

// ─────────────── Project → Task logic ───────────────
const projectTasks = ref([])

const loadTasks = async (projectId) => {
  if (!projectId) {
    projectTasks.value = []
    form.value.task_id = ''
    return
  }
  try {
    const response = await axios.get(`/api/projects/${projectId}/tasks`)
    projectTasks.value = response.data
  } catch (e) {
    console.log('Error fetching tasks:', e)
  }
}

// ─────────────── Event handlers ───────────────
const onDateClick = (info) => {
  form.value.start_date = info.dateStr
  form.value.end_date = info.dateStr
  showModal.value = true
}

const onEventDrop = (info) => {
  router.patch(`/calendar/${info.event.id}`, {
    start_date: info.event.startStr,
    end_date: info.event.endStr ?? info.event.startStr
  })
}

const onEventResize = (info) => {
  router.patch(`/calendar/${info.event.id}`, {
    start_date: info.event.startStr,
    end_date: info.event.endStr ?? info.event.startStr
  })
}

// ─────────────── Submit event ───────────────
const submitEvent = () => {
  router.post('/calendar', form.value, {
    onSuccess: (page) => {
      showModal.value = false
      resetForm()
      nextTick(() => {
        // Update calendar events without full reload
        events.value = page.props.events ?? events.value
      })
    }
  })
}

const resetForm = () => {
  form.value = {
    title: '',
    description: '',
    start_date: '',
    end_date: '',
    project_id: '',
    task_id: '',
    type: 'normal',
    color: '#3788d8'
  }
  projectTasks.value = []
}
</script>

<template>
  <Layout>

  <div class="p-6">
    <!-- Calendar Container -->
    <div class="bg-white shadow rounded p-4">
      <FullCalendar
        ref="calendarRef"
        :plugins="[dayGridPlugin, interactionPlugin]"
        initial-view="dayGridMonth"
        :events="events"
        @dateClick="onDateClick"
        @eventDrop="onEventDrop"
        @eventResize="onEventResize"
        editable
        droppable
        selectable
        height="600"
      />
    </div>
  </div>

  <!-- ─────────────── Event Modal ─────────────── -->
  <div
    v-if="showModal"
    class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50"
  >
    <div class="bg-white w-full max-w-lg rounded shadow p-6">
      <h2 class="text-lg font-bold mb-4">Create Event</h2>

      <!-- TITLE -->
      <label class="block mb-2">Title</label>
      <input v-model="form.title" class="w-full border rounded p-2 mb-3" />

      <!-- DESCRIPTION -->
      <label class="block mb-2">Description</label>
      <textarea v-model="form.description" class="w-full border rounded p-2 mb-3"></textarea>

      <!-- DATE RANGE -->
      <div class="flex gap-2">
        <div class="flex-1">
          <label class="block mb-2">Start</label>
          <input type="date" v-model="form.start_date" class="w-full border rounded p-2" />
        </div>
        <div class="flex-1">
          <label class="block mb-2">End</label>
          <input type="date" v-model="form.end_date" class="w-full border rounded p-2" />
        </div>
      </div>

      <!-- TYPE -->
      <label class="block mt-4 mb-2">Type</label>
      <select v-model="form.type" class="w-full border rounded p-2 mb-3">
        <option value="normal">Normal Event</option>
        <option value="project_deadline">Project Deadline</option>
        <option value="task_deadline">Task Deadline</option>
      </select>

      <!-- PROJECT SELECTION -->
      <div v-if="form.type !== 'normal'">
        <label class="block mb-2">Project</label>
        <select
          v-model="form.project_id"
          @change="loadTasks(form.project_id)"
          class="w-full border rounded p-2 mb-3"
        >
          <option value="">Select Project</option>
          <option
            v-for="p in projects"
            :key="p.id"
            :value="p.id"
          >
            {{ p.name }}
          </option>
        </select>
      </div>

      <!-- TASK SELECTION -->
      <div v-if="form.type === 'task_deadline'">
        <label class="block mb-2">Task</label>
        <select v-model="form.task_id" class="w-full border rounded p-2 mb-3">
          <option value="">Select Task</option>
          <option
            v-for="t in projectTasks"
            :key="t.id"
            :value="t.id"
          >
            {{ t.title }}
          </option>
        </select>
      </div>

      <!-- COLOR -->
      <label class="block mb-2">Color</label>
      <input type="color" v-model="form.color" class="w-full border rounded p-2 mb-4" />

      <!-- BUTTONS -->
      <div class="flex justify-end gap-2">
        <button class="px-4 py-2 bg-gray-300 rounded" @click="showModal = false">
          Cancel
        </button>
        <button class="px-4 py-2 bg-blue-600 text-white rounded" @click="submitEvent">
          Save
        </button>
      </div>
    </div>
  </div>
</Layout>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Layout from "../Dashboard/Layout.vue";
import dayjs from "dayjs";

const page = usePage();

// Reactive data from page props
const projects = ref(page.props.projects || []);
const allTasks = ref(page.props.tasks || []);
const events = ref(page.props.events || []);
const activities = ref(page.props.activities || []);

// Watch for page updates to refresh data
watch(() => page.props, (newProps) => {
  console.log('Page props updated:', newProps);
  projects.value = newProps.projects || [];
  allTasks.value = newProps.tasks || [];
  events.value = newProps.events || [];
  activities.value = newProps.activities || [];
}, { deep: true });

// Calendar state
const currentDate = ref(dayjs());
const currentMonth = ref(currentDate.value.month());
const currentYear = ref(currentDate.value.year());

// Form
const form = useForm({
  title: "",
  description: "",
  start_date: currentDate.value.format("YYYY-MM-DD"),
  end_date: currentDate.value.format("YYYY-MM-DD"),
  project_id: "",
  task_id: "",
  activity_id: "",
  color: "#3b82f6",
});

// Filtered tasks (based on selected project)
const filteredTasks = computed(() => {
  if (!form.project_id) return [];
  return allTasks.value.filter(t => t.project_id == form.project_id);
});

// Days in calendar
const daysInMonth = computed(() => {
  const start = dayjs().year(currentYear.value).month(currentMonth.value).startOf("month");
  const end = dayjs().year(currentYear.value).month(currentMonth.value).endOf("month");

  const days = [];
  let current = start.startOf("week");

  while (current.isBefore(end.endOf("week")) || days.length < 42) {
    days.push(current);
    current = current.add(1, "day");
  }

  return days;
});

// Month name
const currentMonthName = computed(() => {
  return dayjs().year(currentYear.value).month(currentMonth.value).format("MMMM YYYY");
});

// Navigation
const prevMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
};

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
};

// Select date
const selectDate = (date) => {
  const formatted = date.format("YYYY-MM-DD");
  form.start_date = formatted;
  form.end_date = formatted;
};

// Events on a date - FIXED: Use start_date instead of start
// Events on a date - FIXED: Use the correct property names
const getEventsForDate = (date) => {
  const dateStr = date.format("YYYY-MM-DD");
  const list = [];

  console.log('Checking events for date:', dateStr);
  console.log('Available events:', events.value);

  // Calendar events - FIX: Use 'start' property (from controller mapping)
  events.value.forEach(ev => {
    if (ev && ev.start === dateStr) {
      console.log('Found matching event:', ev);
      list.push({
        type: "event",
        title: ev.title,
        color: ev.color
      });
    }
  });

  // Project deadlines
  projects.value.forEach(project => {
    if (project && project.due_date === dateStr) {
      list.push({
        type: "project",
        title: project.title,
        color: "#ef4444",
      });
    }
  });

  // Activity deadlines
  activities.value.forEach(activity => {
    if (activity && activity.due_date === dateStr) {
      list.push({
        type: "activity",
        title: activity.title,
        color: "#22c55e",
      });
    }
  });

  console.log('Events for date', dateStr, ':', list);
  return list;
};

// When selecting a project → autofill the deadline + red color
watch(() => form.project_id, (projectId) => {
  form.task_id = "";

  if (!projectId) {
    form.color = "#3b82f6";
    return;
  }

  const project = projects.value.find(p => p.id == projectId);
  if (project?.due_date) {
    form.start_date = project.due_date;
    form.end_date = project.due_date;
    form.color = "#ef4444";
  }
});

// When selecting a task → yellow
watch(() => form.task_id, (taskId) => {
  if (taskId) form.color = "#facc15";
});

// Save event
const saveEvent = () => {
  console.log('Saving event:', form.data());
  
  form.post("/calendar", {
    preserveScroll: true,
    onSuccess: () => {
      console.log('Event saved successfully');
      form.reset();
      form.start_date = dayjs().format("YYYY-MM-DD");
      form.end_date = dayjs().format("YYYY-MM-DD");
      form.color = "#3b82f6";
    },
    onError: (errors) => {
      console.log('Errors saving event:', errors);
    }
  });
};

// Debug: log events changes
watch(events, (newEvents) => {
  console.log('Events updated:', newEvents);
}, { deep: true });
</script>

<template>
  <Layout>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
      <!-- Calendar -->
      <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <button @click="prevMonth" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            ← Previous
          </button>
          <h2 class="text-xl font-semibold text-gray-800">{{ currentMonthName }}</h2>
          <button @click="nextMonth" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Next →
          </button>
        </div>

        <!-- Debug info -->
        <div class="mb-4 p-2 bg-yellow-50 text-sm text-gray-700 rounded">
          Total events: {{ events.length }}
        </div>

        <!-- Day headers -->
        <div class="grid grid-cols-7 gap-2 text-center font-semibold text-gray-600 mb-2">
          <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
            {{ day }}
          </div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7 gap-2">
          <div
            v-for="day in daysInMonth"
            :key="day.format('YYYY-MM-DD')"
            class="min-h-[100px] border border-gray-200 rounded-lg p-2 cursor-pointer hover:bg-gray-50 transition-colors"
            :class="{
              'bg-gray-100': !day.isSame(dayjs().year(currentYear).month(currentMonth), 'month'),
              'bg-blue-50': day.format('YYYY-MM-DD') === form.start_date
            }"
            @click="selectDate(day)"
          >
            <div class="text-sm font-medium mb-1" :class="{
              'text-gray-400': !day.isSame(dayjs().year(currentYear).month(currentMonth), 'month'),
              'text-blue-600': day.format('YYYY-MM-DD') === form.start_date
            }">
              {{ day.date() }}
            </div>

            <!-- Events for this day -->
            <div class="space-y-1">
              <div
                v-for="(event, index) in getEventsForDate(day)"
                :key="`${event.type}-${event.title}-${index}`"
                class="text-xs text-white px-2 py-1 rounded truncate"
                :style="{ backgroundColor: event.color }"
                :title="event.title"
              >
                {{ event.title }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Event Form -->
      <div class="bg-white shadow rounded-lg p-4 max-h-[550px] overflow-y-auto">
        <h2 class="text-lg font-semibold mb-4 text-gray-800">Add Event</h2>

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="form.title"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              placeholder="Event title"
            />
            <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
            <input type="date" v-model="form.start_date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
            <div v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" v-model="form.end_date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
            <select v-model="form.project_id"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option value="">-- Select Project --</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.title }} ({{ project.due_date }})
              </option>
            </select>
          </div>

          <!-- TASK DROPDOWN APPEARS ONLY WHEN PROJECT SELECTED -->
          <div v-if="form.project_id">
            <label class="block text-sm font-medium text-gray-700 mb-1">Task</label>
            <select v-model="form.task_id"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option value="">-- Select Task --</option>
              <option v-for="task in filteredTasks" :key="task.id" :value="task.id">
                {{ task.title }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <input type="color" v-model="form.color" class="w-full h-10 border rounded-lg cursor-pointer" />
          </div>

          <button @click="saveEvent" :disabled="form.processing"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium py-3 px-4 rounded-lg transition-colors">
            <span v-if="form.processing">Saving...</span>
            <span v-else>Save Event</span>
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>
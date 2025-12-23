<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Layout from "../Dashboard/Layout.vue";
import dayjs from "dayjs";

const page = usePage();

// Check for dark mode
const isDarkMode = computed(() => {
  return document.documentElement.classList.contains('dark');
});

// Ensure dark mode is applied on mount
onMounted(() => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  }
});

// Reactive data from page props
const projects = ref(page.props.projects || []);
const allTasks = ref(page.props.tasks || []);
const events = ref(page.props.events || []);
const activities = ref(page.props.activities || []);
const permissions = ref(page.props.permissions?.calendar || {
  create: true,
  edit: true,
  delete: true,
  view: true
});
const auth = ref(page.props.auth || {});

// Debug: Check what events look like
console.log('Events from controller:', events.value);
console.log('User permissions:', permissions.value);
console.log('Auth user:', auth.value);

// Watch for page updates to refresh data
watch(() => page.props, (newProps) => {
  console.log('Page props updated:', newProps);
  projects.value = newProps.projects || [];
  allTasks.value = newProps.tasks || [];
  events.value = newProps.events || [];
  activities.value = newProps.activities || [];
  permissions.value = newProps.permissions?.calendar || {
    create: true,
    edit: true,
    delete: true,
    view: true
  };
  auth.value = newProps.auth || {};
}, { deep: true });

// Calendar state
const currentDate = ref(dayjs());
const currentMonth = ref(currentDate.value.month());
const currentYear = ref(currentDate.value.year());
const selectedDate = ref(currentDate.value.format("YYYY-MM-DD"));
const showModal = ref(false);
const modalMode = ref('add');
const selectedEvent = ref(null);
const isMobile = ref(false);
const activeView = ref('calendar'); // 'calendar' or 'events'

// Check screen size on mount and resize
const checkScreenSize = () => {
  isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize);
});

// Forms
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

const editForm = useForm({
  id: "",
  title: "",
  description: "",
  start_date: "",
  end_date: "",
  project_id: "",
  task_id: "",
  activity_id: "",
  color: "#3b82f6",
});

// Get color based on event type
const getEventColor = (event) => {
  if (event.type === 'project') {
    return '#ef4444';
  } else if (event.type === 'activity') {
    return '#22c55e';
  } else if (event.type === 'event') {
    if (event.task_id) {
      return '#facc15';
    } else if (event.project_id) {
      return '#a855f7';
    } else {
      return event.color || '#3b82f6';
    }
  }
  return '#3b82f6';
};

// Get event type label for display
const getEventTypeLabel = (event) => {
  if (event.type === 'project') {
    return 'Project Deadline';
  } else if (event.type === 'activity') {
    return 'Activity Deadline';
  } else if (event.type === 'event') {
    if (event.task_id) {
      return 'Task Event';
    } else if (event.project_id) {
      return 'Project Event';
    } else {
      return 'Event';
    }
  }
  return 'Event';
};

// Permission checks
const canCreate = computed(() => permissions.value.create !== false);
const canEdit = computed(() => permissions.value.edit !== false);
const canDelete = computed(() => permissions.value.delete !== false);
const canView = computed(() => permissions.value.view !== false);

// Check if user can edit/delete specific event (for ownership-based permissions)
const canEditEvent = (event) => {
  if (event.type !== 'event') return false;
  if (!canEdit.value) return false;
  // Optional: Add ownership check
  // return event.created_by === auth.value.user?.id;
  return true;
};

const canDeleteEvent = (event) => {
  if (event.type !== 'event') return false;
  if (!canDelete.value) return false;
  // Optional: Add ownership check
  // return event.created_by === auth.value.user?.id;
  return true;
};

// Filtered tasks (based on selected project)
const filteredTasks = computed(() => {
  if (!form.project_id) return [];
  return allTasks.value.filter(t => t.project_id == form.project_id);
});

const filteredTasksEdit = computed(() => {
  if (!editForm.project_id) return [];
  return allTasks.value.filter(t => t.project_id == editForm.project_id);
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
  selectedDate.value = formatted;
  if (isMobile.value) {
    activeView.value = 'events';
  }
};

// Open modal to add event
const openAddModal = () => {
  if (!canCreate.value) return;
  
  modalMode.value = 'add';
  form.reset();
  form.start_date = selectedDate.value;
  form.end_date = selectedDate.value;
  
  if (form.project_id) {
    const project = projects.value.find(p => p.id == form.project_id);
    if (project?.due_date) {
      form.color = "#a855f7";
    }
  } else if (form.task_id) {
    form.color = "#facc15";
  } else {
    form.color = "#3b82f6";
  }
  
  showModal.value = true;
};

// Open modal to edit event
const openEditModal = (event) => {
  if (!canEditEvent(event)) return;
  
  selectedEvent.value = event;
  modalMode.value = 'edit';
  
  editForm.id = event.id;
  editForm.title = event.title;
  editForm.description = event.description || "";
  editForm.start_date = dayjs(event.start).format("YYYY-MM-DD");
  editForm.end_date = event.end_date ? dayjs(event.end_date).format("YYYY-MM-DD") : dayjs(event.start).format("YYYY-MM-DD");
  editForm.project_id = event.project_id || "";
  editForm.task_id = event.task_id || "";
  editForm.activity_id = event.activity_id || "";
  editForm.color = event.color || "#3b82f6";
  
  showModal.value = true;
};

// Close modal
const closeModal = () => {
  showModal.value = false;
  selectedEvent.value = null;
};

// Events for selected date
const selectedDateEvents = computed(() => {
  return getEventsForDate(dayjs(selectedDate.value));
});

// Check if date is today
const isToday = (date) => {
  return date.format('YYYY-MM-DD') === dayjs().format('YYYY-MM-DD');
};

// Check if date has any events - FIXED VERSION
const dateHasEvents = (date) => {
  const dateStr = date.format("YYYY-MM-DD");
  
  // Check calendar events with date range
  const hasEvent = events.value.some(ev => {
    if (!ev || !ev.start) return false;
    
    const startDate = dayjs(ev.start).format("YYYY-MM-DD");
    // Use end_date if it exists, otherwise use start date
    const endDate = ev.end_date ? dayjs(ev.end_date).format("YYYY-MM-DD") : startDate;
    
    // Check if date falls within range (inclusive)
    return dateStr >= startDate && dateStr <= endDate;
  });
  
  if (hasEvent) return true;
  
  // Check project deadlines (single day)
  const hasProject = projects.value.some(project => 
    project && project.due_date === dateStr
  );
  
  if (hasProject) return true;
  
  // Check activity deadlines (single day)
  const hasActivity = activities.value.some(activity => 
    activity && activity.due_date === dateStr
  );
  
  return hasActivity;
};

// Get events for a date - FIXED VERSION
const getEventsForDate = (date) => {
  const dateStr = date.format("YYYY-MM-DD");
  const list = [];

  // Calendar events with date range
  events.value.forEach(ev => {
    if (!ev || !ev.start) return;
    
    const startDate = dayjs(ev.start).format("YYYY-MM-DD");
    const endDate = ev.end_date ? dayjs(ev.end_date).format("YYYY-MM-DD") : startDate;
    
    // Check if date falls within range (inclusive)
    if (dateStr >= startDate && dateStr <= endDate) {
      list.push({
        ...ev,
        type: "event",
        color: ev.color || "#3b82f6"
      });
    }
  });

  // Project deadlines (single day)
  projects.value.forEach(project => {
    if (project && project.due_date === dateStr) {
      list.push({
        ...project,
        type: "project",
        color: "#ef4444",
      });
    }
  });

  // Activity deadlines (single day)
  activities.value.forEach(activity => {
    if (activity && activity.due_date === dateStr) {
      list.push({
        ...activity,
        type: "activity",
        color: "#22c55e",
      });
    }
  });

  return list;
};

// Get event markers for a date - FIXED VERSION
const getEventMarkersForDate = (date) => {
  const dateStr = date.format("YYYY-MM-DD");
  const markers = [];

  // Calendar events with date range
  events.value.forEach(ev => {
    if (!ev || !ev.start) return;
    
    const startDate = dayjs(ev.start).format("YYYY-MM-DD");
    const endDate = ev.end_date ? dayjs(ev.end_date).format("YYYY-MM-DD") : startDate;
    
    // Check if date falls within range (inclusive)
    if (dateStr >= startDate && dateStr <= endDate) {
      markers.push({
        type: "event",
        color: getEventColor({
          type: 'event',
          task_id: ev.task_id,
          project_id: ev.project_id,
          color: ev.color
        })
      });
    }
  });

  // Project deadlines (single day)
  if (projects.value.some(project => project && project.due_date === dateStr)) {
    markers.push({
      type: "project",
      color: "#ef4444"
    });
  }

  // Activity deadlines (single day)
  if (activities.value.some(activity => activity && activity.due_date === dateStr)) {
    markers.push({
      type: "activity",
      color: "#22c55e"
    });
  }

  return markers.slice(0, 3);
};

// Watch for project selection
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
  }
  
  form.color = "#a855f7";
});

watch(() => editForm.project_id, (projectId) => {
  editForm.task_id = "";

  if (!projectId) {
    editForm.color = "#3b82f6";
    return;
  }

  const project = projects.value.find(p => p.id == projectId);
  if (project?.due_date) {
    editForm.start_date = project.due_date;
    editForm.end_date = project.due_date;
  }
  
  editForm.color = "#a855f7";
});

// When selecting a task → yellow
watch(() => form.task_id, (taskId) => {
  if (taskId) form.color = "#facc15";
});

watch(() => editForm.task_id, (taskId) => {
  if (taskId) editForm.color = "#facc15";
});

// Save event
const saveEvent = () => {
  if (!canCreate.value) return;
  
  form.post("/calendar", {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      closeModal();
    },
    onError: (errors) => {
      console.log('Errors saving event:', errors);
    }
  });
};

// Update event
const updateEvent = () => {
  if (!canEdit.value) return;
  
  editForm.put(`/calendar/${editForm.id}`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      closeModal();
    },
    onError: (errors) => {
      console.log('Errors updating event:', errors);
    }
  });
};

// Confirm delete event
const confirmDelete = (event) => {
  if (!event || !canDeleteEvent(event)) return;
  
  if (!confirm(`Are you sure you want to delete "${event.title}"?`)) {
    return;
  }
  
  deleteEvent(event);
};

// Delete event
const deleteEvent = (event) => {
  if (!event || !canDeleteEvent(event)) return;
  
  useForm({}).delete(`/calendar/${event.id}`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      console.log('Event deleted successfully');
      closeModal();
    },
    onError: (errors) => {
      console.log('Errors deleting event:', errors);
      alert('Failed to delete event. Please try again.');
    }
  });
};
</script>
<template>
  <Layout>
    <div class="p-4 md:p-6 h-screen flex flex-col overflow-hidden">
      <!-- Mobile View Toggle -->
      <div v-if="isMobile" class="mb-4">
        <div class="flex justify-center space-x-2">
          <button 
            @click="activeView = 'calendar'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="activeView === 'calendar' 
              ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
          >
            Calendar
          </button>
          <button 
            @click="activeView = 'events'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="activeView === 'events' 
              ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
          >
            Events
          </button>
        </div>
      </div>

      <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 min-h-0">
        <!-- Calendar - Left Side (2/3 width) -->
        <div 
          class="lg:col-span-2 bg-white dark:bg-gray-800 shadow rounded-lg p-3 md:p-4 flex flex-col min-h-0"
          :class="{
            'hidden lg:flex': isMobile && activeView !== 'calendar',
            'flex': !isMobile || activeView === 'calendar'
          }"
        >
          <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
            <div class="flex items-center gap-2">
              <button @click="prevMonth" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors text-sm md:text-base">
                ←
              </button>
              <h2 class="text-lg md:text-xl font-semibold text-gray-800 dark:text-gray-200 text-center sm:text-left">
                {{ currentMonthName }}
              </h2>
              <button @click="nextMonth" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors text-sm md:text-base">
                →
              </button>
            </div>
            <button 
              v-if="canCreate"
              @click="openAddModal"
              class="bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-medium py-2 px-3 md:px-4 rounded-lg transition-colors text-sm md:text-base whitespace-nowrap w-full sm:w-auto"
              :class="{ 'opacity-50 cursor-not-allowed': !canCreate }"
            >
              + Add Event
            </button>
          </div>

          <!-- Color Legend - Mobile optimized -->
          <div class="mb-3 md:mb-4">
            <div class="flex flex-wrap gap-2 items-center text-xs md:text-sm">
              <div class="flex items-center">
                <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2 bg-blue-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Event</span>
              </div>
              <div class="flex items-center">
                <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2 bg-purple-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Project</span>
              </div>
              <div class="flex items-center">
                <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2 bg-yellow-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Task</span>
              </div>
              <div class="flex items-center">
                <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2 bg-red-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Deadline</span>
              </div>
              <div class="flex items-center">
                <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-1 md:mr-2 bg-green-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Activity</span>
              </div>
            </div>
          </div>

          <!-- Day headers - Mobile optimized -->
          <div class="grid grid-cols-7 gap-1 text-center font-semibold text-gray-600 dark:text-gray-400 mb-1 text-xs md:text-sm">
            <div v-for="day in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="day">
              {{ day }}
            </div>
          </div>

          <!-- Calendar grid - Proper height distribution -->
          <div class="grid grid-cols-7 gap-1 flex-1">
            <div
              v-for="day in daysInMonth"
              :key="day.format('YYYY-MM-DD')"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-1 md:p-2 cursor-pointer transition-colors relative flex flex-col"
              :class="{
                'bg-gray-100 dark:bg-gray-700': !day.isSame(dayjs().year(currentYear).month(currentMonth), 'month'),
                'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30': day.format('YYYY-MM-DD') === selectedDate,
                'hover:bg-gray-50 dark:hover:bg-gray-700/50': day.format('YYYY-MM-DD') !== selectedDate,
                'ring-1 md:ring-2 ring-blue-500 dark:ring-blue-400': isToday(day)
              }"
              @click="selectDate(day)"
            >
              <!-- Date number -->
              <div class="flex justify-between items-start mb-0 md:mb-1">
                <div class="text-xs md:text-sm font-medium" :class="{
                  'text-gray-400 dark:text-gray-500': !day.isSame(dayjs().year(currentYear).month(currentMonth), 'month'),
                  'text-blue-600 dark:text-blue-400': day.format('YYYY-MM-DD') === selectedDate,
                  'text-gray-700 dark:text-gray-300': day.isSame(dayjs().year(currentYear).month(currentMonth), 'month'),
                  'text-blue-800 dark:text-blue-300 font-bold': isToday(day)
                }">
                  {{ day.date() }}
                  <span v-if="isToday(day)" class="text-xs text-blue-600 dark:text-blue-400 hidden md:inline">•</span>
                </div>
                <!-- Event count badge -->
                <div 
                  v-if="dateHasEvents(day)" 
                  class="w-4 h-4 md:w-5 md:h-5 flex items-center justify-center text-xs bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full"
                >
                  {{ getEventsForDate(day).length }}
                </div>
              </div>

              <!-- Event markers (small dots) -->
              <div class="flex-1 flex flex-col justify-end">
                <div class="flex flex-wrap gap-0 md:gap-1 justify-center">
                  <div
                    v-for="(marker, index) in getEventMarkersForDate(day)"
                    :key="index"
                    class="w-1 h-1 md:w-2 md:h-2 rounded-full"
                    :style="{ backgroundColor: marker.color }"
                    :title="marker.type"
                  ></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Mobile: Back to calendar button when in events view -->
          <div v-if="isMobile && activeView === 'calendar'" class="mt-3">
            <button 
              @click="activeView = 'events'"
              class="w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium py-2 px-4 rounded-lg transition-colors text-sm"
            >
              View Events for {{ dayjs(selectedDate).format('MMM D') }}
            </button>
          </div>
        </div>

        <!-- Selected Date Events - Right Side (1/3 width) -->
        <div 
          class="bg-white dark:bg-gray-800 shadow rounded-lg p-3 md:p-4 flex flex-col min-h-0"
          :class="{
            'hidden lg:flex': isMobile && activeView !== 'events',
            'flex': !isMobile || activeView === 'events'
          }"
        >
          <!-- Mobile: Back button -->
          <div v-if="isMobile && activeView === 'events'" class="mb-3">
            <button 
              @click="activeView = 'calendar'"
              class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-2"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
              Back to Calendar
            </button>
          </div>
          
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base md:text-lg font-semibold text-gray-800 dark:text-gray-200">
              {{ dayjs(selectedDate).format('ddd, MMM D, YYYY') }}
            </h2>
            <button 
              v-if="canCreate"
              @click="openAddModal"
              class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs md:text-sm font-medium whitespace-nowrap"
              :class="{ 'opacity-50 cursor-not-allowed': !canCreate }"
            >
              + Add Event
            </button>
          </div>
          
          <div v-if="selectedDateEvents.length > 0" class="flex-1 overflow-y-auto pr-1 md:pr-2 space-y-2 md:space-y-3">
            <div 
              v-for="event in selectedDateEvents" 
              :key="`${event.type}-${event.id || event.title}`"
              class="group relative flex items-start p-2 md:p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
            >
              <!-- Event color indicator -->
              <div class="w-2 h-2 md:w-3 md:h-3 rounded-full mr-2 md:mr-3 mt-1 md:mt-1 flex-shrink-0" :style="{ backgroundColor: getEventColor(event) }"></div>
              
              <!-- Event content -->
              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 dark:text-gray-100 truncate text-sm md:text-base">{{ event.title }}</div>
                <div v-if="event.description" class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-0 md:mt-1 line-clamp-2">{{ event.description }}</div>
                <!-- Show date range if event spans multiple days -->
                <div v-if="event.type === 'event'" class="text-xs text-gray-500 dark:text-gray-500 mt-0 md:mt-1">
                  {{ dayjs(event.start).format('MMM D') }}
                  <span v-if="event.end_date && event.end_date !== event.start_date">
                    - {{ dayjs(event.end_date).format('MMM D') }}
                  </span>
                </div>
                <div class="mt-1 md:mt-2 flex flex-wrap gap-1 md:gap-2">
                  <span class="text-xs px-1.5 py-0.5 md:px-2 md:py-1 rounded capitalize" :style="{ 
                    backgroundColor: getEventColor(event) + '20',
                    color: getEventColor(event)
                  }">
                    {{ getEventTypeLabel(event) }}
                  </span>
                  <!-- Show "Linked" badge for project/activity deadlines -->
                  <span 
                    v-if="event.type === 'project' || event.type === 'activity'" 
                    class="text-xs px-1.5 py-0.5 md:px-2 md:py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                  >
                    Linked
                  </span>
                </div>
              </div>

              <!-- Action buttons ONLY for manually added events and based on permissions -->
              <div 
                v-if="event.type === 'event' && (canEditEvent(event) || canDeleteEvent(event))"
                class="absolute right-1 md:right-2 top-1 md:top-2 opacity-0 group-hover:opacity-100 transition-opacity flex space-x-0 md:space-x-1"
              >
                <button 
                  v-if="canEditEvent(event)"
                  @click.stop="openEditModal(event)"
                  class="p-0.5 md:p-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                  title="Edit Event"
                >
                  <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button 
                  v-if="canDeleteEvent(event)"
                  @click.stop="confirmDelete(event)"
                  class="p-0.5 md:p-1 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                  title="Delete Event"
                >
                  <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 p-2 md:p-4">
            <svg class="w-12 h-12 md:w-16 md:h-16 text-gray-300 dark:text-gray-600 mb-2 md:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-center text-sm md:text-base">No events scheduled</p>
            <p class="text-xs md:text-sm mt-1 text-center" v-if="canCreate">Click "Add Event" or select a different date</p>
            <p class="text-xs md:text-sm mt-1 text-center" v-else>No events scheduled for this date</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Event Modal - Mobile Optimized -->
    <div v-if="showModal && modalMode === 'add'" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-2 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="closeModal"></div>
        
        <!-- Modal panel -->
        <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg max-h-[90vh] overflow-y-auto">
          <!-- Modal header -->
          <div class="flex items-start justify-between p-3 md:p-4 border-b dark:border-gray-700 rounded-t sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
              Add Event for {{ dayjs(selectedDate).format('MMM D, YYYY') }}
            </h3>
            <button type="button" @click="closeModal" class="text-gray-400 bg-transparent hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100 rounded-lg text-sm p-1 ml-auto inline-flex items-center">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          
          <!-- Modal body -->
          <div class="p-4 md:p-6 space-y-3 md:space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
              <input v-model="form.title"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                placeholder="Event title"
                :disabled="!canCreate"
              />
              <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
              <textarea v-model="form.description" rows="2"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canCreate"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date *</label>
                <input type="date" v-model="form.start_date"
                  class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                  :disabled="!canCreate" />
                <div v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" v-model="form.end_date"
                  class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                  :disabled="!canCreate" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
              <select v-model="form.project_id"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canCreate"
              >
                <option value="">-- Select Project --</option>
                <option v-for="project in projects" :key="project.id" :value="project.id">
                  {{ project.title }}
                </option>
              </select>
            </div>

            <!-- TASK DROPDOWN APPEARS ONLY WHEN PROJECT SELECTED -->
            <div v-if="form.project_id">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task</label>
              <select v-model="form.task_id"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canCreate"
              >
                <option value="">-- Select Task --</option>
                <option v-for="task in filteredTasks" :key="task.id" :value="task.id">
                  {{ task.title }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
              <div class="flex items-center space-x-3 md:space-x-4">
                <input type="color" v-model="form.color" class="w-12 h-10 md:w-16 md:h-10 border dark:border-gray-600 rounded-lg cursor-pointer" :disabled="!canCreate" />
                <div class="w-6 h-6 md:w-8 md:h-8 rounded-full border dark:border-gray-600" :style="{ backgroundColor: form.color }"></div>
                <span class="text-xs md:text-sm text-gray-600 dark:text-gray-400 truncate">{{ form.color }}</span>
              </div>
            </div>
          </div>
          
          <!-- Modal footer -->
          <div class="flex flex-col sm:flex-row items-center p-4 md:p-6 space-y-2 sm:space-y-0 sm:space-x-2 border-t dark:border-gray-700 border-gray-200 rounded-b sticky bottom-0 bg-white dark:bg-gray-800 z-10">
            <button @click="saveEvent" :disabled="form.processing || !canCreate"
              class="w-full sm:flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors text-sm md:text-base">
              <span v-if="form.processing">Saving...</span>
              <span v-else>Save Event</span>
            </button>
            <button @click="closeModal" type="button"
              class="w-full sm:w-auto py-3 px-4 text-sm md:text-base font-medium text-gray-900 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Event Modal - Mobile Optimized -->
    <div v-if="showModal && modalMode === 'edit'" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-2 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="closeModal"></div>
        
        <!-- Modal panel -->
        <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg max-h-[90vh] overflow-y-auto">
          <!-- Modal header -->
          <div class="flex items-start justify-between p-3 md:p-4 border-b dark:border-gray-700 rounded-t sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
              Edit Event
            </h3>
            <button type="button" @click="closeModal" class="text-gray-400 bg-transparent hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100 rounded-lg text-sm p-1 ml-auto inline-flex items-center">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          
          <!-- Modal body -->
          <div class="p-4 md:p-6 space-y-3 md:space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
              <input v-model="editForm.title"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                placeholder="Event title"
                :disabled="!canEdit"
              />
              <div v-if="editForm.errors.title" class="text-red-500 text-xs mt-1">{{ editForm.errors.title }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
              <textarea v-model="editForm.description" rows="2"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canEdit"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date *</label>
                <input type="date" v-model="editForm.start_date"
                  class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                  :disabled="!canEdit" />
                <div v-if="editForm.errors.start_date" class="text-red-500 text-xs mt-1">{{ editForm.errors.start_date }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" v-model="editForm.end_date"
                  class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                  :disabled="!canEdit" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
              <select v-model="editForm.project_id"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canEdit"
              >
                <option value="">-- Select Project --</option>
                <option v-for="project in projects" :key="project.id" :value="project.id">
                  {{ project.title }}
                </option>
              </select>
            </div>

            <!-- TASK DROPDOWN APPEARS ONLY WHEN PROJECT SELECTED -->
            <div v-if="editForm.project_id">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task</label>
              <select v-model="editForm.task_id"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm md:text-base"
                :disabled="!canEdit"
              >
                <option value="">-- Select Task --</option>
                <option v-for="task in filteredTasksEdit" :key="task.id" :value="task.id">
                  {{ task.title }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
              <div class="flex items-center space-x-3 md:space-x-4">
                <input type="color" v-model="editForm.color" class="w-12 h-10 md:w-16 md:h-10 border dark:border-gray-600 rounded-lg cursor-pointer" :disabled="!canEdit" />
                <div class="w-6 h-6 md:w-8 md:h-8 rounded-full border dark:border-gray-600" :style="{ backgroundColor: editForm.color }"></div>
                <span class="text-xs md:text-sm text-gray-600 dark:text-gray-400 truncate">{{ editForm.color }}</span>
              </div>
            </div>
          </div>
          
          <!-- Modal footer -->
          <div class="flex flex-col sm:flex-row items-center p-4 md:p-6 space-y-2 sm:space-y-0 sm:space-x-2 border-t dark:border-gray-700 border-gray-200 rounded-b sticky bottom-0 bg-white dark:bg-gray-800 z-10">
            <button @click="updateEvent" :disabled="editForm.processing || !canEdit"
              class="w-full sm:flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors text-sm md:text-base">
              <span v-if="editForm.processing">Updating...</span>
              <span v-else>Update Event</span>
            </button>
            
            <button @click="closeModal" type="button"
              class="w-full sm:w-auto py-3 px-4 text-sm md:text-base font-medium text-gray-900 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<style>
/* Custom scrollbar for events panel */
.pr-1::-webkit-scrollbar,
.pr-2::-webkit-scrollbar,
.max-h-\[90vh\]::-webkit-scrollbar {
  width: 4px;
}

@media (min-width: 768px) {
  .pr-1::-webkit-scrollbar,
  .pr-2::-webkit-scrollbar,
  .max-h-\[90vh\]::-webkit-scrollbar {
    width: 6px;
  }
}

.pr-1::-webkit-scrollbar-track,
.pr-2::-webkit-scrollbar-track,
.max-h-\[90vh\]::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.dark .pr-1::-webkit-scrollbar-track,
.dark .pr-2::-webkit-scrollbar-track,
.dark .max-h-\[90vh\]::-webkit-scrollbar-track {
  background: #2d3748;
  border-radius: 3px;
}

.pr-1::-webkit-scrollbar-thumb,
.pr-2::-webkit-scrollbar-thumb,
.max-h-\[90vh\]::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.dark .pr-1::-webkit-scrollbar-thumb,
.dark .pr-2::-webkit-scrollbar-thumb,
.dark .max-h-\[90vh\]::-webkit-scrollbar-thumb {
  background: #4a5568;
  border-radius: 3px;
}

.pr-1::-webkit-scrollbar-thumb:hover,
.pr-2::-webkit-scrollbar-thumb:hover,
.max-h-\[90vh\]::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

.dark .pr-1::-webkit-scrollbar-thumb:hover,
.dark .pr-2::-webkit-scrollbar-thumb:hover,
.dark .max-h-\[90vh\]::-webkit-scrollbar-thumb:hover {
  background: #718096;
}

/* Line clamp for descriptions */
.line-clamp-2 {
  display: -webkit-box;
  
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Disabled state */
.cursor-not-allowed {
  cursor: not-allowed;
}

/* Responsive text sizing */
.text-responsive {
  font-size: clamp(0.875rem, 2vw, 1rem);
}

/* Improve touch targets on mobile */
@media (max-width: 640px) {
  button, 
  input[type="color"],
  select {
    min-height: 44px;
  }
  
  .calendar-day {
    min-height: 60px;
  }
}
</style>
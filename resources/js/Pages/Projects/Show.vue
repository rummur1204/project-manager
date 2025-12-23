<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import Layout from '../Dashboard/Layout.vue'

const props = defineProps({
  project: { type: Object, required: true },
  activity_types: { type: Array, default: () => [] },
})

const page = usePage()
const user = page.props.auth?.user || {}

/* ---------- Tabs ---------- */
const activeMainTab = ref('overview')
const activeTaskTab = ref('pending')
const activeActivityTypeTab = ref('')
const activeActivityStatusTab = ref('pending')
const activeCommentTab = ref('new')

/* ---------- Modals & Forms ---------- */
const showAddTaskModal = ref(false)
const showEditTaskModal = ref(false)
const showAddActivityModal = ref(false)
const showEditActivityModal = ref(false)

/* Add modal state */
const stagedTasks = ref([])

const currentNewTask = ref({
  title: '',
  description: '',
  task_type: 'Development',
  assign_to: [],
  raw_weight: 1,
  due_date: ''
})

/* Activity modal state */
const newActivity = ref({
  title: '',
  description: '',
  activity_type_id: '',
  due_date: '',
  developer_ids: []
})

/* Edit activity modal state */
const editingActivity = ref(null)
const editActivityForm = ref({
  title: '',
  description: '',
  activity_type_id: '',
  due_date: '',
  developer_ids: []
})

/* Edit modal state */
const editTasks = ref([])
const editingTaskIndex = ref(null)

/* ---------- Comments ---------- */
const comments = ref(
  (props.project.comments || [])
    .map(comment => ({
      ...comment,
      seen_by: Array.isArray(comment.seen_by) ? comment.seen_by : [],
      is_editing: false,
      edit_message: comment.message,
      user: comment.user || { id: null, name: 'Unknown' }
    }))
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)

const newComment = ref('')
const commentsContainer = ref(null)

watch(() => props.project.comments, (newComments) => {
  if (newComments) {
    comments.value = newComments
      .map(comment => ({
        ...comment,
        seen_by: Array.isArray(comment.seen_by) ? comment.seen_by : [],
        is_editing: false,
        edit_message: comment.message,
        user: comment.user || { id: null, name: 'Unknown' }
      }))
      .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  }
}, { deep: true })

/* ---------- Helpers ---------- */
const availableDevelopers = computed(() => props.project.developers || [])
const isAssignedDeveloper = computed(() =>
  (props.project.developers || []).some(d => d.id === user.id)
)
const currentDeveloper = computed(() =>
  (props.project.developers || []).find(d => d.id === user.id)
)
const hasAccepted = computed(() => currentDeveloper.value?.pivot?.accepted)
const projectStatus = computed(() => {
  const progress = calculateWeightedProgress(props.project.tasks)
  
  if (progress >= 100) {
    return 'Completed'
  }
  
  const hasAnyAcceptedDeveloper = (props.project.developers || []).some(d => d.pivot?.accepted)
  
  if (hasAnyAcceptedDeveloper) {
    return 'In Progress'
  }
  
  return 'Pending'
})

/* ---------- Tab Counts ---------- */
const pendingTasksCount = computed(() => {
  const tasks = props.project.tasks || []
  if (isAssignedDeveloper.value && hasAccepted.value) {
    return tasks.filter(task => 
      task.status === 'Pending' && 
      task.developers?.some(dev => dev.id === user.id)
    ).length
  }
  return tasks.filter(t => t.status === 'Pending').length
})

const pendingActivitiesCount = computed(() => {
  const activities = props.project.activities || []
  if (isAssignedDeveloper.value && hasAccepted.value) {
    return activities.filter(activity => 
      activity.status === 'Pending' && 
      activity.developers?.some(dev => dev.id === user.id)
    ).length
  }
  return activities.filter(a => a.status === 'Pending').length
})

const newCommentsCount = computed(() => {
  return comments.value.filter(comment => 
    comment.user?.id !== user.id && 
    !comment.seen_by?.includes(user.id)
  ).length
})

const seenCommentsCount = computed(() => {
  return comments.value.filter(comment => 
    comment.user?.id !== user.id && 
    comment.seen_by?.includes(user.id)
  ).length
})

const myCommentsCount = computed(() => {
  return comments.value.filter(comment => 
    comment.user?.id === user.id
  ).length
})

/* ---------- Tasks filtering ---------- */
const filteredTasks = computed(() => {
  const tasks = props.project.tasks || []
  
  let filtered = tasks
  if (isAssignedDeveloper.value && hasAccepted.value) {
    filtered = tasks.filter(task => 
      task.developers?.some(dev => dev.id === user.id)
    )
  }
  
  if (activeTaskTab.value === 'pending') return filtered.filter(t => t.status === 'Pending')
  if (activeTaskTab.value === 'inprogress') return filtered.filter(t => t.status === 'In Progress')
  if (activeTaskTab.value === 'completed') return filtered.filter(t => t.status === 'Completed')
  
  return filtered
})

/* ---------- Activities filtering ---------- */
const filteredActivities = computed(() => {
  const activities = props.project.activities || []
  
  let filtered = activities
  if (isAssignedDeveloper.value && hasAccepted.value) {
    filtered = activities.filter(activity => 
      activity.developers?.some(dev => dev.id === user.id)
    )
  }
  
  if (activeActivityTypeTab.value) {
    filtered = filtered.filter(activity => activity.activity_type_id == activeActivityTypeTab.value)
  }
  
  if (activeActivityStatusTab.value === 'pending') return filtered.filter(a => a.status === 'Pending')
  if (activeActivityStatusTab.value === 'inprogress') return filtered.filter(a => a.status === 'In Progress')
  if (activeActivityStatusTab.value === 'completed') return filtered.filter(a => a.status === 'Completed')
  
  return filtered
})

/* ---------- Comments filtering ---------- */
const filteredComments = computed(() => {
  let filtered = []
  
  if (activeCommentTab.value === 'mycomments') {
    filtered = comments.value.filter(comment => comment.user?.id === user.id)
  } else if (activeCommentTab.value === 'new') {
    filtered = comments.value.filter(comment => 
      comment.user?.id !== user.id && 
      !comment.seen_by?.includes(user.id)
    )
  } else if (activeCommentTab.value === 'seen') {
    filtered = comments.value.filter(comment => 
      comment.user?.id !== user.id && 
      comment.seen_by?.includes(user.id)
    )
  }
  
  return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

/* ---------- Weighted progress ---------- */
const calculateWeightedProgress = (tasks = []) => {
  const totalWeight = tasks.reduce((sum, t) => sum + (Number(t.weight) || 0), 0) || 1
  const completedWeight = tasks
    .filter(t => t.status === 'Completed')
    .reduce((sum, t) => sum + (Number(t.weight) || 0), 0)
  return Math.round((completedWeight / totalWeight) * 100)
}

/* ---------- Normalization for Add Modal ---------- */
const normalizedTasksForAdd = computed(() => {
  const existing = (props.project.tasks || []).map(t => ({
    id: t.id,
    title: t.title,
    description: t.description,
    task_type: t.task_type,
    developer_ids: t.developers?.map(d => d.id) || [],
    raw_weight: Number(t.raw_weight || 0),
    source: 'existing',
    status: t.status || 'Pending'
  }))

  const staged = stagedTasks.value.map(t => ({
    id: t.id || null,
    title: t.title,
    description: t.description,
    task_type: t.task_type,
    developer_ids: t.developer_ids || t.assign_to || [],
    raw_weight: Number(t.raw_weight || 0),
    source: 'staged',
    status: t.status || 'Pending'
  }))

  const combined = [...existing, ...staged]
  const totalRaw = combined.reduce((s, it) => s + Number(it.raw_weight || 0), 0)

  if (totalRaw === 0) {
    return combined.map(it => ({ ...it, normalized_weight: 0 }))
  }

  return combined.map(it => ({
    ...it,
    normalized_weight: Number(((Number(it.raw_weight || 0) / totalRaw) * 100).toFixed(2))
  }))
})

const totalNormalizedWeightForAdd = computed(() =>
  normalizedTasksForAdd.value.reduce((s, t) => s + Number(t.normalized_weight || 0), 0).toFixed(2)
)

/* ---------- Add Modal Functions ---------- */
const openAddTaskModal = () => {
  stagedTasks.value = []
  currentNewTask.value = { 
    title: '', 
    description: '', 
    task_type: 'Development', 
    assign_to: [], 
    raw_weight: 1, 
    due_date: '' 
  }
  showAddTaskModal.value = true
}

const addCurrentTaskToList = () => {
  if (!currentNewTask.value.title || !currentNewTask.value.title.trim()) {
    return alert('Task title is required')
  }

  stagedTasks.value.push({
    title: currentNewTask.value.title.trim(),
    description: currentNewTask.value.description || '',
    task_type: currentNewTask.value.task_type || 'Development',
    developer_ids: [...(currentNewTask.value.assign_to || [])],
    raw_weight: Number(currentNewTask.value.raw_weight) || 1,
    due_date: currentNewTask.value.due_date || '',
    status: 'Pending'
  })

  currentNewTask.value = { 
    title: '', 
    description: '', 
    task_type: 'Development', 
    assign_to: [], 
    raw_weight: currentNewTask.value.raw_weight,
    due_date: '' 
  }
}

const removeStagedTask = (index) => {
  stagedTasks.value.splice(index, 1)
}

const updateAllTasksWithNewOnes = () => {
  if (!stagedTasks.value.length) return alert('Please add at least one new task before updating.')

  const existing = (props.project.tasks || []).map(t => ({
    id: t.id,
    title: t.title,
    description: t.description || '',
    task_type: t.task_type || 'Development',
    raw_weight: Number(t.raw_weight || 0),
    developer_ids: t.developers?.map(d => d.id) || [],
    status: t.status
  }))

  const staged = stagedTasks.value.map(t => ({
    id: null,
    title: t.title,
    description: t.description || '',
    task_type: t.task_type || 'Development',
    raw_weight: Number(t.raw_weight || 0),
    developer_ids: t.developer_ids || t.assign_to || [],
    status: 'Pending'
  }))

  const combined = [...existing, ...staged]
  const totalRaw = combined.reduce((s, it) => s + Number(it.raw_weight || 0), 0)

  const payloadTasks = combined.map(it => ({
    id: it.id || null,
    title: it.title,
    description: it.description || '',
    task_type: it.task_type || 'Development',
    raw_weight: Number(it.raw_weight || 0),
    weight: totalRaw === 0 ? 0 : Number(((Number(it.raw_weight || 0) / totalRaw) * 100).toFixed(2)),
    developer_ids: it.developer_ids || [],
    status: it.status
  }))

  console.log('🔄 Sending tasks to backend:', payloadTasks)

  router.patch(`/projects/${props.project.id}/tasks/bulk-update`, { tasks: payloadTasks }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showAddTaskModal.value = false
      stagedTasks.value = []
      console.log('✅ Tasks created successfully!')
    },
    onError: (errors) => {
      console.error('❌ Backend error:', errors)
    }
  })
}

/* ---------- Edit Modal Functions ---------- */
const openEditTaskModal = () => {
  editTasks.value = (props.project.tasks || [])
    .filter(t => t.status !== 'Completed')
    .map(t => ({
      id: t.id,
      title: t.title,
      description: t.description || '',
      task_type: t.task_type,
      raw_weight: Number(t.raw_weight || 1),
      developer_ids: t.developers?.map(d => d.id) || [],
      status: t.status
    }))
  editingTaskIndex.value = null
  showEditTaskModal.value = true
}

const startEditingTask = (index) => {
  editingTaskIndex.value = index
  const t = editTasks.value[index]
  currentNewTask.value = {
    title: t.title,
    description: t.description,
    task_type: t.task_type,
    assign_to: [...(t.developer_ids || [])],
    raw_weight: Number(t.raw_weight || 1),
    due_date: t.due_date || ''
  }
}

const saveEditedTask = () => {
  if (editingTaskIndex.value === null) return
  if (!currentNewTask.value.title || !currentNewTask.value.title.trim()) return alert('Task title is required')
  
  editTasks.value[editingTaskIndex.value] = {
    ...editTasks.value[editingTaskIndex.value],
    title: currentNewTask.value.title,
    description: currentNewTask.value.description,
    task_type: currentNewTask.value.task_type,
    developer_ids: [...currentNewTask.value.assign_to],
    raw_weight: Number(currentNewTask.value.raw_weight || 1)
  }
  
  editingTaskIndex.value = null
  currentNewTask.value = { title: '', description: '', task_type: 'Development', assign_to: [], raw_weight: 1, due_date: '' }
}

const cancelEditing = () => {
  editingTaskIndex.value = null
  currentNewTask.value = { title: '', description: '', task_type: 'Development', assign_to: [], raw_weight: 1, due_date: '' }
}

const removeTaskFromEditList = (index) => {
  editTasks.value.splice(index, 1)
  if (editingTaskIndex.value === index) cancelEditing()
  else if (editingTaskIndex.value > index) editingTaskIndex.value--
}

const normalizedEditTasks = computed(() => {
  const totalRawWeight = editTasks.value.reduce((sum, t) => sum + Number(t.raw_weight || 0), 0)
  if (totalRawWeight === 0) return editTasks.value.map(t => ({ ...t, normalized_weight: 0 }))
  
  return editTasks.value.map(t => ({ 
    ...t, 
    normalized_weight: Number(((t.raw_weight / totalRawWeight) * 100).toFixed(2))
  }))
})

const totalNormalizedWeightForEdit = computed(() => {
  return normalizedEditTasks.value.reduce((sum, t) => sum + Number(t.normalized_weight || 0), 0).toFixed(2)
})

const updateAllTasks = () => {
  if (!editTasks.value.length) return alert('No tasks to update.')
  
  const payloadTasks = normalizedEditTasks.value.map(t => ({
    id: t.id,
    title: t.title,
    description: t.description || '',
    task_type: t.task_type,
    raw_weight: Number(t.raw_weight || 0),
    weight: t.normalized_weight,
    developer_ids: t.developer_ids || [],
    status: t.status || 'Pending'
  }))

  console.log('🔄 Sending updated tasks to backend:', payloadTasks)

  router.patch(`/projects/${props.project.id}/tasks/bulk-update`, { tasks: payloadTasks }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showEditTaskModal.value = false
      editTasks.value = []
      console.log('✅ Tasks updated successfully!')
    },
    onError: (errors) => {
      console.error('❌ Backend error:', errors)
      const errorMsg = errors?.message || 'Failed to update tasks. Check browser console and Laravel logs.'
      alert(`Error: ${errorMsg}`)
    }
  })
}

/* ---------- Task Actions with Permissions ---------- */
const markTaskAsSeen = (taskId) => {
  router.patch(`/projects/${props.project.id}/tasks/${taskId}/status`, { 
    status: 'In Progress' 
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const task = props.project.tasks?.find(t => t.id === taskId)
      if (task) task.status = 'In Progress'
    }
  })
}

const toggleTaskCompletion = (taskId, currentStatus) => {
  const newStatus = currentStatus === 'Completed' ? 'In Progress' : 'Completed'
  router.patch(`/projects/${props.project.id}/tasks/${taskId}/status`, { 
    status: newStatus 
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const task = props.project.tasks?.find(t => t.id === taskId)
      if (task) task.status = newStatus
    }
  })
}

const deleteTask = (taskId) => {
  if (!confirm('Are you sure you want to delete this task?')) return
  router.delete(`/projects/${props.project.id}/tasks/${taskId}`, {
    preserveScroll: true,
    preserveState: true
  })
}

/* ---------- Task Permission Checks ---------- */
const canEditTasks = computed(() => {
  return user.permissions?.includes('edit tasks') || 
         props.project.created_by === user.id
})

const canDeleteTasks = computed(() => {
  return user.permissions?.includes('delete tasks') || 
         props.project.created_by === user.id
})

const canMarkAsSeen = computed(() => {
  return (isAssignedDeveloper.value && hasAccepted.value) || canEditTasks.value
})

const canToggleCompletion = computed(() => {
  return (isAssignedDeveloper.value && hasAccepted.value) || canEditTasks.value
})

const isAssignedToTask = (task) => {
  if (!isAssignedDeveloper.value) return false
  return task.developers?.some(dev => dev.id === user.id) || false
}

/* ---------- Activities ---------- */
const activityTypes = computed(() => props.activity_types || [])

/* ---------- Activity Permission Checks ---------- */
const canCreateActivities = computed(() => {
  return user.permissions?.includes('create activities') || 
         props.project.created_by === user.id
})

const canEditActivity = computed(() => (activity) => {
  if (activity.status === 'Completed') return false
  if (props.project.created_by === user.id) {
    return true
  }
  
  if (user.permissions?.includes('edit activities')) {
    return true
  }
  
  return false
})

const canDeleteActivity = computed(() => (activity) => {
  if (props.project.created_by === user.id) {
    return true
  }
  
  if (user.permissions?.includes('delete activities')) {
    return activity.created_by === user.id
  }
  
  return false
})

const canMarkActivityAsSeen = computed(() => (activity) => {
  if (activity.status !== 'Pending') return false
  
  if (props.project.created_by === user.id) {
    return true
  }
  
  if (user.permissions?.includes('edit activities')) {
    return true
  }
  
  if (isAssignedDeveloper.value && hasAccepted.value) {
    return activity.developers?.some(dev => dev.id === user.id) || false
  }
  
  return false
})

const canToggleActivityCompletion = computed(() => (activity) => {
  if (activity.status === 'Pending') return false
  
  if (props.project.created_by === user.id) {
    return true
  }
  
  if (user.permissions?.includes('edit activities')) {
    return true
  }
  
  if (isAssignedDeveloper.value && hasAccepted.value) {
    return activity.developers?.some(dev => dev.id === user.id) || false
  }
  
  return false
})

const isAssignedToActivity = (activity) => {
  if (!isAssignedDeveloper.value) return false
  return activity.developers?.some(dev => dev.id === user.id) || false
}

/* ---------- Activity Action Functions ---------- */
const markActivityAsSeen = (activityId) => {
  router.patch(`/activities/${activityId}/status`, { 
    status: 'In Progress' 
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const activity = props.project.activities?.find(a => a.id === activityId)
      if (activity) activity.status = 'In Progress'
    }
  })
}

const toggleActivityCompletion = (activityId, currentStatus) => {
  const newStatus = currentStatus === 'Completed' ? 'In Progress' : 'Completed'
  router.patch(`/activities/${activityId}/status`, { 
    status: newStatus 
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const activity = props.project.activities?.find(a => a.id === activityId)
      if (activity) activity.status = newStatus
    }
  })
}

const deleteActivity = (activityId) => {
  if (!confirm('Are you sure you want to delete this activity?')) return
  router.delete(`/activities/${activityId}`, {
    preserveScroll: true,
    preserveState: true
  })
}

/* ---------- Edit Activity Modal Functions ---------- */
const openEditActivityModal = (activity) => {
  editingActivity.value = activity
  editActivityForm.value = {
    title: activity.title,
    description: activity.description || '',
    activity_type_id: activity.activity_type_id,
    due_date: activity.due_date || '',
    developer_ids: activity.developers?.map(d => d.id) || []
  }
  showEditActivityModal.value = true
}

const updateActivity = () => {
  if (!editActivityForm.value.title?.trim()) {
    return alert('Activity title is required')
  }
  if (!editActivityForm.value.activity_type_id) {
    return alert('Please select an activity type')
  }

  router.put(`/activities/${editingActivity.value.id}`, editActivityForm.value, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showEditActivityModal.value = false
      editingActivity.value = null
      editActivityForm.value = {
        title: '',
        description: '',
        activity_type_id: '',
        due_date: '',
        developer_ids: []
      }
    },
    onError: (errors) => {
      console.error('Error updating activity:', errors)
      alert('Failed to update activity. Please check the form and try again.')
    }
  })
}

const openAddActivityModal = () => {
  newActivity.value = {
    title: '',
    description: '',
    activity_type_id: '',
    due_date: '',
    developer_ids: []
  }
  showAddActivityModal.value = true
}

const createActivity = () => {
  if (!newActivity.value.title?.trim()) {
    return alert('Activity title is required')
  }
  if (!newActivity.value.activity_type_id) {
    return alert('Please select an activity type')
  }

  router.post(`/projects/${props.project.id}/activities`, {
    ...newActivity.value,
    project_id: props.project.id
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      showAddActivityModal.value = false
      newActivity.value = {
        title: '',
        description: '',
        activity_type_id: '',
        due_date: '',
        developer_ids: []
      }
    },
    onError: (errors) => {
      console.error('Error creating activity:', errors)
      alert('Failed to create activity. Please check the form and try again.')
    }
  })
}

/* ---------- Comment Functions ---------- */
const detectUrgency = (text = '') => {
  const lower = (text || '').toLowerCase()
  if (lower.includes('urgent') || lower.includes('immediately') || lower.includes('asap') || lower.includes('critical')) return 'Critical'
  if (lower.includes('soon') || lower.includes('important') || lower.includes('issue')) return 'High'
  return 'Normal'
}

const urgencyColor = (urgency) => ({
  Critical: 'text-red-600 dark:text-red-400 font-semibold',
  High: 'text-orange-600 dark:text-orange-400 font-semibold',
  Normal: 'text-gray-600 dark:text-gray-300'
}[urgency] || 'text-gray-600 dark:text-gray-300')

const scrollToTop = () => nextTick(() => {
  if (commentsContainer.value) commentsContainer.value.scrollTop = 0
})

const getDeveloperName = (userId) => {
  const dev = props.project.developers?.find(d => d.id === userId)
  if (dev) return dev.name
  
  if (props.project.created_by === userId) {
    return props.project.creator?.name || 'Project Creator'
  }
  
  const commentAuthor = comments.value.find(c => c.user?.id === userId)?.user
  if (commentAuthor) return commentAuthor.name
  
  if (userId === user.id) return user.name || 'You'
  
  return 'Unknown'
}

/* ---------- Comment Actions ---------- */
const markCommentAsSeen = (comment) => {
  if (comment.seen_by?.includes(user.id)) {
    return;
  }
  
  console.log('Marking comment as seen:', comment.id);
  
  router.post(`/projects/${props.project.id}/comments/${comment.id}/seen`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      console.log('✅ Success - Flash data:', page.props.flash);
      
      const flash = page.props.flash || {};
      if (flash.comment_id === comment.id && flash.seen_by) {
        console.log('✅ Found matching comment in flash data');
        
        const commentIndex = comments.value.findIndex(c => c.id === comment.id);
        if (commentIndex !== -1) {
          comments.value[commentIndex].seen_by = flash.seen_by;
          console.log('✅ Updated comment locally:', comments.value[commentIndex]);
        }
      } else {
        console.log('⚠️ Flash data not found or mismatch, updating locally anyway');
        if (!comment.seen_by) comment.seen_by = [];
        if (!comment.seen_by.includes(user.id)) {
          comment.seen_by = [...comment.seen_by, user.id];
        }
      }
    },
    onError: (errors) => {
      console.error('❌ Error marking comment as seen:', errors);
      if (!comment.seen_by) comment.seen_by = [];
      if (!comment.seen_by.includes(user.id)) {
        comment.seen_by = [...comment.seen_by, user.id];
      }
    }
  });
};

const deleteComment = (commentId) => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  router.delete(`/projects/${props.project.id}/comments/${commentId}`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const index = comments.value.findIndex(c => c.id === commentId)
      if (index > -1) comments.value.splice(index, 1)
    }
  })
}

const startEditingComment = (comment) => {
  comment.is_editing = true
  comment.edit_message = comment.message
}

const saveEditedComment = (comment) => {
  if (!comment.edit_message?.trim()) return alert('Comment cannot be empty')
  
  router.put(`/projects/${props.project.id}/comments/${comment.id}`, { 
    message: comment.edit_message
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      comment.message = comment.edit_message
      comment.urgency = detectUrgency(comment.edit_message)
      comment.is_editing = false
    },
    onError: (errors) => {
      console.error('Error updating comment:', errors)
      alert('Failed to update comment. Please try again.')
    }
  })
}

const cancelEditingComment = (comment) => {
  comment.is_editing = false
  comment.edit_message = comment.message
}

const addComment = async (e) => {
  e.preventDefault()
  if (!newComment.value?.trim()) return alert('Please write a comment.')
  
  try {
    const tempComment = {
      id: 'temp-' + Date.now(),
      message: newComment.value,
      urgency: detectUrgency(newComment.value),
      user: user,
      seen_by: [user.id],
      created_at: new Date().toISOString(),
      is_editing: false,
      edit_message: newComment.value
    }
    
    comments.value.unshift(tempComment)
    
    const commentText = newComment.value
    newComment.value = ''
    
    scrollToTop()
    
    const response = await router.post(`/projects/${props.project.id}/comments`, { 
      message: commentText
    }, {
      preserveScroll: true,
      preserveState: true,
    })
    
    if (page.props.flash?.new_comment) {
      const newCommentData = page.props.flash.new_comment
      
      const tempIndex = comments.value.findIndex(c => c.id === tempComment.id)
      if (tempIndex !== -1) {
        comments.value[tempIndex] = {
          ...newCommentData,
          seen_by: newCommentData.seen_by || [user.id],
          is_editing: false,
          edit_message: newCommentData.message,
          user: newCommentData.user || user
        }
        console.log('✅ New comment added from flash data:', newCommentData)
      }
    } else {
      await router.reload({ 
        only: ['project'], 
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          comments.value = (props.project.comments || [])
            .map(comment => ({
              ...comment,
              seen_by: Array.isArray(comment.seen_by) ? comment.seen_by : [],
              is_editing: false,
              edit_message: comment.message,
              user: comment.user || { id: null, name: 'Unknown' }
            }))
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        }
      })
    }
    
    console.log('✅ Comment added successfully')
    
  } catch (error) {
    console.error('❌ Error adding comment:', error)
    
    const tempIndex = comments.value.findIndex(c => c.id && c.id.startsWith('temp-'))
    if (tempIndex !== -1) {
      comments.value.splice(tempIndex, 1)
    }
    
    alert('Failed to add comment. Please try again.')
  }
}

/* ---------- Other Functions ---------- */
const acceptProject = () => {
  router.post(`/projects/${props.project.id}/accept`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => { if (currentDeveloper.value) currentDeveloper.value.pivot.accepted = true }
  })
}

const declineProject = () => {
  router.post(`/projects/${props.project.id}/decline`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => { if (currentDeveloper.value) currentDeveloper.value.pivot.accepted = false }
  })
}

const closeModals = () => {
  showAddTaskModal.value = false
  showEditTaskModal.value = false
  showAddActivityModal.value = false
  showEditActivityModal.value = false
  editingTaskIndex.value = null
  editingActivity.value = null
  currentNewTask.value = { title: '', description: '', task_type: 'Development', assign_to: [], raw_weight: 1, due_date: '' }
  editActivityForm.value = { title: '', description: '', activity_type_id: '', due_date: '', developer_ids: [] }
  stagedTasks.value = []
  editTasks.value = []
}
</script>

<template>
  <Layout>
    <main class="p-6 overflow-y-auto flex-1">
      <div class="max-w-5xl mx-auto rounded-2xl transition-colors duration-300">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ props.project.title }}</h2>
          </div>
          <Link href="/projects" class="text-teal-600 dark:text-teal-400 hover:underline">← Back</Link>
        </div>

        <!-- Description + Info -->
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300 mb-6">
          <p><strong>Status:</strong> {{ projectStatus }}</p>
          <p><strong>Due Date:</strong> {{ props.project.due_date }}</p>
          <p><strong>Progress:</strong> {{ calculateWeightedProgress(props.project.tasks) }}%</p>
          <p><strong>Client:</strong> {{ props.project.client?.name  }}</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Project Progress</h3>
            <span class="text-2xl font-bold text-teal-600 dark:text-teal-400">
              {{ calculateWeightedProgress(props.project.tasks) }}%
            </span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 h-4 rounded-full overflow-hidden">
            <div class="bg-gradient-to-r from-teal-500 to-emerald-600 h-4 rounded-full transition-all duration-500"
                 :style="{ width: calculateWeightedProgress(props.project.tasks) + '%' }"></div>
          </div>
        </div>
        
        <!-- Project Info Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <!-- Developers Card -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold dark:text-gray-100 mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-6.5a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
              </svg>
              Team Members
            </h3>
            <ul class="space-y-2">
              <li v-for="dev in props.project.developers" :key="dev.id" class="flex items-center justify-between text-gray-700 dark:text-gray-300 py-2 border-b dark:border-gray-700 last:border-0">
                <span>{{ dev.name }}</span>
                <span :class="dev.pivot?.accepted ? 'text-green-600 dark:text-green-400 text-sm' : 'text-yellow-600 dark:text-yellow-400 text-sm'">
                  {{ dev.pivot?.accepted ? '✓ Accepted' : 'Pending' }}
                </span>
              </li>
            </ul>
          </div>

          <!-- Repository Links Card -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold dark:text-gray-100 mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
              </svg>
              Repository Links
            </h3>
            <div v-if="props.project.project_github_links && props.project.project_github_links.length" class="space-y-3">
              <div v-for="link in props.project.project_github_links" :key="link.id" class="flex items-center gap-3">
                <a :href="link.url" target="_blank" rel="noopener noreferrer" 
                   class="text-indigo-600 dark:text-indigo-400 hover:underline break-all text-sm flex items-center gap-2">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                  </svg>
                  {{ link.url.replace('https://github.com/', '') }}
                </a>
              </div>
            </div>
            <p v-else class="text-gray-500 dark:text-gray-400 text-sm">No repository links attached.</p>
          </div>
        </div>

        <!-- VERTICAL TABS LAYOUT -->
        <div class="mb-6">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <!-- Mobile: Stack tabs above content -->
            <div class="flex flex-col">
              <!-- Vertical Tabs - Always vertical, no horizontal scrolling -->
              <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                <nav class="grid grid-cols-2 md:grid-cols-4 gap-2">
                  <button @click="activeMainTab = 'overview'"
                          :class="[
                            'flex flex-col items-center justify-center p-4 rounded-lg font-medium text-sm transition-all duration-300',
                            activeMainTab === 'overview'
                              ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 shadow-sm'
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                          ]">
                    <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Overview</span>
                  </button>
                  
                  <button @click="activeMainTab = 'tasks'"
                          :class="[
                            'flex flex-col items-center justify-center p-4 rounded-lg font-medium text-sm transition-all duration-300',
                            activeMainTab === 'tasks'
                              ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                          ]">
                    <div class="relative">
                      <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      </svg>
                      <span v-if="pendingTasksCount > 0" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">
                        {{ pendingTasksCount }}
                      </span>
                    </div>
                    <span>Tasks</span>
                  </button>
                  
                  <button @click="activeMainTab = 'activities'"
                          :class="[
                            'flex flex-col items-center justify-center p-4 rounded-lg font-medium text-sm transition-all duration-300',
                            activeMainTab === 'activities'
                              ? 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 shadow-sm'
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                          ]">
                    <div class="relative">
                      <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      <span v-if="pendingActivitiesCount > 0" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300">
                        {{ pendingActivitiesCount }}
                      </span>
                    </div>
                    <span>Activities</span>
                  </button>
                  
                  <button @click="activeMainTab = 'comments'"
                          :class="[
                            'flex flex-col items-center justify-center p-4 rounded-lg font-medium text-sm transition-all duration-300',
                            activeMainTab === 'comments'
                              ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 shadow-sm'
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                          ]">
                    <div class="relative">
                      <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                      </svg>
                      <span v-if="newCommentsCount > 0" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-300">
                        {{ newCommentsCount }}
                      </span>
                    </div>
                    <span>Comments</span>
                  </button>
                </nav>
              </div>

              <!-- Tab Content Area -->
              <div class="flex-1">
                <div class="p-6">
                  <!-- Tab Content (same as before) -->
                  <!-- Overview content -->
                  <div v-if="activeMainTab === 'overview'">
                    <section class="mb-6">
                      <h3 class="text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">Overview</h3>
                      <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        {{ props.project.description }}
                      </p>
                    </section>
                  </div>

                  <!-- Tasks Tab -->
                  <div v-if="activeMainTab === 'tasks'" class="mb-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                      <!-- Task Subtabs -->
                      <div class="w-full sm:w-auto">
                        <div class="inline-flex flex-wrap gap-1 sm:gap-2 border border-gray-200 dark:border-gray-700 p-1 rounded-lg w-full sm:w-auto">
                          <button @click="activeTaskTab = 'pending'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeTaskTab === 'pending'
                                      ? 'bg-blue-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            Pending ({{ pendingTasksCount }})
                          </button>
                          <button @click="activeTaskTab = 'inprogress'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeTaskTab === 'inprogress'
                                      ? 'bg-yellow-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            In Progress ({{ (props.project.tasks || []).filter(t => t.status === 'In Progress').length }})
                          </button>
                          <button @click="activeTaskTab = 'completed'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeTaskTab === 'completed'
                                      ? 'bg-green-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            Completed ({{ (props.project.tasks || []).filter(t => t.status === 'Completed').length }})
                          </button>
                        </div>
                      </div>

                      <div class="flex flex-wrap gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <button v-if="canEditTasks" @click="openAddTaskModal" class="flex-1 sm:flex-none bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-3 py-2 rounded-lg text-sm">
                          <span class="hidden sm:inline">+ Add Task</span>
                          <span class="sm:hidden">+ Add</span>
                        </button>
                        <button v-if="canEditTasks" @click="openEditTaskModal" class="flex-1 sm:flex-none bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-sm">
                          <span class="hidden sm:inline">Edit All Tasks</span>
                          <span class="sm:hidden">Edit All</span>
                        </button>
                      </div>
                    </div>

                    <!-- tasks list -->
                    <div class="space-y-4">
                      <div v-for="task in filteredTasks" :key="task.id"
                           class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg hover:shadow transition bg-white dark:bg-gray-800">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                          <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                              <h4 class="font-medium text-gray-800 dark:text-gray-200">{{ task.title }}</h4>
                              <span class="text-xs px-2 py-1 rounded capitalize self-start sm:self-center"
                                    :class="{
                                      'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': task.status === 'Pending',
                                      'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200': task.status === 'In Progress',
                                      'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': task.status === 'Completed'
                                    }">
                                {{ task.status }}
                              </span>
                            </div>

                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">{{ task.description }}</p>

                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                              <p><strong>Type:</strong> {{ task.task_type }} | <strong>Weight:</strong> {{ task.weight }}%</p>
                              <p>
                                <strong>Assigned to:</strong>
                                <span v-if="task.developers?.length">{{ task.developers.map(d=>d.name).join(', ') }}</span>
                                <span v-else class="text-gray-400">Not assigned</span>
                              </p>
                              <p v-if="isAssignedToTask(task)" class="text-green-600 dark:text-green-400">
                                ✓ Assigned to you
                              </p>
                            </div>
                          </div>

                          <div class="flex flex-row md:flex-col flex-wrap gap-2 md:gap-2 md:ml-4 md:w-auto w-full">
                            <button v-if="task.status === 'Pending' && canMarkAsSeen && (isAssignedToTask(task) || canEditTasks)" 
                                    @click="markTaskAsSeen(task.id)"
                                    class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                              <span class="hidden sm:inline">Mark as Seen</span>
                              <span class="sm:hidden">Seen</span>
                            </button>

                            <div v-if="(task.status === 'In Progress' || task.status === 'Completed') && canToggleCompletion && (isAssignedToTask(task) || canEditTasks)"
                                 class="flex items-center justify-between md:justify-start space-x-2 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded flex-1 md:flex-none">
                              <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Complete:</span>
                              <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       :checked="task.status === 'Completed'"
                                       @change="toggleTaskCompletion(task.id, task.status)"
                                       class="sr-only peer">
                                <div class="w-9 h-5 md:w-11 md:h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 md:after:h-5 md:after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                              </label>
                            </div>

                            <button v-if="canDeleteTasks" 
                                    @click="deleteTask(task.id)" 
                                    class="flex-1 md:flex-none bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                              <span class="hidden sm:inline">Delete</span>
                              <span class="sm:hidden">Delete</span>
                            </button>
                          </div>
                        </div>
                      </div>

                      <p v-if="!filteredTasks.length" class="text-gray-500 dark:text-gray-400 text-center py-8">
                        No tasks in {{ activeTaskTab }} status.
                      </p>
                    </div>
                  </div>

                  <!-- Activities Tab -->
                  <div v-if="activeMainTab === 'activities'" class="mb-8">
                    <!-- Activity Type Tabs -->
                    <div class="mb-6">
                      <div class="inline-flex flex-wrap gap-2">
                        <button @click="activeActivityTypeTab = ''"
                                :class="[
                                  'px-3 py-2 text-sm rounded-lg font-medium transition-all',
                                  activeActivityTypeTab === ''
                                    ? 'bg-purple-500 text-white shadow'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                ]">
                          All Types
                        </button>
                        <button v-for="type in activityTypes" :key="type.id"
                                @click="activeActivityTypeTab = type.id"
                                :class="[
                                  'px-3 py-2 text-sm rounded-lg font-medium transition-all truncate',
                                  activeActivityTypeTab === type.id
                                    ? 'bg-purple-500 text-white shadow'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                ]">
                          {{ type.name }}
                        </button>
                      </div>
                    </div>

                    <!-- Activity Status Tabs -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                      <div class="w-full sm:w-auto">
                        <div class="inline-flex flex-wrap gap-1 sm:gap-2 border border-gray-200 dark:border-gray-700 p-1 rounded-lg w-full sm:w-auto">
                          <button @click="activeActivityStatusTab = 'pending'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeActivityStatusTab === 'pending'
                                      ? 'bg-blue-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            Pending ({{ pendingActivitiesCount }})
                          </button>
                          <button @click="activeActivityStatusTab = 'inprogress'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeActivityStatusTab === 'inprogress'
                                      ? 'bg-yellow-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            In Progress
                          </button>
                          <button @click="activeActivityStatusTab = 'completed'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeActivityStatusTab === 'completed'
                                      ? 'bg-green-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            Completed
                          </button>
                        </div>
                      </div>

                      <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <button v-if="canCreateActivities" @click="openAddActivityModal" class="flex-1 sm:flex-none bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-3 py-2 rounded-lg text-sm">
                          <span class="hidden sm:inline">+ Add Activity</span>
                          <span class="sm:hidden">+ Add</span>
                        </button>
                      </div>
                    </div>

                    <!-- Activities list -->
                    <div class="space-y-4">
                      <div v-for="activity in filteredActivities" :key="activity.id"
                           class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg hover:shadow transition bg-white dark:bg-gray-800">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                          <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                              <h4 class="font-medium text-gray-800 dark:text-gray-200">{{ activity.title }}</h4>
                              <div class="flex items-center gap-2 self-start sm:self-center">
                                <span class="text-xs px-2 py-1 rounded bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 truncate">
                                  {{ activityTypes.find(t => t.id === activity.activity_type_id)?.name || 'Uncategorized' }}
                                </span>
                                <span class="text-xs px-2 py-1 rounded capitalize"
                                      :class="{
                                        'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': activity.status === 'Pending',
                                        'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200': activity.status === 'In Progress',
                                        'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': activity.status === 'Completed'
                                      }">
                                  {{ activity.status }}
                                </span>
                              </div>
                            </div>

                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">{{ activity.description }}</p>

                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                              <p><strong>Due Date:</strong> {{ activity.due_date ? new Date(activity.due_date).toLocaleDateString() : 'Not set' }}</p>
                              <p>
                                <strong>Assigned to:</strong>
                                <span v-if="activity.developers?.length">
                                  {{ activity.developers.map(d => d.name).join(', ') }}
                                  <span v-if="activity.developers.some(d => d.pivot?.accepted)" class="text-green-600 dark:text-green-400 ml-1">
                                    ({{ activity.developers.filter(d => d.pivot?.accepted).length }}/{{ activity.developers.length }} seen)
                                  </span>
                                </span>
                                <span v-else class="text-gray-400">Not assigned</span>
                              </p>
                              <p v-if="isAssignedToActivity(activity)" class="text-green-600 dark:text-green-400">
                                ✓ Assigned to you
                                <span v-if="activity.developers?.find(d => d.id === user.id)?.pivot?.accepted" class="ml-1">
                                  (Seen)
                                </span>
                              </p>
                            </div>
                          </div>

                          <div class="flex flex-row md:flex-col flex-wrap gap-2 md:gap-2 md:ml-4 md:w-auto w-full">
                            <button v-if="canEditActivity(activity)" 
                                    @click="openEditActivityModal(activity)"
                                    class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                              <span class="hidden sm:inline">Edit</span>
                              <span class="sm:hidden">Edit</span>
                            </button>

                            <button v-if="canMarkActivityAsSeen(activity)" 
                                    @click="markActivityAsSeen(activity.id)"
                                    class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                              <span class="hidden sm:inline">Mark as Seen</span>
                              <span class="sm:hidden">Seen</span>
                            </button>

                            <div v-if="canToggleActivityCompletion(activity)"
                                 class="flex items-center justify-between md:justify-start space-x-2 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded flex-1 md:flex-none">
                              <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Complete:</span>
                              <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       :checked="activity.status === 'Completed'"
                                       @change="toggleActivityCompletion(activity.id, activity.status)"
                                       class="sr-only peer">
                                <div class="w-9 h-5 md:w-11 md:h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 md:after:h-5 md:after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                              </label>
                            </div>

                            <button v-if="canDeleteActivity(activity)" 
                                    @click="deleteActivity(activity.id)" 
                                    class="flex-1 md:flex-none bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                              <span class="hidden sm:inline">Delete</span>
                              <span class="sm:hidden">Delete</span>
                            </button>
                          </div>
                        </div>
                      </div>

                      <p v-if="!filteredActivities.length" class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <span v-if="isAssignedDeveloper && hasAccepted">
                          No activities assigned to you in {{ activeActivityStatusTab }} status{{ activeActivityTypeTab ? ` for ${activityTypes.find(t => t.id === activeActivityTypeTab)?.name || 'this type'}` : '' }}.
                        </span>
                        <span v-else>
                          No activities in {{ activeActivityStatusTab }} status{{ activeActivityTypeTab ? ` for ${activityTypes.find(t => t.id === activeActivityTypeTab)?.name || 'this type'}` : '' }}.
                        </span>
                      </p>
                    </div>
                  </div>

                  <!-- Comments Tab -->
                  <div v-if="activeMainTab === 'comments'" class="mb-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                      <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Comments</h3>
                      <div class="w-full sm:w-auto">
                        <div class="inline-flex flex-wrap gap-1 sm:gap-2 border border-gray-200 dark:border-gray-700 p-1 rounded-lg w-full sm:w-auto">
                          <button @click="activeCommentTab = 'mycomments'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeCommentTab === 'mycomments'
                                      ? 'bg-indigo-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            My Comments ({{ myCommentsCount }})
                          </button>
                          <button @click="activeCommentTab = 'new'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeCommentTab === 'new'
                                      ? 'bg-green-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            New ({{ newCommentsCount }})
                          </button>
                          <button @click="activeCommentTab = 'seen'"
                                  :class="[
                                    'flex-1 sm:flex-none px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-colors',
                                    activeCommentTab === 'seen'
                                      ? 'bg-gray-500 text-white shadow'
                                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                                  ]">
                            Seen ({{ seenCommentsCount }})
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Add comment form -->
                    <form @submit.prevent="addComment" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-6 border dark:border-gray-700">
                      <h4 class="font-medium mb-2 text-gray-800 dark:text-gray-200">Add a Comment</h4>
                      <textarea v-model="newComment" placeholder="Write your comment..." 
                                class="w-full border dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded p-2 mb-3 resize-none"
                                rows="3"></textarea>
                      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Urgency will be auto-detected from keywords</p>
                          <p class="text-xs text-gray-500 dark:text-gray-500">
                            Keywords: "urgent", "immediately", "asap", "critical", "soon", "important", "issue"
                          </p>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mt-2 sm:mt-0">Submit</button>
                      </div>
                    </form>

                    <!-- Comments list -->
                    <div ref="commentsContainer" class="max-h-96 overflow-y-auto border dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                      <div v-if="filteredComments.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="comment in filteredComments" :key="comment.id" 
                             :class="[
                               'p-4 transition-all duration-200',
                               comment.user?.id === user.id 
                                 ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' 
                                 : (comment.seen_by?.includes(user.id) 
                                    ? 'bg-white dark:bg-gray-800' 
                                    : 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500')
                             ]">
                          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-3">
                            <div class="flex items-start gap-3">
                              <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ comment.user?.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                  {{ new Date(comment.created_at).toLocaleString() }}
                                </p>
                              </div>
                              <span :class="[
                                'px-2 py-1 text-xs rounded-full self-start',
                                comment.urgency === 'Critical' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' :
                                comment.urgency === 'High' ? 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200' :
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                              ]">
                                {{ comment.urgency }}
                              </span>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                              <template v-if="activeCommentTab === 'mycomments'">
                                <button v-if="!comment.is_editing"
                                        @click="startEditingComment(comment)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                                  Edit
                                </button>
                                <button v-if="comment.is_editing"
                                        @click="saveEditedComment(comment)"
                                        class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors">
                                  Save
                                </button>
                                <button v-if="comment.is_editing"
                                        @click="cancelEditingComment(comment)"
                                        class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                  Cancel
                                </button>
                                <button v-if="!comment.is_editing"
                                        @click="deleteComment(comment.id)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                                  Delete
                                </button>
                              </template>
                              
                              <button v-if="activeCommentTab === 'new' && !comment.seen_by?.includes(user.id) && comment.user?.id !== user.id"
                                      @click="markCommentAsSeen(comment)"
                                      class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                <span class="hidden sm:inline">Mark as Seen</span>
                                <span class="sm:hidden">Seen</span>
                              </button>
                              
                              <button v-if="props.project.created_by === user.id && comment.user?.id !== user.id"
                                      @click="deleteComment(comment.id)"
                                      class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs sm:text-sm font-medium px-2 sm:px-3 py-1 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                                Delete
                              </button>
                            </div>
                          </div>
                          
                          <div v-if="comment.is_editing" class="mb-3">
                            <textarea v-model="comment.edit_message" 
                                      class="w-full border dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded p-2 mb-2 resize-none"
                                      rows="3"></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                              Urgency will be re-detected from updated text
                            </p>
                          </div>
                          <p v-else class="text-gray-800 dark:text-gray-100 whitespace-pre-wrap">{{ comment.message }}</p>
                          
                          <div v-if="comment.user?.id === user.id && comment.seen_by && comment.seen_by.length > 1" 
                               class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                              Seen by {{ comment.seen_by.length - 1 }} {{ comment.seen_by.length - 1 === 1 ? 'person' : 'people' }}:
                            </p>
                            <div class="flex flex-wrap gap-1">
                              <span v-for="userId in comment.seen_by.filter(id => id !== user.id)" :key="userId"
                                    class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                {{ getDeveloperName(userId) }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div v-else class="p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">
                          {{
                            activeCommentTab === 'mycomments' ? 'No comments from you yet.' :
                            activeCommentTab === 'new' ? 'No new comments.' : 'No seen comments.'
                          }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        
        <!-- Add Activity Modal -->
        <div v-if="showAddActivityModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Add New Activity</h3>

            <form @submit.prevent="createActivity" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                <input v-model="newActivity.title" type="text" required 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea v-model="newActivity.description" rows="3"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white"></textarea>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity Type *</label>
                  <select v-model="newActivity.activity_type_id" required
                          class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white">
                    <option value="">Select Type</option>
                    <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                      {{type.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                  <input v-model="newActivity.due_date" type="date"
                         class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign Developers</label>
                <div v-if="availableDevelopers.length" class="space-y-1 max-h-32 overflow-y-auto p-2 border rounded dark:border-gray-600">
                  <div v-if="isAssignedDeveloper && hasAccepted && !canCreateActivities" class="flex items-center gap-2">
                    <input type="checkbox" :value="user.id" v-model="newActivity.developer_ids" 
                           class="rounded border-gray-300 dark:border-gray-600" />
                    <span class="dark:text-gray-300 text-sm">{{ user.name }} (You)</span>
                  </div>
                  <div v-else v-for="dev in availableDevelopers" :key="dev.id" class="flex items-center gap-2">
                    <input type="checkbox" :value="dev.id" v-model="newActivity.developer_ids" 
                           class="rounded border-gray-300 dark:border-gray-600" />
                    <span class="dark:text-gray-300 text-sm">{{ dev.name }}</span>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">No developers available</p>
              </div>

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeModals" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                  Cancel
                </button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                  Create Activity
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit Activity Modal -->
        <div v-if="showEditActivityModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Edit Activity</h3>

            <form @submit.prevent="updateActivity" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                <input v-model="editActivityForm.title" type="text" required 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea v-model="editActivityForm.description" rows="3"
                          class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white"></textarea>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Activity Type *</label>
                  <select v-model="editActivityForm.activity_type_id" required
                          class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white">
                    <option value="">Select Type</option>
                    <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                      {{type.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                  <input v-model="editActivityForm.due_date" type="date"
                         class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign Developers</label>
                <div v-if="availableDevelopers.length" class="space-y-1 max-h-32 overflow-y-auto p-2 border rounded dark:border-gray-600">
                  <div v-if="isAssignedDeveloper && hasAccepted && props.project.created_by !== user.id" class="flex items-center gap-2">
                    <input type="checkbox" :value="user.id" v-model="editActivityForm.developer_ids" 
                           class="rounded border-gray-300 dark:border-gray-600" />
                    <span class="dark:text-gray-300 text-sm">{{ user.name }} (You)</span>
                  </div>
                  <div v-else v-for="dev in availableDevelopers" :key="dev.id" class="flex items-center gap-2">
                    <input type="checkbox" :value="dev.id" v-model="editActivityForm.developer_ids" 
                           class="rounded border-gray-300 dark:border-gray-600" />
                    <span class="dark:text-gray-300 text-sm">{{ dev.name }}</span>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">No developers available</p>
              </div>

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeModals" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                  Cancel
                </button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                  Update Activity
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Add Task Modal -->
        <div v-if="showAddTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Add New Tasks</h3>

            <div class="mb-6">
              <div v-if="normalizedTasksForAdd.length" class="space-y-2 mb-3">
                <div v-for="(t, i) in normalizedTasksForAdd.filter(t => t.source === 'staged')" 
                     :key="'staged-' + i" 
                     class="border rounded p-3 bg-blue-50 dark:bg-blue-900">
                  <div class="flex justify-between items-center mb-2">
                    <span class="dark:text-gray-200 font-medium">{{ t.title || 'Untitled' }} ({{ t.task_type || 'Task' }})</span>
                    <div class="flex items-center gap-3">
                      <span class="text-sm dark:text-gray-200">New</span>
                      <button @click="removeStagedTask(i)"
                              class="text-red-500 hover:underline text-sm">
                        Remove
                      </button>
                    </div>
                  </div>
                  <div class="text-sm dark:text-gray-300 space-y-1">
                    <p><strong>Raw Weight:</strong> {{ t.raw_weight }}</p>
                    <p><strong>Normalized Weight:</strong> {{ t.normalized_weight }}%</p>
                    <p>
                      <strong>Assigned Developers:</strong>
                      <span v-if="(t.developer_ids && t.developer_ids.length)">
                        <template v-for="devId in (t.developer_ids || [])">
                          <span class="mr-1">{{ availableDevelopers.find(d => d.id === devId)?.name }}</span>
                        </template>
                      </span>
                      <span v-else>None</span>
                    </p>
                  </div>
                </div>
              </div>

              <p v-if="!normalizedTasksForAdd.length" class="text-gray-500 dark:text-gray-400 text-center py-2">No tasks added yet.</p>

              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                Total Normalized Weight: <strong>{{ totalNormalizedWeightForAdd }}%</strong>
              </p>
            </div>

            <div class="border-t pt-4 mb-6">
              <h4 class="font-medium mb-3 text-gray-800 dark:text-gray-200">Add New Task</h4>
              <div class="space-y-4 bg-gray-50 dark:bg-gray-700 p-4 rounded">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                  <input v-model="currentNewTask.title" type="text" required class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                  <textarea v-model="currentNewTask.description" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Type *</label>
                    <select v-model="currentNewTask.task_type" required class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white">
                       <option value="Gathering">Gathering</option>
                       <option value="Design">Design</option>
                      <option value="Development">Development</option>
                      <option value="Testing">Testing</option>
                      <option value="Deployment">Deployment</option>
                      <option value="Maintenance">Maintenance</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weight (raw; 1-5) *</label>
                    <input v-model.number="currentNewTask.raw_weight" type="number" min="1" max="5" required class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
                    <p class="text-xs text-gray-500 mt-1">Enter raw weight (1 lowest - 5 highest). Normalized % will be computed automatically.</p>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign Developers</label>
                  <div v-if="availableDevelopers.length" class="space-y-1 max-h-32 overflow-y-auto p-2 border rounded dark:border-gray-600">
                    <div v-for="dev in availableDevelopers" :key="dev.id" class="flex items-center gap-2">
                      <input type="checkbox" :value="dev.id" v-model="currentNewTask.assign_to" class="rounded border-gray-300 dark:border-gray-600" />
                      <span class="dark:text-gray-300 text-sm">{{ dev.name }}</span>
                    </div>
                  </div>
                  <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">No developers available</p>
                </div>

                <div class="flex gap-2">
                  <button @click="addCurrentTaskToList" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm" :disabled="!currentNewTask.title.trim()">+ Add to List</button>
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
              <button type="button" @click="closeModals" class="px-4 py-2 text-gray-600 dark:text-gray-400">Cancel</button>
              <button @click="updateAllTasksWithNewOnes" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded" :disabled="stagedTasks.length === 0">
                Create Tasks ({{ stagedTasks.length }} new)
              </button>
            </div>
          </div>
        </div>

        <!-- Edit All Tasks Modal -->
        <div v-if="showEditTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Edit All Tasks</h3>

            <div class="mb-6">
              <label class="block font-medium dark:text-gray-200 mb-2">All Tasks ({{ editTasks.length }} total)</label>

              <div v-if="normalizedEditTasks.length" class="space-y-2 mb-3">
                <div v-for="(t, i) in normalizedEditTasks" :key="t.id" class="border rounded p-3 bg-gray-50 dark:bg-gray-700">
                  <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                      <span class="dark:text-gray-200 font-medium">{{ t.title }}</span>
                      <div class="mt-2 text-sm dark:text-gray-300 space-y-1">
                        <p><strong>Raw Weight:</strong> {{ t.raw_weight }} (1-5 scale)</p>
                        <p><strong>Normalized Weight:</strong> {{ t.normalized_weight }}%</p>
                        <p>
                          <strong>Status:</strong> 
                          <span class="ml-1 capitalize" :class="{
                            'text-blue-600': t.status === 'Pending',
                            'text-yellow-600': t.status === 'In Progress', 
                            'text-green-600': t.status === 'Completed'
                          }">
                            {{ t.status }}
                          </span>
                        </p>
                        <p>
                          <strong>Assigned Developers:</strong>
                          <span v-if="!(t.developer_ids && t.developer_ids.length)">None</span>
                          <span v-else>
                            <span v-for="devId in t.developer_ids" :key="devId" class="ml-1">
                              {{ availableDevelopers.find(d => d.id === devId)?.name }}
                            </span>
                          </span>
                        </p>
                        <p v-if="t.description" class="text-gray-600 dark:text-gray-400">
                          {{ t.description }}
                        </p>
                      </div>
                    </div>

                    <div class="flex space-x-2 ml-4">
                      <button 
                        @click="startEditingTask(i)" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                        :disabled="editingTaskIndex !== null"
                      >
                        Edit
                      </button>
                      <button 
                        @click="removeTaskFromEditList(i)" 
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
                        :disabled="editingTaskIndex !== null"
                      >
                        Remove
                      </button>
                    </div>
                  </div>

                  <div v-if="editingTaskIndex === i" class="mt-4 p-4 bg-white dark:bg-gray-600 rounded border">
                    <h5 class="font-medium mb-3 dark:text-gray-200">Edit Task</h5>
                    <div class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                        <input
                          v-model="currentNewTask.title"
                          type="text"
                          required
                          class="w-full border border-gray-300 dark:border-gray-500 rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                        >
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea
                          v-model="currentNewTask.description"
                          rows="2"
                          class="w-full border border-gray-300 dark:border-gray-500 rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task Type *</label>
                          <select
                            v-model="currentNewTask.task_type"
                            required
                            class="w-full border border-gray-300 dark:border-gray-500 rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                          >
                           <option value="Gathering">Gathering</option>
                            <option value="Design">Design</option>
                            <option value="Development">Development</option>
                            <option value="Testing">Testing</option>
                            <option value="Deployment">Deployment</option>
                            <option value="Maintenance">Maintenance</option>
                          </select>
                        </div>
                        
                        <div>
                          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weight (1-5) *</label>
                          <input
                            v-model.number="currentNewTask.raw_weight"
                            type="number"
                            min="1"
                            max="5"
                            required
                            class="w-full border border-gray-300 dark:border-gray-500 rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                          >
                        </div>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign Developers</label>
                        <div class="space-y-1 max-h-32 overflow-y-auto p-2 border rounded dark:border-gray-500">
                          <div v-for="dev in availableDevelopers" :key="dev.id" class="flex items-center gap-2">
                            <input 
                              type="checkbox" 
                              :value="dev.id" 
                              v-model="currentNewTask.assign_to" 
                              class="rounded border-gray-300 dark:border-gray-500"
                            />
                            <span class="dark:text-gray-300 text-sm">{{ dev.name }}</span>
                          </div>
                        </div>
                      </div>
                      
                      <div class="flex gap-2">
                        <button
                          @click="saveEditedTask"
                          class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                        >
                          Save
                        </button>
                        <button
                          @click="cancelEditing"
                          class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <p v-if="!editTasks.length" class="text-gray-500 dark:text-gray-400 text-center py-4">
                No tasks to edit.
              </p>

              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                Total Normalized Weight: <strong>{{ totalNormalizedWeightForEdit }}%</strong>
              </p>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
              <button
                type="button"
                @click="closeModals"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
              >
                Cancel
              </button>
              <button
                @click="updateAllTasks"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded"
                :disabled="editTasks.length === 0 || editingTaskIndex !== null"
              >
                Update All Tasks ({{ editTasks.length }} total)
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style scoped>
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
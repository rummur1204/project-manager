<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'
import { Users, Shield, Sparkles, X, AlertCircle, Search } from 'lucide-vue-next'

// Local tab state - purely client-side
const tab = ref('users')

// Search state
const searchQuery = ref('')

// Modal states
const showAddUserModal = ref(false)
const showEditUserModal = ref(false)
const showAddRoleModal = ref(false)
const showEditRoleModal = ref(false)
const showAddActivityTypeModal = ref(false)
const showEditActivityTypeModal = ref(false)

// Form data (simple objects)
const userForm = ref({ 
  id: null,
  name: '', 
  email: '', 
  password: '', 
  password_confirmation: '', 
  role: '' 
})

const roleForm = ref({ 
  id: null,
  name: '', 
  permissions: [] 
})

const activityTypeForm = ref({ 
  id: null,
  name: '' 
})

// Props from backend
const page = usePage()
const users = computed(() => page.props.users || [])
const roles = computed(() => page.props.roles || [])
const activityTypes = computed(() => page.props.activityTypes || [])
const allPermissions = computed(() => page.props.allPermissions || [])

// Flash messages
const flash = computed(() => page.props.flash || {})

// Tab switch - CLIENT-SIDE ONLY
const switchTab = (name) => {
  tab.value = name
  // Clear search when switching tabs
  searchQuery.value = ''
  // NO router.get() call here - this is purely client-side
}

// Filtered data based on search
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => 
    user.name.toLowerCase().includes(query) || 
    user.email.toLowerCase().includes(query) ||
    (user.roles?.[0]?.name.toLowerCase().includes(query) || '')
  )
})

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value
  const query = searchQuery.value.toLowerCase()
  return roles.value.filter(role => 
    role.name.toLowerCase().includes(query) ||
    role.permissions?.some(p => p.name.toLowerCase().includes(query))
  )
})

const filteredActivityTypes = computed(() => {
  if (!searchQuery.value) return activityTypes.value
  const query = searchQuery.value.toLowerCase()
  return activityTypes.value.filter(type => 
    type.name.toLowerCase().includes(query)
  )
})

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
}

// ===========================================
// USER MODAL FUNCTIONS
// ===========================================
const openAddUserModal = () => {
  userForm.value = { 
    id: null,
    name: '', 
    email: '', 
    password: '', 
    password_confirmation: '', 
    role: '' 
  }
  showAddUserModal.value = true
}

const openEditUserModal = (user) => {
  userForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    role: user.roles && user.roles.length > 0 ? user.roles[0].name : ''
  }
  showEditUserModal.value = true
}

const closeUserModals = () => {
  showAddUserModal.value = false
  showEditUserModal.value = false
  userForm.value = { 
    id: null,
    name: '', 
    email: '', 
    password: '', 
    password_confirmation: '', 
    role: '' 
  }
}

const submitUser = (e) => {
  e.preventDefault()
  
  const formData = {
    name: userForm.value.name,
    email: userForm.value.email,
    role: userForm.value.role,
  }
  
  if (!userForm.value.id) {
    // Creating new user
    formData.password = userForm.value.password
    formData.password_confirmation = userForm.value.password_confirmation
    
    router.post(route('settings.users.store'), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeUserModals()
      }
    })
  } else {
    // Updating existing user
    if (userForm.value.password) {
      formData.password = userForm.value.password
      formData.password_confirmation = userForm.value.password_confirmation
    }
    
    router.put(route('settings.users.update', userForm.value.id), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeUserModals()
      }
    })
  }
}

// ===========================================
// ROLE MODAL FUNCTIONS
// ===========================================
const openAddRoleModal = () => {
  roleForm.value = { 
    id: null,
    name: '', 
    permissions: [] 
  }
  showAddRoleModal.value = true
}

const openEditRoleModal = (role) => {
  roleForm.value = {
    id: role.id,
    name: role.name,
    permissions: role.permissions ? role.permissions.map(p => p.name) : []
  }
  showEditRoleModal.value = true
}

const closeRoleModals = () => {
  showAddRoleModal.value = false
  showEditRoleModal.value = false
  roleForm.value = { 
    id: null,
    name: '', 
    permissions: [] 
  }
}

const submitRole = (e) => {
  e.preventDefault()
  
  const formData = {
    name: roleForm.value.name,
    permissions: roleForm.value.permissions
  }
  
  if (!roleForm.value.id) {
    router.post(route('settings.roles.store'), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeRoleModals()
      }
    })
  } else {
    router.put(route('settings.roles.update', roleForm.value.id), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeRoleModals()
      }
    })
  }
}

// ===========================================
// ACTIVITY TYPE MODAL FUNCTIONS
// ===========================================
const openAddActivityTypeModal = () => {
  activityTypeForm.value = { 
    id: null,
    name: '' 
  }
  showAddActivityTypeModal.value = true
}

const openEditActivityTypeModal = (activityType) => {
  activityTypeForm.value = {
    id: activityType.id,
    name: activityType.name
  }
  showEditActivityTypeModal.value = true
}

const closeActivityTypeModals = () => {
  showAddActivityTypeModal.value = false
  showEditActivityTypeModal.value = false
  activityTypeForm.value = { 
    id: null,
    name: '' 
  }
}

const submitActivityType = (e) => {
  e.preventDefault()
  
  const formData = {
    name: activityTypeForm.value.name
  }
  
  if (!activityTypeForm.value.id) {
    router.post(route('settings.activity-types.store'), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeActivityTypeModals()
      }
    })
  } else {
    router.put(route('settings.activity-types.update', activityTypeForm.value.id), formData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeActivityTypeModals()
      }
    })
  }
}

// ===========================================
// DELETE FUNCTIONS
// ===========================================
const deleteUser = (userId) => {
  if (confirm('Are you sure you want to delete this user?')) {
    router.delete(route('settings.users.destroy', userId), {
      preserveScroll: true,
      preserveState: true
    })
  }
}

const deleteRole = (roleId) => {
  if (confirm('Are you sure you want to delete this role?')) {
    router.delete(route('settings.roles.destroy', roleId), {
      preserveScroll: true,
      preserveState: true
    })
  }
}

const deleteActivityType = (activityTypeId) => {
  if (confirm('Are you sure you want to delete this activity type?')) {
    router.delete(route('settings.activity-types.destroy', activityTypeId), {
      preserveScroll: true,
      preserveState: true
    })
  }
}

// ===========================================
// HELPER FUNCTIONS
// ===========================================
const selectAllPermissions = () => {
  roleForm.value.permissions = [...allPermissions.value]
}

const clearAllPermissions = () => {
  roleForm.value.permissions = []
}
</script>

<template>
  <Layout>
    <div class="max-w-7xl mx-auto p-4 md:p-6">
      <!-- Flash Messages -->
      <div v-if="flash.success" class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">
              {{ flash.success }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="flash.error" class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <AlertCircle class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">
              {{ flash.error }}
            </p>
          </div>
        </div>
      </div>

      <!-- Tabs - PURELY CLIENT-SIDE -->
      <div class="mb-6">
        <div class="border-b border-teal-200 dark:border-teal-800/30">
          <nav class="-mb-px flex space-x-8">
            <button @click="switchTab('users')"
                    :class="['py-3 px-1 border-b-2 font-medium text-sm transition-all duration-200', tab === 'users' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-teal-700 dark:hover:text-teal-300 hover:border-teal-300 dark:hover:border-teal-600']">
              <div class="flex items-center space-x-2">
                <Users class="h-4 w-4" />
                <span>Users</span>
              </div>
            </button>
            <button @click="switchTab('roles')"
                    :class="['py-3 px-1 border-b-2 font-medium text-sm transition-all duration-200', tab === 'roles' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-teal-700 dark:hover:text-teal-300 hover:border-teal-300 dark:hover:border-teal-600']">
              <div class="flex items-center space-x-2">
                <Shield class="h-4 w-4" />
                <span>Roles</span>
              </div>
            </button>
            <button @click="switchTab('activitytypes')"
                    :class="['py-3 px-1 border-b-2 font-medium text-sm transition-all duration-200', tab === 'activitytypes' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-teal-700 dark:hover:text-teal-300 hover:border-teal-300 dark:hover:border-teal-600']">
              <div class="flex items-center space-x-2">
                <Sparkles class="h-4 w-4" />
                <span>Activity Types</span>
              </div>
            </button>
          </nav>
        </div>
      </div>

      <!-- Search Bar - Centered beneath tabs -->
      <div class="mb-8">
        <div class="flex justify-center">
          <div class="w-full max-w-md">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input v-model="searchQuery" 
                     :placeholder="`Search ${tab === 'users' ? 'users' : tab === 'roles' ? 'roles' : 'activity types'}...`"
                     class="w-full pl-10 pr-10 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200" />
              <button v-if="searchQuery" @click="clearSearch" 
                      class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <X class="h-4 w-4" />
              </button>
            </div>
            <p v-if="searchQuery" class="text-xs text-center text-gray-500 dark:text-gray-400 mt-2">
              Found 
              <span v-if="tab === 'users'" class="text-teal-600 dark:text-teal-400 font-medium">
                {{ filteredUsers.length }} user{{ filteredUsers.length !== 1 ? 's' : '' }}
              </span>
              <span v-if="tab === 'roles'" class="text-teal-600 dark:text-teal-400 font-medium">
                {{ filteredRoles.length }} role{{ filteredRoles.length !== 1 ? 's' : '' }}
              </span>
              <span v-if="tab === 'activitytypes'" class="text-teal-600 dark:text-teal-400 font-medium">
                {{ filteredActivityTypes.length }} activity type{{ filteredActivityTypes.length !== 1 ? 's' : '' }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- USERS TAB -->
      <div v-if="tab === 'users'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold dark:text-gray-100">Manage Users</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Create, edit, and delete user accounts
              </p>
            </div>
            <button @click="openAddUserModal" 
                    class="inline-flex items-center justify-center bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
              <Users class="h-4 w-4 mr-2" />
              New User
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="u in filteredUsers" :key="u.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                <td class="py-4 px-4">
                  <div class="text-sm font-medium dark:text-gray-300">{{ u.name }}</div>
                </td>
                <td class="py-4 px-4">
                  <div class="text-sm text-gray-600 dark:text-gray-400">{{ u.email }}</div>
                </td>
                <td class="py-4 px-4">
                  <span v-if="u.roles && u.roles.length > 0" 
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                    {{ u.roles[0].name }}
                  </span>
                  <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic">
                    No Role
                  </span>
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <button @click="openEditUserModal(u)"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                      Edit
                    </button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button @click="deleteUser(u.id)"
                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="4" class="py-12 px-4 text-center">
                  <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                    <Users class="h-12 w-12 mb-3 opacity-50" />
                    <p class="text-sm">{{ searchQuery ? 'No matching users found' : 'No users found' }}</p>
                    <p v-if="!searchQuery" class="text-xs mt-1">Click "New User" to add your first user</p>
                    <button v-else @click="clearSearch" 
                            class="mt-2 text-teal-600 dark:text-teal-400 hover:underline text-sm">
                      Clear search
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ROLES TAB -->
      <div v-if="tab === 'roles'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold dark:text-gray-100">Manage Roles</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Create and manage roles with specific permissions
              </p>
            </div>
            <button @click="openAddRoleModal" 
                    class="inline-flex items-center justify-center bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
              <Shield class="h-4 w-4 mr-2" />
              New Role
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role Name</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissions</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="role in filteredRoles" :key="role.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                <td class="py-4 px-4">
                  <div class="text-sm font-medium dark:text-gray-300">{{ role.name }}</div>
                </td>
                <td class="py-4 px-4">
                  <div v-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="permission in role.permissions.slice(0, 3)" :key="permission.id"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                      {{ permission.name }}
                    </span>
                    <span v-if="role.permissions.length > 3" 
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-400">
                      +{{ role.permissions.length - 3 }} more
                    </span>
                  </div>
                  <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic">
                    No permissions
                  </span>
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <button @click="openEditRoleModal(role)"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                      Edit
                    </button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button @click="deleteRole(role.id)"
                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredRoles.length === 0">
                <td colspan="3" class="py-12 px-4 text-center">
                  <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                    <Shield class="h-12 w-12 mb-3 opacity-50" />
                    <p class="text-sm">{{ searchQuery ? 'No matching roles found' : 'No roles found' }}</p>
                    <p v-if="!searchQuery" class="text-xs mt-1">Click "New Role" to add your first role</p>
                    <button v-else @click="clearSearch" 
                            class="mt-2 text-teal-600 dark:text-teal-400 hover:underline text-sm">
                      Clear search
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ACTIVITY TYPES TAB -->
      <div v-if="tab === 'activitytypes'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold dark:text-gray-100">Activity Types</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage different types of activities for your projects
              </p>
            </div>
            <button @click="openAddActivityTypeModal" 
                    class="inline-flex items-center justify-center bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
              <Sparkles class="h-4 w-4 mr-2" />
              New Activity Type
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="at in filteredActivityTypes" :key="at.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                <td class="py-4 px-4">
                  <div class="text-sm font-medium dark:text-gray-300">{{ at.name }}</div>
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <button @click="openEditActivityTypeModal(at)"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium">
                      Edit
                    </button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button @click="deleteActivityType(at.id)"
                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredActivityTypes.length === 0">
                <td colspan="2" class="py-12 px-4 text-center">
                  <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                    <Sparkles class="h-12 w-12 mb-3 opacity-50" />
                    <p class="text-sm">{{ searchQuery ? 'No matching activity types found' : 'No activity types found' }}</p>
                    <p v-if="!searchQuery" class="text-xs mt-1">Click "New Activity Type" to add your first type</p>
                    <button v-else @click="clearSearch" 
                            class="mt-2 text-teal-600 dark:text-teal-400 hover:underline text-sm">
                      Clear search
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- =========================================== -->
    <!-- MODALS -->
    <!-- =========================================== -->

    <!-- Add/Edit User Modal -->
    <div v-if="showAddUserModal || showEditUserModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
         @click.self="closeUserModals"
         @keydown.escape="closeUserModals">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ userForm.id ? 'Edit User' : 'Create New User' }}
          </h3>
          <button @click="closeUserModals" 
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitUser" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
            <input v-model="userForm.name" type="text" required 
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
            <input v-model="userForm.email" type="email" required 
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
            <select v-model="userForm.role" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white">
              <option value="">Select a role</option>
              <option v-for="role in roles" :key="role.id" :value="role.name">
                {{ role.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Password {{ userForm.id ? '(Optional)' : '*' }}
            </label>
            <input v-model="userForm.password" type="password" 
                   :required="!userForm.id"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
            <p v-if="userForm.id" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Leave empty to keep current password
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Confirm Password {{ userForm.id ? '(Optional)' : '*' }}
            </label>
            <input v-model="userForm.password_confirmation" type="password" 
                   :required="!userForm.id"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeUserModals" 
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
              Cancel
            </button>
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
              {{ userForm.id ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Role Modal -->
    <div v-if="showAddRoleModal || showEditRoleModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
         @click.self="closeRoleModals"
         @keydown.escape="closeRoleModals">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ roleForm.id ? 'Edit Role' : 'Create New Role' }}
          </h3>
          <button @click="closeRoleModals" 
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitRole" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name *</label>
            <input v-model="roleForm.name" type="text" required 
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permissions</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2 max-h-60 overflow-y-auto p-3 border rounded-md dark:border-gray-600 bg-gray-50 dark:bg-gray-900">
              <div v-for="permission in allPermissions" :key="permission" 
                   class="flex items-center space-x-2">
                <input type="checkbox" :id="`perm_${permission}`" :value="permission" 
                       v-model="roleForm.permissions"
                       class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-400" />
                <label :for="`perm_${permission}`" 
                       class="text-sm dark:text-gray-300 truncate cursor-pointer select-none">
                  {{ permission }}
                </label>
              </div>
            </div>
            <div class="flex justify-between items-center mt-2">
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Selected: {{ roleForm.permissions.length }} permission(s)
              </p>
              <div class="space-x-2">
                <button type="button" @click="selectAllPermissions" 
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                  Select All
                </button>
                <button type="button" @click="clearAllPermissions" 
                        class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                  Clear All
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeRoleModals" 
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
              Cancel
            </button>
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
              {{ roleForm.id ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add/Edit Activity Type Modal -->
    <div v-if="showAddActivityTypeModal || showEditActivityTypeModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
         @click.self="closeActivityTypeModals"
         @keydown.escape="closeActivityTypeModals">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ activityTypeForm.id ? 'Edit Activity Type' : 'Create New Activity Type' }}
          </h3>
          <button @click="closeActivityTypeModals" 
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitActivityType" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
            <input v-model="activityTypeForm.name" type="text" required 
                   class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 dark:bg-gray-600 dark:text-white" />
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeActivityTypeModals" 
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
              Cancel
            </button>
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
              {{ activityTypeForm.id ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Layout>
</template>
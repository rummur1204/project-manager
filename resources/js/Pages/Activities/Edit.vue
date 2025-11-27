<script setup>
import Layout from '../Dashboard/Layout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    activity: Object,
    activityTypes: Array,
    developers: Array,
    projects: Array,
})

const form = useForm({
    title: props.activity.title,
    description: props.activity.description,
    activity_type_id: props.activity.activity_type_id,
    project_id: props.activity.project_id,
    developer_ids: props.activity.developers.map(d => d.id),
    due_date: props.activity.due_date,
    // Status is removed - will be managed automatically
})

const update = () => {
    form.put(`/activities/${props.activity.id}`)
}
</script>

<template>
    <Layout>
        <div class="p-6 max-w-2xl mx-auto">

            <h1 class="text-2xl font-bold mb-6">Edit Activity</h1>

            <div class="space-y-6 bg-white shadow-lg p-6 rounded-lg">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input 
                        v-model="form.title" 
                        class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea 
                        v-model="form.description" 
                        rows="4"
                        class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                        <select 
                            v-model="form.activity_type_id" 
                            class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="t in props.activityTypes" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.activity_type_id" class="text-red-500 text-sm mt-1">{{ form.errors.activity_type_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
                        <select 
                            v-model="form.project_id" 
                            class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select Project</option>
                            <option v-for="p in props.projects" :value="p.id">{{ p.title }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Developers</label>
                    <select 
                        v-model="form.developer_ids" 
                        multiple 
                        class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-32"
                    >
                        <option v-for="d in props.developers" :value="d.id">{{ d.name }}</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple developers</p>
                    <p v-if="form.errors.developer_ids" class="text-red-500 text-sm mt-1">{{ form.errors.developer_ids }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                    <input 
                        type="date" 
                        v-model="form.due_date" 
                        class="w-full border border-gray-300 p-3 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p v-if="form.errors.due_date" class="text-red-500 text-sm mt-1">{{ form.errors.due_date }}</p>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <Link 
                        href="/activities" 
                        class="px-6 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition"
                    >
                        Cancel
                    </Link>

                    <button 
                        @click="update" 
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Activity' }}
                    </button>
                </div>

            </div>

        </div>
    </Layout>
</template>
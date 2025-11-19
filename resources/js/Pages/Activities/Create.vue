<script setup>
import Layout from '../Dashboard/Layout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    activityTypes: Array,
    developers: Array,
    projects: Array,
})

const form = useForm({
    title: '',
    description: '',
    activity_type_id: '',
    project_id: '',
    developer_ids: [],
    due_date: '',
    status: 'Pending',
})

const save = () => {
    form.post('/activities')
}
</script>

<template>
    <Layout>
        <div class="p-6 max-w-xl mx-auto">

            <h1 class="text-2xl font-bold mb-4">Create Activity</h1>

            <div class="space-y-3 bg-white shadow p-4 rounded">

                <input v-model="form.title" class="w-full border p-2 rounded" placeholder="Title">

                <textarea v-model="form.description" class="w-full border p-2 rounded" placeholder="Description"></textarea>

                <select v-model="form.activity_type_id" class="w-full border p-2 rounded">
                    <option value="">Select Activity Type</option>
                    <option v-for="t in props.activityTypes" :value="t.id">{{ t.name }}</option>
                </select>

                <select v-model="form.project_id" class="w-full border p-2 rounded">
                    <option value="">Select Project</option>
                    <option v-for="p in props.projects" :value="p.id">{{ p.title }}</option>
                </select>

                <select v-model="form.developer_ids" multiple class="w-full border p-2 rounded h-24">
                    <option v-for="d in props.developers" :value="d.id">{{ d.name }}</option>
                </select>

                <input type="date" v-model="form.due_date" class="w-full border p-2 rounded">

                <select v-model="form.status" class="w-full border p-2 rounded">
                    <option>Pending</option>
                    <option>In Progress</option>
                    <option>Completed</option>
                </select>

                <div class="flex justify-between mt-4">
                    <Link href="/activities" class="px-4 py-2 border rounded">Cancel</Link>

                    <button @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">
                        Create
                    </button>
                </div>

            </div>

        </div>
    </Layout>
</template>

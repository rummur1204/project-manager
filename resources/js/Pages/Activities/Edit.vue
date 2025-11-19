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
    status: props.activity.status,
})

const update = () => {
    form.put(`/activities/${props.activity.id}`)
}
</script>

<template>
    <Layout>
        <div class="p-6 max-w-xl mx-auto">

            <h1 class="text-2xl font-bold mb-4">Edit Activity</h1>

            <div class="space-y-3 bg-white shadow p-4 rounded">

                <input v-model="form.title" class="w-full border p-2 rounded">

                <textarea v-model="form.description" class="w-full border p-2 rounded"></textarea>

                <select v-model="form.activity_type_id" class="w-full border p-2 rounded">
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

                    <button @click="update" class="px-4 py-2 bg-blue-600 text-white rounded">
                        Update
                    </button>
                </div>

            </div>

        </div>
    </Layout>
</template>

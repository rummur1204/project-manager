<script setup>
import Layout from '../Dashboard/Layout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    activities: Array,
})
</script>

<template>
    <Layout>
        <div class="p-6">

            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Activities</h1>

                <Link
                    href="/activities/create"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    + Add Activity
                </Link>
            </div>

            <div class="bg-white shadow rounded p-4">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Title</th>
                            <th class="p-2">Type</th>
                            <th class="p-2">Project</th>
                            <th class="p-2">Developers</th>
                            <th class="p-2">Due</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="a in props.activities" :key="a.id" class="border-b">

                            <td class="p-2">{{ a.title }}</td>

                            <td class="p-2">{{ a.type?.name }}</td>

                            <td class="p-2">{{ a.project?.title ?? '-' }}</td>

                            <td class="p-2">
                                <span
                                    v-for="d in a.developers"
                                    :key="d.id"
                                    class="px-2 py-1 bg-gray-200 rounded text-sm mr-1"
                                >
                                    {{ d.name }}
                                </span>
                            </td>

                            <td class="p-2">{{ a.due_date }}</td>

                            <td class="p-2">{{ a.status }}</td>

                            <td class="p-2 flex gap-3">
                                <Link :href="`/activities/${a.id}/edit`" class="text-blue-600">Edit</Link>

                                <Link
                                    :href="`/activities/${a.id}`"
                                    method="delete"
                                    as="button"
                                    class="text-red-600"
                                    onclick="return confirm('Delete activity?')"
                                >
                                    Delete
                                </Link>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </Layout>
</template>

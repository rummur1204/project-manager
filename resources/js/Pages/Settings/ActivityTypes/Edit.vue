<script setup>
import { useForm, router } from '@inertiajs/vue3'
import Layout from '@/Pages/Dashboard/Layout.vue'

const props = defineProps({ activityType: Object })

const form = useForm({
  name: props.activityType.name
})

function submit() {
  form.put(`/settings/activity-types/${props.activityType.id}`)
}
</script>

<template>
    <Layout>
  <div class="p-6">
    <h1 class="text-xl font-bold mb-4">Edit Activity Type</h1>

    <form @submit.prevent="submit">
      <input v-model="form.name"
             class="border p-2 w-full mb-4" />

      <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Update
      </button>
    </form>
  </div>
  </Layout>
</template>

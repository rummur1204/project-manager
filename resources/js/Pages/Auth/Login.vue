<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="text-center mb-6 lg:hidden">
            <ApplicationLogo class="h-12 w-12 mx-auto mb-3" />
            <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-gray-600 mt-1">Sign in to your account</p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    v-model="form.email"
                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    required
                    autofocus
                    placeholder="your@email.com"
                />
                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    v-model="form.password"
                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    required
                    placeholder="••••••••"
                />
                <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        v-model="form.remember"
                        class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                    />
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>

                <Link
                    href="/forgot-password"
                    class="text-sm text-teal-600 hover:text-teal-800 font-medium"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="mt-6">
                <button
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Logging in...</span>
                    <span v-else>Log in</span>
                </button>
            </div>
            
            
        </form>
    </GuestLayout>
</template>
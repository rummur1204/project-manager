<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex justify-center items-center bg-gray-100">

        <!-- WIDER MAIN CONTAINER -->
        <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl flex overflow-hidden">

            <!-- LEFT SIDE - CHANGED TO TEAL -->
            <div class="w-1/2 bg-gradient-to-br from-teal-600 to-emerald-600 text-white flex flex-col justify-center items-center p-14">

                <ApplicationLogo class="h-22 w-32 mb-6 fill-current text-white" />

                <h3 class="text-3xl font-bold tracking-wide text-center">
                    Project Flow
                </h3>

                <p class="mt-4 text-lg opacity-80 text-center">
                    Welcome back, please sign in.
                </p>

            </div>

            <!-- RIGHT SIDE (LOGIN FORM SLOT) -->
            <div class="w-1/2 flex justify-center items-center p-12">
                <div class="w-full max-w-md">
                    <slot />
                </div>
            </div>
        </div>

    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8 sm:px-6 lg:px-8">
        <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl flex flex-col lg:flex-row overflow-hidden">
            
            <!-- LEFT SIDE - TEAL GRADIENT - ALWAYS AT TOP -->
            <div class="lg:w-1/2 bg-gradient-to-br from-teal-600 to-emerald-600 text-white flex flex-col justify-center items-center p-8 md:p-12 lg:p-14"> 
                <!-- Removed order classes -->
                
                <div class="w-full flex flex-col items-center justify-center">
                    <!-- LOCAL LOGO -->
                    <div class="flex justify-center"> 
                        <img 
                            src="/images/logo.png" 
                            alt="ProjectFlow Logo"
                            class="h-20 w-auto md:h-28" 
                        />
                    </div>
                    
                    <h3 class="text-2xl md:text-3xl font-bold tracking-wide text-center"> 
                        ProjectFlow
                    </h3>

                    <p class="mt-3 md:mt-4 text-base md:text-lg opacity-80 text-center">
                        Welcome back, please sign in.
                    </p>
                </div>
            </div>

            <!-- RIGHT SIDE (LOGIN FORM) - ALWAYS BELOW/BESIDE -->
            <div class="lg:w-1/2 flex justify-center items-center p-6 sm:p-8 md:p-10 lg:p-12"> 
                <!-- Removed order classes -->
                <div class="w-full max-w-md">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-indigo-50 via-white via-cyan-50 to-emerald-50 relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute top-1/2 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-400/15 to-cyan-500/15 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="absolute -bottom-40 right-1/4 w-72 h-72 bg-gradient-to-bl from-purple-400/10 to-pink-500/10 rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-gradient-to-r from-indigo-300/10 to-blue-400/10 rounded-full blur-2xl animate-pulse delay-700"></div>
            <div class="absolute bottom-1/3 right-1/3 w-48 h-48 bg-gradient-to-l from-cyan-300/15 to-teal-400/15 rounded-full blur-2xl animate-pulse delay-300"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent opacity-30"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10">
            <slot name="logo" />
        </div>

        <div class="relative z-10 w-full sm:max-w-md mt-6 px-6 py-4 bg-white/70 backdrop-blur-xl shadow-xl ring-1 ring-white/20 border border-white/30 overflow-hidden sm:rounded-2xl">
            <slot />
        </div>
    </div>
</template>

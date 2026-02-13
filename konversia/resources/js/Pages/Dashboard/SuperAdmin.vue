<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentCompanies: Array,
    companiesStats: Array,
});
</script>

<template>
    <AppLayout title="Dashboard - Super Admin">
        <Head title="Dashboard" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Dashboard - Administrador Geral</h2>
                    <p class="text-slate-600 mt-1">Visão geral completa do sistema</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas Gerais -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Total de Empresas</dt>
                                <dd class="text-3xl font-bold text-slate-900">{{ stats.total_companies }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-green-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Empresas Ativas</dt>
                                <dd class="text-3xl font-bold text-emerald-600">{{ stats.active_companies }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Total de Usuários</dt>
                                <dd class="text-3xl font-bold text-slate-900">{{ stats.total_users }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Conversas Pendentes</dt>
                                <dd class="text-3xl font-bold text-amber-600">{{ stats.pending_conversations }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empresas Recentes -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 mb-8 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Empresas Recentes</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200/50">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Empresa</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Números WhatsApp</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Criada em</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/50 divide-y divide-slate-100/50">
                                    <tr v-for="company in recentCompanies" :key="company.id" class="hover:bg-slate-50/50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-semibold text-slate-900">{{ company.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="company.active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'" class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full">
                                                <div :class="company.active ? 'bg-emerald-500' : 'bg-red-500'" class="w-2 h-2 rounded-full mr-2"></div>
                                                {{ company.active ? 'Ativa' : 'Inativa' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">
                                            {{ company.whatsapp_numbers?.length || 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">
                                            {{ new Date(company.created_at).toLocaleDateString('pt-BR') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas por Empresa -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-purple-500 to-pink-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Estatísticas por Empresa</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="company in companiesStats" :key="company.id" class="group relative rounded-2xl bg-gradient-to-br from-slate-50/80 to-white/60 p-6 border border-slate-200/50 hover:border-slate-300/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-slate-900">{{ company.name }}</h4>
                                    <span :class="company.active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'" class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full">
                                        <div :class="company.active ? 'bg-emerald-500' : 'bg-red-500'" class="w-2 h-2 rounded-full mr-2 animate-pulse"></div>
                                        {{ company.active ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600 font-medium">Usuários:</span>
                                        <span class="bg-slate-100 px-3 py-1 rounded-lg font-bold text-slate-900">{{ company.users_count }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600 font-medium">Conversas:</span>
                                        <span class="bg-blue-100 px-3 py-1 rounded-lg font-bold text-blue-900">{{ company.conversations_count }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600 font-medium">Números WhatsApp:</span>
                                        <span class="bg-emerald-100 px-3 py-1 rounded-lg font-bold text-emerald-900">{{ company.whatsapp_numbers_count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


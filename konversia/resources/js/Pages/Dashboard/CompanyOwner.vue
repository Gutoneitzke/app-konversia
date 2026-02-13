<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    company: Object,
    stats: Object,
    conversationsByDepartment: Array,
    recentConversations: Array,
    messagesToday: Number,
});
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Dashboard - {{ company.name }}</h2>
                    <p class="text-slate-600 mt-1">Visão geral do seu negócio</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas Principais -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Total de Conversas</dt>
                                <dd class="text-2xl font-bold text-slate-900">{{ stats.total_conversations }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-yellow-500 to-orange-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Pendentes</dt>
                                <dd class="text-2xl font-bold text-slate-900">{{ stats.pending_conversations }}</dd>
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
                                <dt class="text-sm font-medium text-slate-600">Em Atendimento</dt>
                                <dd class="text-2xl font-bold text-slate-900">{{ stats.in_progress_conversations }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Mensagens Hoje</dt>
                                <dd class="text-2xl font-bold text-slate-900">{{ messagesToday }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Conversas por Departamento -->
                    <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                        <div class="px-8 py-6">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900">Conversas por Departamento</h3>
                            </div>
                            <div class="space-y-4">
                                <div v-for="dept in conversationsByDepartment" :key="dept.id" class="flex items-center justify-between p-4 bg-slate-50/80 rounded-2xl border border-slate-100/50 hover:bg-slate-100/80 transition-all duration-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-4 h-4 rounded-full shadow-sm" :style="{ backgroundColor: dept.color }"></div>
                                        <span class="font-semibold text-slate-800">{{ dept.name }}</span>
                                    </div>
                                    <div class="flex gap-4 text-sm">
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-lg font-medium">{{ dept.pending_count }} pendentes</span>
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded-lg font-medium">{{ dept.in_progress_count }} em atendimento</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversas Recentes -->
                    <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                        <div class="px-8 py-6">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900">Conversas Recentes</h3>
                                </div>
                                <Link :href="route('conversations.index')" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                                    Ver todas →
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link v-for="conv in recentConversations" :key="conv.id" :href="route('conversations.show', conv.id)" class="group block p-4 rounded-2xl border border-slate-100/50 hover:bg-slate-50/80 hover:border-slate-200/50 transition-all duration-200 hover:shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="font-semibold text-slate-900 group-hover:text-slate-800">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-sm text-slate-600 mt-1">
                                                {{ conv.department?.name }} •
                                                <span :class="{
                                                    'text-amber-600 font-medium': conv.status === 'pending',
                                                    'text-emerald-600 font-medium': conv.status === 'in_progress',
                                                    'text-slate-500': conv.status === 'resolved'
                                                }">
                                                    {{ conv.status === 'pending' ? 'Pendente' : conv.status === 'in_progress' ? 'Em Atendimento' : 'Resolvida' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-400 font-medium">
                                            {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR') }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


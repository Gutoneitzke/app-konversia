<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pendingConversations: Array,
    myConversations: Array,
    stats: Object,
});
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Dashboard - Atendimento</h2>
                    <p class="text-slate-600 mt-1">Visão geral do seu trabalho</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas Rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white/70 backdrop-blur-xl shadow-2xl ring-1 ring-white/20 border border-white/30 rounded-2xl p-6 hover:shadow-3xl transition-all duration-300 hover:scale-[1.02]">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl p-4 shadow-lg">
                                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Pendentes</dt>
                                    <dd class="text-3xl font-bold text-slate-900 mt-1">{{ stats.pending_count }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/70 backdrop-blur-xl shadow-2xl ring-1 ring-white/20 border border-white/30 rounded-2xl p-6 hover:shadow-3xl transition-all duration-300 hover:scale-[1.02]">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl p-4 shadow-lg">
                                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Minhas Conversas</dt>
                                    <dd class="text-3xl font-bold text-slate-900 mt-1">{{ stats.my_conversations_count }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/70 backdrop-blur-xl shadow-2xl ring-1 ring-white/20 border border-white/30 rounded-2xl p-6 hover:shadow-3xl transition-all duration-300 hover:scale-[1.02]">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl p-4 shadow-lg">
                                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Mensagens Hoje</dt>
                                    <dd class="text-3xl font-bold text-slate-900 mt-1">{{ stats.messages_today }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Conversas Pendentes -->
                    <div class="bg-white/70 backdrop-blur-xl shadow-2xl ring-1 ring-white/20 border border-white/30 rounded-2xl overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-200/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900">Conversas Pendentes</h3>
                                </div>
                                <Link :href="route('conversations.index', { status: 'pending' })" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-105">
                                    Ver todas
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                        <div class="px-6 py-5">
                            <div v-if="pendingConversations.length > 0" class="space-y-4">
                                <Link v-for="conv in pendingConversations.slice(0, 5)" :key="conv.id" :href="route('conversations.show', conv.id)" class="block p-4 bg-slate-50/50 border border-slate-200/50 rounded-xl hover:bg-white hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ conv.department?.name }}
                                            </div>
                                            <div v-if="conv.messages && conv.messages.length > 0" class="text-xs text-slate-400 mt-2 truncate">
                                                {{ conv.messages[0].content }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-400 ml-4">
                                            {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR') }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">Nenhuma conversa pendente</p>
                            </div>
                        </div>
                    </div>

                    <!-- Minhas Conversas -->
                    <div class="bg-white/70 backdrop-blur-xl shadow-2xl ring-1 ring-white/20 border border-white/30 rounded-2xl overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-200/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-900">Minhas Conversas</h3>
                                </div>
                                <Link :href="route('conversations.index')" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-105">
                                    Ver todas
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                        <div class="px-6 py-5">
                            <div v-if="myConversations.length > 0" class="space-y-4">
                                <Link v-for="conv in myConversations.slice(0, 5)" :key="conv.id" :href="route('conversations.show', conv.id)" class="block p-4 bg-slate-50/50 border border-slate-200/50 rounded-xl hover:bg-white hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ conv.department?.name }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-400 ml-4">
                                            {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR') }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">Nenhuma conversa atribuída</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


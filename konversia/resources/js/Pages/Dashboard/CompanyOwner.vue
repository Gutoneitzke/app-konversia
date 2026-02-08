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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard - {{ company.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas Principais -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total de Conversas</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.total_conversations }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pendentes</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.pending_conversations }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Em Atendimento</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.in_progress_conversations }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Mensagens Hoje</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ messagesToday }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Conversas por Departamento -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Conversas por Departamento</h3>
                            <div class="space-y-3">
                                <div v-for="dept in conversationsByDepartment" :key="dept.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-3" :style="{ backgroundColor: dept.color }"></div>
                                        <span class="text-sm font-medium text-gray-900">{{ dept.name }}</span>
                                    </div>
                                    <div class="flex gap-4 text-sm">
                                        <span class="text-yellow-600 font-medium">{{ dept.pending_count }} pendentes</span>
                                        <span class="text-green-600 font-medium">{{ dept.in_progress_count }} em atendimento</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversas Recentes -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Conversas Recentes</h3>
                                <Link :href="route('conversations.index')" class="text-sm text-blue-600 hover:text-blue-800">
                                    Ver todas
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link v-for="conv in recentConversations" :key="conv.id" :href="route('conversations.show', conv.id)" class="block p-3 border rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ conv.department?.name }} • 
                                                <span :class="{
                                                    'text-yellow-600': conv.status === 'pending',
                                                    'text-green-600': conv.status === 'in_progress',
                                                    'text-gray-600': conv.status === 'resolved'
                                                }">
                                                    {{ conv.status === 'pending' ? 'Pendente' : conv.status === 'in_progress' ? 'Em Atendimento' : 'Resolvida' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400">
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


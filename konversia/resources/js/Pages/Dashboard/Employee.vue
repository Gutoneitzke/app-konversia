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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard - Atendimento
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas Rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.pending_count }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Minhas Conversas</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.my_conversations_count }}</dd>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Mensagens Hoje</dt>
                                        <dd class="text-lg font-semibold text-gray-900">{{ stats.messages_today }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Conversas Pendentes -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Conversas Pendentes</h3>
                                <Link :href="route('conversations.index', { status: 'pending' })" class="text-sm text-blue-600 hover:text-blue-800">
                                    Ver todas
                                </Link>
                            </div>
                            <div v-if="pendingConversations.length > 0" class="space-y-3">
                                <Link v-for="conv in pendingConversations.slice(0, 5)" :key="conv.id" :href="route('conversations.show', conv.id)" class="block p-3 border rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ conv.department?.name }}
                                            </div>
                                            <div v-if="conv.messages && conv.messages.length > 0" class="text-xs text-gray-400 mt-1 truncate">
                                                {{ conv.messages[0].content }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400 ml-4">
                                            {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR') }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <p>Nenhuma conversa pendente</p>
                            </div>
                        </div>
                    </div>

                    <!-- Minhas Conversas -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Minhas Conversas</h3>
                                <Link :href="route('conversations.index')" class="text-sm text-blue-600 hover:text-blue-800">
                                    Ver todas
                                </Link>
                            </div>
                            <div v-if="myConversations.length > 0" class="space-y-3">
                                <Link v-for="conv in myConversations.slice(0, 5)" :key="conv.id" :href="route('conversations.show', conv.id)" class="block p-3 border rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ conv.department?.name }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400 ml-4">
                                            {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR') }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <p>Nenhuma conversa atribuída</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


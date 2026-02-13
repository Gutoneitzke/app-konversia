<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    conversations: Object,
    departments: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const departmentFilter = ref(props.filters.department_id);

const applyFilters = () => {
    router.get(route('conversations.index'), {
        search: search.value,
        status: statusFilter.value,
        department_id: departmentFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

let searchTimeout = null;

watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

watch([statusFilter, departmentFilter], () => {
    applyFilters();
});

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-green-100 text-green-800',
        'resolved': 'bg-gray-100 text-gray-800',
        'closed': 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        'pending': 'Pendente',
        'in_progress': 'Em Atendimento',
        'resolved': 'Resolvida',
        'closed': 'Fechada',
    };
    return texts[status] || status;
};

import { onMounted, onUnmounted } from 'vue';

let pollingInterval = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        router.reload({
            only: ['conversations'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 15000); // 15 segundos
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <AppLayout title="Conversas">
        <Head title="Conversas" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Conversas</h2>
                    <p class="text-slate-600 mt-1">Gerencie todas as suas conversas</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-slate-500 to-slate-600 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Total</dt>
                                <dd class="text-3xl font-bold text-slate-900">{{ stats.total }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-yellow-500 to-orange-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Pendentes</dt>
                                <dd class="text-3xl font-bold text-amber-600">{{ stats.pending }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-green-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Em Atendimento</dt>
                                <dd class="text-3xl font-bold text-emerald-600">{{ stats.in_progress }}</dd>
                            </div>
                        </div>
                    </div>
                    <div class="group relative rounded-3xl bg-white/70 backdrop-blur-xl p-6 shadow-xl ring-1 ring-white/20 border border-white/30 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-slate-500 to-slate-600 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Resolvidas</dt>
                                <dd class="text-3xl font-bold text-slate-700">{{ stats.resolved }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 mb-8 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Filtros</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-3">Buscar</label>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Nome, telefone..."
                                    class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-3">Status</label>
                                <select
                                    v-model="statusFilter"
                                    class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                >
                                    <option value="all">Todos</option>
                                    <option value="pending">Pendente</option>
                                    <option value="in_progress">Em Atendimento</option>
                                    <option value="resolved">Resolvida</option>
                                    <option value="closed">Fechada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                                <select
                                    v-model="departmentFilter"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Todos</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Conversas -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <div v-if="conversations.data && conversations.data.length > 0" class="space-y-3">
                            <Link
                                v-for="conv in conversations.data"
                                :key="conv.id"
                                :href="route('conversations.show', conv.id)"
                                class="block p-4 border rounded-lg hover:bg-gray-50 transition"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                            </h3>
                                            <span :class="getStatusColor(conv.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ getStatusText(conv.status) }}
                                            </span>
                                            <span v-if="conv.unread_count > 0" class="bg-red-500 text-white text-xs font-semibold rounded-full px-2 py-1">
                                                {{ conv.unread_count }} não lidas
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ conv.department?.name }}
                                            <span v-if="conv.assigned_user"> • Atendente: {{ conv.assigned_user.name }}</span>
                                        </div>
                                        <div v-if="conv.messages && conv.messages.length > 0" class="text-sm text-gray-600 mt-2 truncate">
                                            {{ conv.messages[0].content }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-400 ml-4 whitespace-nowrap">
                                        {{ new Date(conv.last_message_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma conversa encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500">Tente ajustar os filtros.</p>
                        </div>

                        <!-- Paginação -->
                        <div v-if="conversations.links && conversations.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="flex gap-2">
                                <Link
                                    v-for="link in conversations.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="{
                                        'bg-blue-600 text-white': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                                        'opacity-50 cursor-not-allowed': !link.url
                                    }"
                                    class="px-4 py-2 border rounded-md text-sm font-medium"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


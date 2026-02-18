<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ChatConversation from '@/Components/ChatConversation.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    conversations: Object,
    departments: Array,
    users: Array,
    filters: Object,
    stats: Object,
    selectedConversation: Object, // Conversa selecionada (opcional)
});

const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const departmentFilter = ref(props.filters.department_id);
const selectedConversationId = ref(props.selectedConversation?.id || null);

const applyFilters = () => {
    router.get(route('conversations.index'), {
        search: search.value,
        status: statusFilter.value,
        department_id: departmentFilter.value,
        selected: selectedConversationId.value,
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
    // Quando os filtros mudam, desmarcar qualquer conversa selecionada
    selectedConversationId.value = null;
    applyFilters();
});

const selectConversation = (conversation) => {
    selectedConversationId.value = conversation.id;

    // Construir URL com todos os parâmetros preservados
    const params = new URLSearchParams();

    // Adicionar filtros atuais
    if (search.value.trim()) params.set('search', search.value.trim());
    if (statusFilter.value && statusFilter.value !== 'all') params.set('status', statusFilter.value);
    if (departmentFilter.value) params.set('department_id', departmentFilter.value);

    // Adicionar conversa selecionada
    params.set('selected', conversation.id);

    // Atualizar URL
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);

    // Marcar mensagens como lidas com axios (ação em background)
    axios.post(route('conversations.mark-read', conversation.id))
        .catch((error) => {
            console.error('Erro ao marcar mensagens como lidas:', error);
        });
};

const selectedConversation = computed(() => {
    // Primeiro tenta usar a conversa selecionada carregada do backend (com todas as mensagens)
    if (props.selectedConversation && props.selectedConversation.id === selectedConversationId.value) {
        return props.selectedConversation;
    }

    // Fallback: procura na lista de conversas (limitada a 1 mensagem cada)
    if (selectedConversationId.value) {
        return props.conversations.data.find(conv => conv.id === selectedConversationId.value) || null;
    }

    return null;
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

const formatLastMessageTime = (timestamp) => {
    if (!timestamp) return '';

    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
        return 'Ontem';
    } else if (diffDays < 7) {
        return date.toLocaleDateString('pt-BR', { weekday: 'short' });
    } else {
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    }
};

import { onMounted, onUnmounted } from 'vue';

let pollingInterval = null;

onMounted(() => {
    // Carregar filtros e seleção da URL
    const urlParams = new URLSearchParams(window.location.search);

    // Carregar filtros da URL
    const urlSearch = urlParams.get('search');
    const urlStatus = urlParams.get('status');
    const urlDepartment = urlParams.get('department_id');
    const selectedId = urlParams.get('selected');

    if (urlSearch) search.value = urlSearch;
    if (urlStatus) statusFilter.value = urlStatus;
    if (urlDepartment) departmentFilter.value = urlDepartment;
    if (selectedId) {
        selectedConversationId.value = parseInt(selectedId);
    }

    // Limpar URL se não houver parâmetros
    const cleanParams = new URLSearchParams();
    if (search.value.trim()) cleanParams.set('search', search.value.trim());
    if (statusFilter.value && statusFilter.value !== 'all') cleanParams.set('status', statusFilter.value);
    if (departmentFilter.value) cleanParams.set('department_id', departmentFilter.value);
    if (selectedConversationId.value) cleanParams.set('selected', selectedConversationId.value);

    const cleanUrl = cleanParams.toString();
    if (cleanUrl !== window.location.search.substring(1)) {
        window.history.replaceState({}, '', cleanUrl ? `${window.location.pathname}?${cleanUrl}` : window.location.pathname);
    }

    pollingInterval = setInterval(() => {
        // Fazer polling respeitando os filtros atuais
        router.reload({
            only: ['conversations'],
            data: {
                search: search.value,
                status: statusFilter.value,
                department_id: departmentFilter.value,
            },
            preserveState: true,
            preserveScroll: true,
        });
    }, 10000); // 10 segundos - mais frequente para melhor UX
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <AppLayout title="Conversas">
        <Head title="Conversas" />

        <!-- <template #header>
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
        </template> -->

        <div class="">
            <div class="">
                <!-- Layout Principal WhatsApp-style -->
                <div class="bg-white shadow rounded-lg overflow-hidden" style="height: 88.5vh;">
                    <div class="flex h-full">
                        <!-- Sidebar - Lista de Conversas -->
                        <div class="w-1/3 bg-gray-50 border-r border-gray-200 flex flex-col">
                            <!-- Header da Sidebar -->
                            <div class="bg-white border-b border-gray-200 px-4 py-3">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Conversas</h3>
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <span>Total: {{ stats.total }}</span>
                                    </div>
                                </div>

                                <!-- Filtros Compactos -->
                                <div class="space-y-3">
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Buscar conversas..."
                                        class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    />
                                    <div class="flex gap-2">
                                        <select
                                            v-model="statusFilter"
                                            class="flex-1 rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                            <option value="all">Todos</option>
                                            <option value="pending">Pendentes</option>
                                            <option value="in_progress">Em andamento</option>
                                            <option value="resolved">Resolvidas</option>
                                            <option value="closed">Fechadas</option>
                                        </select>
                                        <select
                                            v-model="departmentFilter"
                                            class="flex-1 rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                            <option :value="null">Todos deptos</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                                {{ dept.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de Conversas -->
                            <div class="flex-1 overflow-y-auto">
                                <div v-if="conversations.data && conversations.data.length > 0">
                                    <div
                                        v-for="conv in conversations.data"
                                        :key="conv.id"
                                        @click="selectConversation(conv)"
                                        :class="[
                                            'border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors duration-150',
                                            selectedConversationId === conv.id ? 'bg-emerald-50 border-l-4 border-l-emerald-500' : ''
                                        ]"
                                    >
                                        <div class="px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                                    <!-- Avatar -->
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-white font-semibold text-sm">
                                                            {{ (conv.contact?.name || conv.contact_name || 'C').charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>

                                                    <!-- Informações da Conversa -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <h4 class="text-sm font-semibold text-gray-900 truncate">
                                                                {{ conv.contact?.name || conv.contact_name || 'Contato Desconhecido' }}
                                                            </h4>
                                                            <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                                                {{ formatLastMessageTime(conv.last_message_at) }}
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center justify-between">
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-xs text-gray-600 truncate mb-1">
                                                                    {{ conv.department?.name }}
                                                                    <span v-if="conv.assigned_user" class="ml-1">• {{ conv.assigned_user.name }}</span>
                                                                </p>
                                                                <p class="text-sm text-gray-700 truncate">
                                                                    <span v-if="conv.last_message">
                                                                        <template v-if="conv.last_message.type === 'image'">📷 Imagem</template>
                                                                        <template v-else-if="conv.last_message.type === 'audio'">🎤 Áudio</template>
                                                                        <template v-else>{{ conv.last_message.content }}</template>
                                                                    </span>
                                                                    <span v-else class="text-gray-400">
                                                                        Conversa iniciada
                                                                    </span>
                                                                </p>
                                                            </div>

                                                            <!-- Indicadores -->
                                                            <div class="flex flex-col items-end gap-1 ml-2">
                                                                <span :class="getStatusColor(conv.status)" class="px-1.5 py-0.5 text-xs font-medium rounded-full">
                                                                    {{ getStatusText(conv.status) }}
                                                                </span>
                                                                <span v-if="conv.unread_messages_count > 0" class="bg-emerald-500 text-white text-xs font-semibold rounded-full px-1.5 py-0.5 min-w-[20px] text-center">
                                                                    {{ conv.unread_messages_count > 99 ? '99+' : conv.unread_messages_count }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="flex items-center justify-center h-full text-gray-500">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <p class="text-sm font-medium">Nenhuma conversa encontrada</p>
                                        <p class="text-xs">Tente ajustar os filtros</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Área Principal - Chat -->
                        <div class="flex-1 flex flex-col">
                            <div v-if="selectedConversation" class="h-full">
                                <ChatConversation
                                    :conversation="selectedConversation"
                                    :show-header="true"
                                    :show-back-button="false"
                                    :departments="departments"
                                    :users="users"
                                />
                            </div>

                            <div v-else class="h-full flex items-center justify-center bg-gray-50">
                                <div class="text-center">
                                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Selecione uma conversa</h3>
                                    <p class="text-sm text-gray-500">Escolha uma conversa da lista para começar a conversar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


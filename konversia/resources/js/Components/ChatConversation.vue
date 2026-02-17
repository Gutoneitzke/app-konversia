<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue';
import TransferConversationModal from './TransferConversationModal.vue';

const props = defineProps({
    conversation: Object,
    showHeader: {
        type: Boolean,
        default: true
    },
    showBackButton: {
        type: Boolean,
        default: false
    },
    departments: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    content: '',
});

const sending = ref(false);
const messagesContainer = ref(null);
const showTransferModal = ref(false);

const sendMessage = () => {
    if (!form.content.trim()) return;

    sending.value = true;

    form.post(route('conversations.messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            sending.value = false;
            scrollToBottom();
        },
        onError: (errors) => {
            sending.value = false;
            console.error('Erro ao enviar mensagem:', errors);
            alert('Erro ao enviar mensagem. Tente novamente.');
        },
        onFinish: () => {
            sending.value = false;
        }
    });
};

onMounted(() => {
    scrollToBottom();
    startMessagePolling();
});

onUnmounted(() => {
    stopMessagePolling();
});

// Polling para mensagens
let messagePollingInterval = null;

const startMessagePolling = () => {
    if (messagePollingInterval) return;
    getMessages();
    messagePollingInterval = setInterval(() => {
        getMessages();
    }, 3000);
};

const getMessages = () => {
    // Usar Inertia para fazer reload apenas da conversa selecionada
    router.reload({
        only: ['selectedConversation'],
        data: { selected: props.conversation.id },
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Atualizar apenas as mensagens se a conversa ainda for a mesma
            if (page.props.selectedConversation && page.props.selectedConversation.id === props.conversation.id) {
                props.conversation.messages = page.props.selectedConversation.messages;
            }
        },
        onError: (errors) => {
            console.warn('Erro ao buscar mensagens atualizadas:', errors);
        }
    });
};

const stopMessagePolling = () => {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }
};

// Fazer scroll quando mensagens mudarem
watch(() => props.conversation?.messages, (newMessages, oldMessages) => {
    if (newMessages && newMessages.length !== (oldMessages?.length || 0)) {
        nextTick(() => scrollToBottom());
    }
}, { deep: true });

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const formatMessageTime = (timestamp) => {
    return new Date(timestamp).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

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

const openTransferModal = () => {
    showTransferModal.value = true;
};

const handleTransferred = (transferData) => {
    showTransferModal.value = false;
    // O backend redireciona automaticamente para a lista de conversas
    console.log('Conversa transferida:', transferData);
};
</script>

<template>
    <div class="flex flex-col h-full bg-white">
        <!-- Header da Conversa -->
        <div v-if="showHeader" class="flex-shrink-0 border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link v-if="showBackButton" :href="route('conversations.index')" class="text-gray-500 hover:text-gray-700 lg:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">
                                {{ (conversation.contact?.name || conversation.contact_name || 'Contato').charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div>
                            <h2 class="font-semibold text-lg text-gray-900">
                                {{ conversation.contact?.name || conversation.contact_name || 'Contato Desconhecido' }}
                            </h2>
                            <p class="text-sm text-gray-500">
                                {{ conversation.contact?.phone_number || conversation.contact_phone || 'Telefone não informado' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>Departamento: <strong>{{ conversation.department?.name }}</strong></span>
                        <span v-if="conversation.assigned_user">
                            Atendente: <strong>{{ conversation.assigned_user.name }}</strong>
                        </span>
                        <span :class="getStatusColor(conversation.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                            {{ getStatusText(conversation.status) }}
                        </span>
                        <button
                            @click="openTransferModal"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Transferir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Mensagens -->
        <div class="flex-1 overflow-hidden bg-gray-50">
            <div ref="messagesContainer" class="h-full overflow-y-auto px-6 py-4">
                <div v-if="conversation.messages && conversation.messages.length > 0" class="space-y-4">
                    <div
                        v-for="message in conversation.messages"
                        :key="message.id"
                        :class="{
                            'flex justify-end': message.direction === 'outbound',
                            'flex justify-start': message.direction === 'inbound',
                        }"
                    >
                        <div :class="{
                            'bg-emerald-500 text-white': message.direction === 'outbound',
                            'bg-white text-gray-900 shadow-sm': message.direction === 'inbound',
                        }" class="max-w-[70%] rounded-2xl px-4 py-3 break-words">
                            <div class="text-sm leading-relaxed">{{ message.content }}</div>
                            <div :class="{
                                'text-emerald-100': message.direction === 'outbound',
                                'text-gray-500': message.direction === 'inbound',
                            }" class="text-xs mt-2">
                                {{ formatMessageTime(message.sent_at) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center justify-center h-full text-gray-500">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-lg font-medium">Nenhuma mensagem ainda</p>
                        <p class="text-sm">Seja o primeiro a iniciar a conversa!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Envio -->
        <div class="flex-shrink-0 bg-white border-t border-gray-200 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <input
                        v-model="form.content"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Digite sua mensagem..."
                        class="w-full rounded-full border-gray-300 bg-gray-50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                        :disabled="sending"
                    />
                </div>
                <button
                    @click="sendMessage"
                    :disabled="sending || !form.content.trim()"
                    class="inline-flex items-center justify-center h-12 w-12 bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 text-white rounded-full transition-colors duration-200 disabled:cursor-not-allowed"
                >
                    <svg v-if="sending" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </div>
        </div>
        <!-- Modal de Transferência -->
        <TransferConversationModal
            v-if="showTransferModal"
            :conversation="conversation"
            :departments="departments"
            :users="users"
            @close="showTransferModal = false"
            @transferred="handleTransferred"
        />
    </div>
</template>


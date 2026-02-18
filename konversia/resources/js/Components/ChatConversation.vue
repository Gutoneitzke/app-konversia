<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { onUnmounted, ref, nextTick, watch, onMounted } from 'vue'
import TransferConversationModal from './TransferConversationModal.vue'

/* =======================
   PROPS
======================= */
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
})

/* =======================
   STATE
======================= */
const form = useForm({ content: '' })
const sending = ref(false)
const loadingMessages = ref(true)
const hasUnreadMessages = ref(false)

const messagesContainer = ref(null)
const showTransferModal = ref(false)
const retryingMessages = ref({})
const resolving = ref(false)
const closing = ref(false)
const reopening = ref(false)
const showDropdown = ref(false)

/*/**
 * Mensagens locais (NÃO muta props)
 */
const messages = ref([])

/**
 * Controle de polling
 */
let messagePollingInterval = null
let isFetching = false
const currentConversationId = ref(null)

/* =======================
   HELPERS
======================= */
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight
        }
    })
}

const formatMessageTime = (timestamp) => {
    return new Date(timestamp).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusColor = (status) => ({
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-green-100 text-green-800',
    resolved: 'bg-gray-100 text-gray-800',
    closed: 'bg-red-100 text-red-800'
}[status] || 'bg-gray-100 text-gray-800')

const getStatusText = (status) => ({
    pending: 'Pendente',
    in_progress: 'Em Atendimento',
    resolved: 'Resolvida',
    closed: 'Fechada'
}[status] || status)

/* =======================
   POLLING
======================= */
const startMessagePolling = () => {
    if (messagePollingInterval || !props.conversation?.id) return

    getMessages(true)

    messagePollingInterval = setInterval(() => {
        getMessages(false)
    }, 3000)
}

const stopMessagePolling = () => {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval)
        messagePollingInterval = null
    }
}

const getMessages = (showLoader = false) => {
    if (isFetching) return
    isFetching = true

    if (showLoader) loadingMessages.value = true

    router.reload({
        only: ['selectedConversation'],
        data: { selected: props.conversation.id },
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const updated = page.props.selectedConversation

            if (updated?.id === props.conversation.id) {
                const previousLength = messages.value.length
                const newMessages = updated.messages || []

                messages.value = newMessages

                if (newMessages.length > previousLength) {
                    if (isAtBottom()) {
                        scrollToBottom()
                    } else {
                        hasUnreadMessages.value = true
                    }
                }
            }
        },
        onFinish: () => {
            loadingMessages.value = false
            isFetching = false
        }
    })
}

const isAtBottom = () => {
    if (!messagesContainer.value) return true

    const { scrollTop, scrollHeight, clientHeight } = messagesContainer.value
    return scrollTop + clientHeight >= scrollHeight - 20
}

/* =======================
   WATCH (CHAVE DA SOLUÇÃO)
======================= */
watch(
    () => props.conversation?.id,
    (newId, oldId) => {
        if (!newId || newId === oldId) return

        loadingMessages.value = true
        messages.value = props.conversation.messages || []

        stopMessagePolling()
        startMessagePolling()

        currentConversationId.value = newId
        nextTick(scrollToBottom)
        loadingMessages.value = false
    },
    { immediate: true }
)

/* =======================
   LIFECYCLE
======================= */
onMounted(() => {
    // Fechar dropdown ao clicar fora
    const handleClickOutside = (event) => {
        const dropdown = event.target.closest('.relative')
        if (!dropdown) {
            showDropdown.value = false
        }
    }
    document.addEventListener('click', handleClickOutside)
    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside)
    })
})

onUnmounted(() => {
    stopMessagePolling()
})

/* =======================
   ACTIONS
======================= */
const sendMessage = () => {
    if (!form.content.trim()) return

    sending.value = true

    form.post(route('conversations.messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            scrollToBottom()
        },
        onError: (errors) => {
            console.error('Erro ao enviar mensagem:', errors)
            alert('Erro ao enviar mensagem. Tente novamente.')
        },
        onFinish: () => {
            sending.value = false
        }
    })
}

const openTransferModal = () => {
    showTransferModal.value = true
}

const handleTransferred = (transferData) => {
    showTransferModal.value = false
    console.log('Conversa transferida:', transferData)
}

const goToLatestMessages = () => {
    scrollToBottom()
    hasUnreadMessages.value = false
}

const handleScroll = () => {
    if (isAtBottom()) {
        hasUnreadMessages.value = false
    }
}

const retryMessage = (message) => {
    if (retryingMessages.value[message.id]) return

    retryingMessages.value[message.id] = true

    axios.post(route('messages.retry', message.id), {}, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    })
    // .then((response) => {
    //     // Recarregar mensagens para mostrar o novo status
    //     getMessages(true)
    // })
    .catch((error) => {
        console.error('Erro ao reenviar mensagem:', error)
        // Mostrar notificação de erro mais amigável
        const errorMessage = error.response?.data?.message || 'Erro ao reenviar mensagem. Verifique sua conexão e tente novamente.'
        alert(errorMessage)
    })
    .finally(() => {
        retryingMessages.value[message.id] = false
    })
}

const resolveConversation = () => {
    if (resolving.value) return

    if (!confirm('Tem certeza que deseja resolver esta conversa?')) {
        return
    }

    resolving.value = true

    axios.post(route('conversations.resolve', props.conversation.id), {}, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    })
    .then((response) => {
        // Recarregar a página para atualizar o status
        router.reload({
            only: ['selectedConversation'],
            preserveState: true,
        })
    })
    .catch((error) => {
        console.error('Erro ao resolver conversa:', error)
        const errorMessage = error.response?.data?.message || 'Erro ao resolver conversa. Tente novamente.'
        alert(errorMessage)
    })
    .finally(() => {
        resolving.value = false
    })
}

const closeConversation = () => {
    if (closing.value) return

    if (!confirm('Tem certeza que deseja fechar esta conversa?')) {
        return
    }

    closing.value = true

    axios.post(route('conversations.close', props.conversation.id), {}, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    })
    .then((response) => {
        // Recarregar a página para atualizar o status
        router.reload({
            only: ['selectedConversation'],
            preserveState: true,
        })
    })
    .catch((error) => {
        console.error('Erro ao fechar conversa:', error)
        const errorMessage = error.response?.data?.message || 'Erro ao fechar conversa. Tente novamente.'
        alert(errorMessage)
    })
    .finally(() => {
        closing.value = false
    })
}

const reopenConversation = () => {
    if (reopening.value) return

    if (!confirm('Tem certeza que deseja reabrir esta conversa?')) {
        return
    }

    reopening.value = true

    axios.post(route('conversations.reopen', props.conversation.id), {}, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    })
    .then((response) => {
        // Recarregar a página para atualizar o status
        router.reload({
            only: ['selectedConversation'],
            preserveState: true,
        })
    })
    .catch((error) => {
        console.error('Erro ao reabrir conversa:', error)
        const errorMessage = error.response?.data?.message || 'Erro ao reabrir conversa. Tente novamente.'
        alert(errorMessage)
    })
    .finally(() => {
        reopening.value = false
    })
}

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
                        <div class="flex items-center gap-2">
                            <!-- Dropdown de Ações (só mostra se conversa estiver ativa) -->
                            <div v-if="conversation.status !== 'resolved' && conversation.status !== 'closed'" class="relative">
                                <button
                                    @click="showDropdown = !showDropdown"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                >
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                                    </svg>
                                    Ações
                                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown Menu -->
                                <div
                                    v-if="showDropdown"
                                    @click.stop
                                    class="absolute right-0 z-10 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg"
                                >
                                    <div class="py-1">
                                        <!-- Resolver -->
                                        <button
                                            @click="resolveConversation(); showDropdown = false"
                                            :disabled="resolving"
                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-4 h-4 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ resolving ? 'Resolvendo...' : 'Resolver' }}
                                        </button>

                                        <!-- Fechar -->
                                        <button
                                            @click="closeConversation(); showDropdown = false"
                                            :disabled="closing"
                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg class="w-4 h-4 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            {{ closing ? 'Fechando...' : 'Fechar' }}
                                        </button>

                                        <div class="border-t border-gray-100"></div>

                                        <!-- Transferir -->
                                        <button
                                            @click="openTransferModal(); showDropdown = false"
                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            <svg class="w-4 h-4 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            Transferir
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Botão Reabrir (só mostra se estiver fechada ou resolvida) -->
                            <button
                                v-if="conversation.status === 'resolved' || conversation.status === 'closed'"
                                @click="reopenConversation"
                                :disabled="reopening"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="reopening" class="w-4 h-4 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ reopening ? 'Reabrindo...' : 'Reabrir' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Mensagens -->
        <div class="flex-1 overflow-hidden bg-gray-50">
            <div
                v-if="hasUnreadMessages"
                class="sticky top-2 z-10 flex justify-center"
            >
                <button
                    @click="goToLatestMessages()"
                    class="bg-emerald-500 text-white text-sm px-4 py-2 rounded-full shadow hover:bg-emerald-600 transition"
                >
                    📩 Nova mensagem não lida
                </button>
            </div>
            <div v-if="loadingMessages" class="h-full flex items-center justify-center">
                <div class="animate-spin h-10 w-10 border-b-2 border-emerald-500 rounded-full" />
            </div>

            <div
                v-else
                ref="messagesContainer"
                @scroll="handleScroll"
                class="h-full overflow-y-auto px-6 py-4"
            >
                <div v-if="messages.length" class="space-y-4">
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        :class="{
                            'flex justify-end': message.direction === 'outbound',
                            'flex justify-start': message.direction === 'inbound',
                        }"
                    >
                        <div class="max-w-[70%] break-words">
                            <!-- Mensagem falhada -->
                            <div
                                v-if="message.delivery_status === 'failed' && message.direction === 'outbound'"
                                class="bg-red-50 rounded-2xl px-4 py-3 relative"
                            >
                                <!-- Ícone de erro no topo direito -->
                                <div class="absolute -top-1 -right-1 bg-red-500 rounded-full p-1">
                                    <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>

                                <div class="text-red-900 text-sm leading-relaxed pr-6">
                                    {{ message.content }}
                                </div>
                                <div class="flex items-center justify-between mt-2 pr-6">
                                    <span class="text-red-600 text-xs">
                                        Não enviada.
                                    </span>
                                    <button
                                        @click="retryMessage(message)"
                                        class="text-red-600 hover:text-red-800 text-xs underline transition-colors"
                                        :disabled="retryingMessages[message.id]"
                                        :class="{ 'opacity-50 cursor-not-allowed': retryingMessages[message.id] }"
                                    >
                                        <span v-if="retryingMessages[message.id]">Tentando...</span>
                                        <span v-else>Tentar novamente</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mensagem enviada com sucesso -->
                            <div
                                v-else
                                :class="{
                                    'bg-emerald-500 text-white': message.direction === 'outbound',
                                    'bg-white text-gray-900 shadow-sm': message.direction === 'inbound',
                                }"
                                class="rounded-2xl px-4 py-3"
                            >
                                <div class="text-sm leading-relaxed">
                                    {{ message.content }}
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div
                                        :class="{
                                            'text-emerald-100': message.direction === 'outbound',
                                            'text-gray-500': message.direction === 'inbound',
                                        }"
                                        class="text-xs"
                                    >
                                        {{ formatMessageTime(message.sent_at) }}
                                    </div>

                                    <!-- Indicador de status para mensagens outbound -->
                                    <div v-if="message.direction === 'outbound'" class="text-xs flex items-center ml-1">
                                        <span v-if="message.delivery_status === 'pending'" class="text-gray-400">
                                            <svg class="h-3 w-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" class="opacity-25"></circle>
                                                <path fill="none" stroke="currentColor" stroke-width="2" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                        <span v-else-if="message.delivery_status === 'sent'" class="text-gray-400">
                                            ✓
                                        </span>
                                        <span v-else-if="message.delivery_status === 'delivered'" class="text-gray-400">
                                            ✓✓
                                        </span>
                                        <span v-else-if="message.delivery_status === 'read'" class="text-blue-500">
                                            ✓✓
                                        </span>
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
                        <p class="text-lg font-medium">Nenhuma mensagem ainda</p>
                        <p class="text-sm">Seja o primeiro a iniciar a conversa!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Envio -->
        <div class="flex-shrink-0 bg-white border-t border-gray-200 px-6 py-4 border-b border-gray-200">
            <!-- Conversa fechada/resolvida -->
            <div v-if="conversation.status === 'resolved' || conversation.status === 'closed'" class="text-center py-4">
                <div class="flex items-center justify-center gap-2 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="text-sm">
                        Esta conversa está {{ conversation.status === 'resolved' ? 'resolvida' : 'fechada' }} e não pode mais receber mensagens.
                    </span>
                </div>
            </div>

            <!-- Campo de envio ativo -->
            <div v-else class="flex items-center gap-3">
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


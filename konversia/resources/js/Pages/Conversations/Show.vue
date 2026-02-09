<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    conversation: Object,
});

const form = useForm({
    content: '',
});

const sending = ref(false);

const sendMessage = () => {
    if (!form.content.trim()) return;
    
    sending.value = true;
    
    // Envio otimista (opcional, pode ser implementado depois) ou apenas envio simples
    form.post(route('conversations.messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            sending.value = false;
        },
        onError: () => {
            sending.value = false;
        }
    });
};

let pollingInterval = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        // Verificar se não estamos editando algo ou interagindo (opcional)
        // Recarregar apenas mensagens novos
        router.reload({
            only: ['conversation'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 3000); // 3 segundos
    
    // Auto-scroll para o fim da lista de mensagens
    scrollToBottom();
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const messagesContainer = ref(null);

const scrollToBottom = () => {
    // Implementar scroll se necessário
};
</script>

<template>
    <AppLayout title="Conversa">
        <Head title="Conversa" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('conversations.index')" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ conversation.contact?.name || conversation.contact_name || 'Contato Desconhecido' }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ conversation.contact?.phone_number || conversation.contact_phone }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Informações da Conversa -->
                    <div class="px-4 py-3 bg-gray-50 border-b">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-4">
                                <span class="text-gray-600">Departamento: <strong>{{ conversation.department?.name }}</strong></span>
                                <span v-if="conversation.assigned_user" class="text-gray-600">
                                    Atendente: <strong>{{ conversation.assigned_user.name }}</strong>
                                </span>
                            </div>
                            <span :class="{
                                'bg-yellow-100 text-yellow-800': conversation.status === 'pending',
                                'bg-green-100 text-green-800': conversation.status === 'in_progress',
                                'bg-gray-100 text-gray-800': conversation.status === 'resolved',
                            }" class="px-2 py-1 text-xs font-semibold rounded-full">
                                {{ conversation.status === 'pending' ? 'Pendente' : conversation.status === 'in_progress' ? 'Em Atendimento' : 'Resolvida' }}
                            </span>
                        </div>
                    </div>

                    <!-- Mensagens -->
                    <div class="px-4 py-5 sm:p-6 max-h-[600px] overflow-y-auto">
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
                                    'bg-blue-600 text-white': message.direction === 'outbound',
                                    'bg-gray-200 text-gray-900': message.direction === 'inbound',
                                }" class="max-w-[70%] rounded-lg px-4 py-2">
                                    <div class="text-sm">{{ message.content }}</div>
                                    <div :class="{
                                        'text-blue-100': message.direction === 'outbound',
                                        'text-gray-500': message.direction === 'inbound',
                                    }" class="text-xs mt-1">
                                        {{ new Date(message.sent_at).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            <p>Nenhuma mensagem ainda</p>
                        </div>
                    </div>

                    <!-- Área de Envio -->
                    <div class="px-4 py-3 bg-gray-50 border-t">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.content"
                                @keyup.enter="sendMessage"
                                type="text"
                                placeholder="Digite sua mensagem..."
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="sending"
                            />
                            <button
                                @click="sendMessage"
                                :disabled="sending || !form.content.trim()"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                <span v-if="sending">Enviando...</span>
                                <span v-else>Enviar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


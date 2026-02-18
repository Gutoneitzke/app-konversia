<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    whatsappNumber: {
        type: Object,
        default: null
    }
});

const isConnected = ref(false);
const statusMessage = ref('');
const statusColor = ref('gray');
const alertClass = ref('');
const intervalId = ref(null);
const lastUpdated = ref(null);

// Estado reativo para o número WhatsApp
const whatsappNumber = ref(props.whatsappNumber);

// Busca o status atualizado do servidor
const fetchUpdatedStatus = async () => {
    try {
        const response = await axios.get('/api/whatsapp/status');
        if (response.data && response.data.whatsapp_number) {
            // Atualiza o estado reativo com os dados mais recentes
            whatsappNumber.value = response.data.whatsapp_number;
            lastUpdated.value = new Date();
        }
    } catch (error) {
        console.warn('Erro ao buscar status WhatsApp:', error);
    }
};

// Atualiza o status baseado no número WhatsApp
const updateStatus = () => {
    const currentNumber = whatsappNumber.value;
    if (!currentNumber) {
        isConnected.value = false;
        statusMessage.value = 'Nenhum número WhatsApp configurado';
        statusColor.value = 'gray';
        alertClass.value = 'bg-gray-50 border-gray-200';
        return;
    }

    const status = currentNumber.status;

    switch (status) {
        case 'connected':
            isConnected.value = true;
            statusMessage.value = `Conectado`;
            statusColor.value = 'green';
            alertClass.value = 'bg-green-50 border-green-200';
            break;
        case 'connecting':
            isConnected.value = false;
            statusMessage.value = `Conectando`;
            statusColor.value = 'yellow';
            alertClass.value = 'bg-yellow-50 border-yellow-200';
            break;
        case 'inactive':
        case 'error':
        case 'blocked':
            isConnected.value = false;
            statusMessage.value = `WhatsApp desconectado`;
            statusColor.value = 'red';
            alertClass.value = 'bg-red-100 border-red-400';
            break;
        default:
            isConnected.value = false;
            statusMessage.value = `Status desconhecido`;
            statusColor.value = 'gray';
            alertClass.value = 'bg-gray-50 border-gray-200';
    }
};

// Atualiza o status quando o componente é montado ou quando a prop muda
onMounted(() => {
    updateStatus();

    // Inicia polling para atualizar o status automaticamente a cada 30 segundos
    intervalId.value = setInterval(fetchUpdatedStatus, 30000);
});

// Sincroniza o estado reativo com a prop inicial
watch(() => props.whatsappNumber, (newValue) => {
    whatsappNumber.value = newValue;
}, { immediate: true });

// Atualiza o status quando o número WhatsApp muda
watch(whatsappNumber, updateStatus, { deep: true });

// Limpa o intervalo quando o componente é desmontado
onUnmounted(() => {
    if (intervalId.value) {
        clearInterval(intervalId.value);
    }
});
</script>

<template>
    <div v-if="whatsappNumber" :class="['px-3 py-2 border-l-2', alertClass]">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <!-- Ícone de status -->
                <svg v-if="isConnected" class="h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else-if="statusColor === 'yellow'" class="h-4 w-4 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-2 flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-medium" :class="statusColor === 'red' ? 'text-red-900' : `text-${statusColor}-800`">
                        {{ statusMessage }}
                    </p>
                    <p v-if="!isConnected && whatsappNumber.last_connected_at" class="text-xs ml-4" :class="statusColor === 'red' ? 'text-red-700' : `text-${statusColor}-700`">
                        Última conexão: {{ new Date(whatsappNumber.last_connected_at).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

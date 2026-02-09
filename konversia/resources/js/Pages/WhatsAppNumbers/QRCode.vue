<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

import axios from 'axios';

const props = defineProps({
    whatsappNumber: Object,
    qrCode: String,
});

const checkingStatus = ref(false);
const currentQR = ref(props.qrCode);
let statusInterval = null;
let qrInterval = null;

const checkStatus = () => {
    checkingStatus.value = true;
    axios.get(route('whatsapp-numbers.status', props.whatsappNumber.api_key))
        .then(response => {
            if (response.data.status === 'connected') {
                router.visit(route('whatsapp-numbers.index'));
            }
        })
        .finally(() => {
            checkingStatus.value = false;
        });
};

const refreshQR = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ['qrCode'],
        onSuccess: (page) => {
            if (page.props.qrCode) {
                currentQR.value = page.props.qrCode;
            }
        }
    });
};

onMounted(() => {
    // Verificar status a cada 3 segundos
    statusInterval = setInterval(checkStatus, 3000);
    
    // Atualizar QR a cada 5 segundos (caso expire)
    qrInterval = setInterval(refreshQR, 5000);
});

onUnmounted(() => {
    if (statusInterval) {
        clearInterval(statusInterval);
    }
    if (qrInterval) {
        clearInterval(qrInterval);
    }
});
</script>

<template>
    <AppLayout :title="'QR Code - ' + whatsappNumber.nickname">
        <Head title="QR Code" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Conectar: {{ whatsappNumber.nickname }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Escaneie o QR Code com seu WhatsApp
                            </h3>
                            
                            <div v-if="currentQR" class="flex justify-center mb-4">
                                <img :src="currentQR" alt="QR Code" class="border-4 border-gray-200 rounded-lg" />
                            </div>
                            
                            <div v-else class="flex justify-center mb-4">
                                <div class="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
                                <p class="ml-4 text-sm text-gray-500 self-center">Aguardando QR Code...</p>
                            </div>

                            <p class="text-sm text-gray-500 mb-4">
                                1. Abra o WhatsApp no seu celular<br>
                                2. Toque em Menu ou Configurações<br>
                                3. Toque em Aparelhos conectados<br>
                                4. Toque em Conectar um aparelho<br>
                                5. Aponte seu celular para esta tela para capturar o código
                            </p>

                            <div class="mt-6">
                                <button
                                    @click="checkStatus"
                                    :disabled="checkingStatus"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <span v-if="checkingStatus">Verificando...</span>
                                    <span v-else>Verificar Conexão</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


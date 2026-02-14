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
    axios.get(route('whatsapp-numbers.status', props.whatsappNumber.jid))
        .then(response => {
            const data = response.data;
            if (data && data.IsConnected && data.IsLoggedIn) {
                // WhatsApp conectado com sucesso
                router.visit(route('whatsapp-numbers.index'));
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status:', error);
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
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Conectar: {{ whatsappNumber.nickname }}</h2>
                    <p class="text-slate-600 mt-1">Siga os passos para conectar seu WhatsApp</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-8">
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-3 mb-8">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-slate-900">
                                    Escaneie o QR Code com seu WhatsApp
                                </h3>
                            </div>

                            <div v-if="currentQR" class="flex justify-center mb-8">
                                <div class="relative p-4 bg-white rounded-2xl shadow-lg border border-slate-200">
                                    <img :src="`https://quickchart.io/qr?text=${encodeURIComponent(currentQR)}&size=300&margin=4&bgcolor=white&color=black&format=png`" alt="QR Code" class="w-64 h-64 rounded-xl" />
                                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/10 via-transparent to-cyan-400/10 rounded-2xl"></div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center mb-8">
                                <div class="relative">
                                    <div class="animate-spin rounded-full h-32 w-32 border-4 border-emerald-200 border-t-emerald-600"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500">
                                            <svg class="h-8 w-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-4 text-lg font-medium text-slate-700">Aguardando QR Code...</p>
                                <p class="mt-2 text-sm text-slate-600">O código será gerado automaticamente</p>
                            </div>

                            <div class="bg-slate-50/80 rounded-2xl p-6 mb-8 border border-slate-200/50">
                                <h4 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                                    <svg class="h-5 w-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Como conectar:
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-700">
                                    <div class="flex items-start space-x-3">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">1</span>
                                        <span>Abra o WhatsApp no seu celular</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">2</span>
                                        <span>Toque em Menu ou Configurações</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">3</span>
                                        <span>Toque em Aparelhos conectados</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">4</span>
                                        <span>Toque em Conectar um aparelho</span>
                                    </div>
                                    <div class="flex items-start space-x-3 md:col-span-2">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">5</span>
                                        <span>Aponte seu celular para esta tela para capturar o código QR</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                                <button
                                    @click="checkStatus"
                                    :disabled="checkingStatus"
                                    class="group inline-flex items-center px-8 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50"
                                >
                                    <svg v-if="checkingStatus" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span v-if="checkingStatus">Verificando Conexão...</span>
                                    <span v-else>Verificar Conexão</span>
                                </button>

                                <Link
                                    :href="route('whatsapp-numbers.index')"
                                    class="inline-flex items-center px-6 py-3 bg-white/80 backdrop-blur-sm rounded-xl font-semibold text-base text-slate-900 shadow-lg ring-1 ring-slate-200/50 hover:bg-white hover:shadow-xl transition-all duration-200"
                                >
                                    ← Voltar
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


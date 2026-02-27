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
const isConnected = ref(false);
const connectionError = ref(null);
const initialStatusChecked = ref(false);
let statusInterval = null;
let qrInterval = null;

const checkStatus = () => {
    // Sempre verificar status periodicamente
    // Removido: condições que impediam verificação

    checkingStatus.value = true;

    axios.get(route('whatsapp-numbers.status', props.whatsappNumber.id))
        .then(response => {
            const data = response.data;

            // Marcar que já fez pelo menos uma verificação
            if (!initialStatusChecked.value) {
                initialStatusChecked.value = true;
            }

            // Atualizar status da conexão
            if (data && data.IsConnected && data.IsLoggedIn) {
                if (!isConnected.value) {
                    console.log('🎉 WhatsApp conectado com sucesso!');
                    isConnected.value = true;
                    connectionError.value = null;
                }
                // Continuar verificando mesmo quando conectado (para detectar desconexões)
            } else if (data && initialStatusChecked.value) {
                console.log('📊 Status verificado:', {
                    IsConnected: data.IsConnected,
                    IsLoggedIn: data.IsLoggedIn,
                    initialCheckDone: initialStatusChecked.value
                });

                // Se perdeu conexão após estar conectado, mostrar erro
                if (isConnected.value && (!data.IsConnected || !data.IsLoggedIn)) {
                    console.log('❌ Conexão perdida!');
                    connectionError.value = 'Conexão perdida. Tente reconectar.';
                    isConnected.value = false;
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status:', error);

            // Mostrar erro para o usuário
            if (error.response && error.response.status === 404) {
                connectionError.value = 'Número não encontrado. Verifique se o número ainda existe.';
                clearIntervals();
            } else if (error.response && error.response.status === 403) {
                connectionError.value = 'Sem permissão para acessar este número.';
                clearIntervals();
            } else if (error.response && error.response.status >= 500) {
                connectionError.value = 'Erro no servidor. Tente novamente mais tarde.';
            } else {
                connectionError.value = 'Erro ao verificar status da conexão.';
            }
        })
        .finally(() => {
            checkingStatus.value = false;
        });
};

// Removido: funções de redirecionamento automático não são mais necessárias

const refreshQR = () => {
    // Atualizar QR periodicamente, mas não se há erro de conexão
    if (connectionError.value) {
        return;
    }

    router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ['qrCode'],
        onSuccess: (page) => {
            if (page.props.qrCode) {
                currentQR.value = page.props.qrCode;
            }
        },
        onError: (error) => {
            console.error('Erro ao atualizar QR:', error);
            // Se erro ao atualizar QR, pode ser problema de sessão
            if (error.response && error.response.status === 419) {
                console.error('Sessão expirada durante atualização do QR');
                clearIntervals();
            }
        }
    });
};

const clearIntervals = () => {
    if (statusInterval) {
        clearInterval(statusInterval);
        statusInterval = null;
    }
    if (qrInterval) {
        clearInterval(qrInterval);
        qrInterval = null;
    }
};

const manualCheckStatus = () => {
    checkStatus();
};

onMounted(() => {
    // Aguardar alguns segundos antes de iniciar verificações para dar tempo do QR carregar
    setTimeout(() => {
        // Verificar status a cada 3 segundos
        statusInterval = setInterval(checkStatus, 3000);
    }, 2000);

    // Atualizar QR a cada 5 segundos (caso expire)
    qrInterval = setInterval(refreshQR, 5000);
});

onUnmounted(() => {
    clearIntervals();
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

                            <div v-if="currentQR" class="flex justify-center mb-6">
                                <div class="relative p-4 bg-white rounded-2xl shadow-lg border border-slate-200">
                                    <img :src="`https://quickchart.io/qr?text=${encodeURIComponent(currentQR)}&size=300&margin=4&bgcolor=white&color=black&format=png`" alt="QR Code" class="w-64 h-64 rounded-xl" />
                                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/10 via-transparent to-cyan-400/10 rounded-2xl"></div>
                                </div>
                            </div>

                            <div v-if="currentQR" class="text-center mb-8">
                                <div class="inline-flex items-center text-amber-600 bg-amber-50 px-4 py-2 rounded-lg border border-amber-200">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <span class="text-sm font-medium">O QR Code pode expirar em alguns minutos. Se isso acontecer, volte à tela anterior e abra uma nova conexão.</span>
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
                                <p class="mt-4 text-lg font-medium text-slate-700">{{ initialStatusChecked ? 'Aguardando conexão...' : 'Preparando verificação...' }}</p>
                                <p class="mt-2 text-sm text-slate-600">{{ initialStatusChecked ? 'Escaneie o QR Code com seu WhatsApp' : 'O código será gerado automaticamente' }}</p>
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

                            <!-- Status da Conexão -->
                            <div v-if="isConnected" class="text-center mb-6">
                                <div class="inline-flex items-center text-green-600 bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-sm font-medium">WhatsApp Conectado! Seu número está pronto para receber mensagens.</span>
                                </div>
                            </div>

                            <div v-else-if="connectionError" class="text-center mb-6">
                                <div class="inline-flex items-center text-red-600 bg-red-50 px-4 py-2 rounded-lg border border-red-200">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <span class="text-sm font-medium">{{ connectionError }}</span>
                                </div>
                            </div>

                            <div v-else-if="initialStatusChecked" class="text-center mb-6">
                                <div class="inline-flex items-center text-blue-600 bg-blue-50 px-4 py-2 rounded-lg border border-blue-200">
                                    <svg class="animate-pulse w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 15h4.01M12 21h4.01"></path>
                                    </svg>
                                    <span class="text-sm font-medium">Aguardando Conexão - Escaneie o QR Code com seu WhatsApp para conectar</span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                                <Link
                                    :href="route('whatsapp-numbers.index')"
                                    class="inline-flex items-center px-6 py-3 bg-white/80 backdrop-blur-sm rounded-xl font-semibold text-base text-slate-900 shadow-lg ring-1 ring-slate-200/50 hover:bg-white hover:shadow-xl transition-all duration-200"
                                >
                                    ← Voltar para Lista
                                </Link>

                                <button
                                    @click="manualCheckStatus"
                                    :disabled="checkingStatus"
                                    class="group inline-flex items-center px-8 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50"
                                >
                                    <svg v-if="checkingStatus" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else-if="isConnected" class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span v-if="checkingStatus">Verificando...</span>
                                    <span v-else-if="isConnected">Verificar Novamente</span>
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


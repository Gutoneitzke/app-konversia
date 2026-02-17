<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    numbers: Array,
});

const connecting = ref({});

const connect = (number) => {
    connecting.value[number.id] = true;
    router.post(route('whatsapp-numbers.connect', number.jid), {}, {
        preserveScroll: true,
        onFinish: () => {
            connecting.value[number.id] = false;
        }
    });
};

const disconnect = (number) => {
    router.post(route('whatsapp-numbers.disconnect', number.jid), {}, {
        preserveScroll: true,
    });
};

const getStatusColor = (status) => {
    const colors = {
        'connected': 'bg-green-100 text-green-800',
        'connecting': 'bg-yellow-100 text-yellow-800',
        'disconnected': 'bg-gray-100 text-gray-800',
        'error': 'bg-red-100 text-red-800',
        'active': 'bg-blue-100 text-blue-800',
        'inactive': 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        'connected': 'Conectado',
        'connecting': 'Conectando',
        'disconnected': 'Desconectado',
        'error': 'Erro',
        'active': 'Ativo',
        'inactive': 'Inativo',
    };
    return texts[status] || status;
};
</script>

<template>
    <AppLayout title="Números WhatsApp">
        <Head title="Números WhatsApp" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Números WhatsApp</h2>
                    <p class="text-slate-600 mt-1">Gerencie seus números conectados</p>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-8">
                        <div v-if="numbers.length > 0" class="space-y-6">
                            <div v-for="number in numbers" :key="number.id" class="group relative rounded-3xl bg-slate-50/80 p-6 border border-slate-200/50 hover:bg-slate-100/80 hover:border-slate-300/50 transition-all duration-300 hover:shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold text-slate-900">{{ number.nickname }}</h3>
                                                <span :class="getStatusColor(number.status)" class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full mt-1">
                                                    {{ getStatusText(number.status) }}
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-slate-600 mt-3 font-medium">{{ number.phone_number }}</p>
                                        <p v-if="number.description" class="text-slate-500 mt-1 text-sm">{{ number.description }}</p>
                                        <div v-if="number.company" class="text-xs text-slate-400 mt-3 font-medium">
                                            Empresa: {{ number.company.name }}
                                        </div>
                                    </div>
                                    <div class="flex gap-3 mt-4">
                                        <button
                                            v-if="number.status !== 'connected' && number.status !== 'connecting'"
                                            @click="connect(number)"
                                            :disabled="connecting[number.id]"
                                            class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-sm text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50"
                                        >
                                            <span v-if="connecting[number.id]">Conectando...</span>
                                            <span v-else>Conectar</span>
                                        </button>
                                        <button
                                            v-if="number.status === 'connected'"
                                            @click="disconnect(number)"
                                            class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl font-semibold text-sm text-white shadow-lg hover:shadow-xl hover:from-red-600 hover:to-pink-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                                        >
                                            Desconectar
                                        </button>
                                        <Link
                                            v-if="number.status === 'connecting'"
                                            :href="route('whatsapp-numbers.qr', number.jid)"
                                            class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl font-semibold text-sm text-white shadow-lg hover:shadow-xl hover:from-green-600 hover:to-emerald-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                                        >
                                            Ver QR Code
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-16">
                            <div class="flex justify-center">
                                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-r from-slate-100 to-slate-200 shadow-lg">
                                    <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-xl font-bold text-slate-900">Nenhum número cadastrado</h3>
                            <p class="mt-2 text-slate-600 max-w-sm mx-auto">Cadastre um número WhatsApp para começar a gerenciar suas conversas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


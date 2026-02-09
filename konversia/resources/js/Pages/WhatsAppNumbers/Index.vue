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
    router.post(route('whatsapp-numbers.connect', number.api_key), {}, {
        preserveScroll: true,
        onFinish: () => {
            connecting.value[number.id] = false;
            setTimeout(() => {
                router.visit(route('whatsapp-numbers.qr', number.api_key));
            }, 2000);
        }
    });
};

const disconnect = (number) => {
    router.post(route('whatsapp-numbers.disconnect', number.api_key), {}, {
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Números WhatsApp
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <div v-if="numbers.length > 0" class="space-y-4">
                            <div v-for="number in numbers" :key="number.id" class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-medium text-gray-900">{{ number.nickname }}</h3>
                                            <span :class="getStatusColor(number.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ getStatusText(number.status) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">{{ number.phone_number }}</p>
                                        <p v-if="number.description" class="text-sm text-gray-400 mt-1">{{ number.description }}</p>
                                        <div v-if="number.company" class="text-xs text-gray-400 mt-2">
                                            Empresa: {{ number.company.name }}
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            v-if="number.status !== 'connected' && number.status !== 'connecting'"
                                            @click="connect(number)"
                                            :disabled="connecting[number.id]"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                        >
                                            <span v-if="connecting[number.id]">Conectando...</span>
                                            <span v-else>Conectar</span>
                                        </button>
                                        <button
                                            v-if="number.status === 'connected'"
                                            @click="disconnect(number)"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Desconectar
                                        </button>
                                        <Link
                                            v-if="number.status === 'connecting'"
                                            :href="route('whatsapp-numbers.qr', number.api_key)"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Ver QR Code
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum número cadastrado</h3>
                            <p class="mt-1 text-sm text-gray-500">Cadastre um número WhatsApp para começar.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


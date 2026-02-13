<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    companies: Array,
});

const showCreateForm = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
});

const submit = () => {
    form.post(route('companies.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            form.reset();
        },
    });
};

const toggleCreateForm = () => {
    showCreateForm.value = !showCreateForm.value;
    if (!showCreateForm.value) {
        form.reset();
    }
};
</script>

<template>
    <AppLayout title="Empresas">
        <Head title="Empresas" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">Empresas</h2>
                        <p class="text-slate-600 mt-1">Gerencie todas as empresas do sistema</p>
                    </div>
                </div>

                <button
                    @click="toggleCreateForm"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nova Empresa
                </button>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Formulário de Criação -->
                <div v-show="showCreateForm" class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Cadastrar Nova Empresa</h3>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Nome da Empresa</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                        placeholder="Digite o nome da empresa"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                        placeholder="empresa@email.com"
                                        required
                                    />
                                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Telefone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                        placeholder="(11) 99999-9999"
                                    />
                                    <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4 pt-4">
                                <button
                                    type="button"
                                    @click="toggleCreateForm"
                                    class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors duration-200"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Criando...</span>
                                    <span v-else>Criar Empresa</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Empresas -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Empresas Cadastradas</h3>
                                <p class="text-slate-600 text-sm">{{ companies.length }} empresa{{ companies.length !== 1 ? 's' : '' }} encontrada{{ companies.length !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>

                        <div v-if="companies.length > 0" class="space-y-4">
                            <div v-for="company in companies" :key="company.id" class="group relative rounded-2xl bg-gradient-to-br from-slate-50/80 to-white/60 p-6 border border-slate-200/50 hover:border-slate-300/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                                                <span class="text-white font-bold text-lg">{{ company.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 text-lg">{{ company.name }}</h4>
                                                <p class="text-slate-600">{{ company.email }}</p>
                                                <div class="flex items-center space-x-4 mt-1 text-sm">
                                                    <span v-if="company.phone" class="text-slate-500">{{ company.phone }}</span>
                                                    <span :class="company.active ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium'">
                                                        {{ company.active ? 'Ativa' : 'Inativa' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-slate-400 font-medium">
                                            Criada em {{ new Date(company.created_at).toLocaleDateString('pt-BR') }}
                                        </span>
                                        <Link
                                            :href="route('admin.companies.show', company.id)"
                                            class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200 transition-colors duration-200"
                                        >
                                            Ver Detalhes
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-16">
                            <div class="flex justify-center">
                                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-r from-slate-100 to-slate-200 shadow-lg">
                                    <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-xl font-bold text-slate-900">Nenhuma empresa cadastrada</h3>
                            <p class="mt-2 text-slate-600 max-w-sm mx-auto">Comece cadastrando a primeira empresa do sistema.</p>
                            <button
                                @click="toggleCreateForm"
                                class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Cadastrar Primeira Empresa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

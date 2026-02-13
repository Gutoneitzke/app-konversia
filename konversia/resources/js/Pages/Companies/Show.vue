<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    company: Object,
});

const form = useForm({
    name: props.company.name,
    email: props.company.email,
    phone: props.company.phone,
    active: props.company.active,
});

const submit = () => {
    form.put(route('admin.companies.update', props.company.id), {
        preserveScroll: true,
    });
};

const destroy = () => {
    if (confirm('Tem certeza que deseja excluir esta empresa?')) {
        router.delete(route('admin.companies.destroy', props.company.id));
    }
};
</script>

<template>
    <AppLayout :title="'Empresa - ' + company.name">
        <Head :title="'Empresa - ' + company.name" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">{{ company.name }}</h2>
                        <p class="text-slate-600 mt-1">Detalhes e configurações da empresa</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('admin.companies.index')"
                        class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors duration-200"
                    >
                        ← Voltar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Informações da Empresa -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Informações da Empresa</h3>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Nome da Empresa</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
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
                                    />
                                    <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Status</label>
                                    <select
                                        v-model="form.active"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                    >
                                        <option :value="true">Ativa</option>
                                        <option :value="false">Inativa</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-4">
                                <button
                                    type="button"
                                    @click="destroy"
                                    class="px-6 py-3 bg-red-100 text-red-700 rounded-xl font-semibold hover:bg-red-200 transition-colors duration-200"
                                >
                                    Excluir Empresa
                                </button>

                                <div class="flex space-x-4">
                                    <Link
                                        :href="route('admin.companies.index')"
                                        class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors duration-200"
                                    >
                                        Cancelar
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50"
                                    >
                                        <span v-if="form.processing">Salvando...</span>
                                        <span v-else>Salvar Alterações</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estatísticas da Empresa -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white/70 backdrop-blur-xl p-6 rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600">Usuários</p>
                                <p class="text-2xl font-bold text-slate-900">{{ company.users?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/70 backdrop-blur-xl p-6 rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-green-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600">Conversas</p>
                                <p class="text-2xl font-bold text-slate-900">{{ company.conversations?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/70 backdrop-blur-xl p-6 rounded-3xl shadow-xl ring-1 ring-white/20 border border-white/30">
                        <div class="flex items-center space-x-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600">Números WhatsApp</p>
                                <p class="text-2xl font-bold text-slate-900">{{ company.whatsapp_numbers?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usuários da Empresa -->
                <div v-if="company.users && company.users.length > 0" class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Usuários da Empresa</h3>
                                <p class="text-slate-600 text-sm">{{ company.users.length }} usuário{{ company.users.length !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-for="user in company.users" :key="user.id" class="flex items-center justify-between p-4 bg-slate-50/80 rounded-2xl">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500">
                                        <span class="text-white font-bold text-sm">{{ user.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ user.name }}</p>
                                        <p class="text-sm text-slate-600">{{ user.email }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-medium rounded-full">
                                    Ativo
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

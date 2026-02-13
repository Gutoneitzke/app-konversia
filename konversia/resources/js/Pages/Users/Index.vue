<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    company: Object,
});

const showCreateForm = ref(false);

const form = useForm({
    name: '',
    email: '',
    department_id: '',
});

const submit = () => {
    form.post(route('users.store'), {
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

const updateUserStatus = (userId, active) => {
    router.patch(route('users.update-status', userId), {
        active: active,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Funcionários">
        <Head title="Usuários" />

        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                    <h2 class="text-3xl font-bold text-slate-900">Funcionários</h2>
                    <p class="text-slate-600 mt-1">Gerencie os funcionários da empresa {{ company.name }}</p>
                    </div>
                </div>

                <button
                    @click="toggleCreateForm"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Funcionário
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
                            <h3 class="text-xl font-bold text-slate-900">Cadastrar Novo Funcionário</h3>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Nome Completo</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                        placeholder="João Silva Santos"
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
                                        placeholder="joao@empresa.com"
                                        required
                                    />
                                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-slate-800 mb-2">Departamento</label>
                                    <select
                                        v-model="form.department_id"
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50/50 px-4 py-3 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all duration-200"
                                        required
                                    >
                                        <option value="">Selecione um departamento</option>
                                        <option v-for="department in company.departments" :key="department.id" :value="department.id">
                                            {{ department.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.department_id" class="mt-2 text-sm text-red-600">{{ form.errors.department_id }}</div>
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
                                        <span v-else>Criar Funcionário</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Usuários -->
                <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-2xl ring-1 ring-white/20 border border-white/30 overflow-hidden">
                    <div class="px-8 py-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                            <h3 class="text-xl font-bold text-slate-900">Funcionários Cadastrados</h3>
                            <p class="text-slate-600 text-sm">{{ users.length }} funcionário{{ users.length !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>

                        <div v-if="users.length > 0" class="space-y-4">
                            <div v-for="user in users" :key="user.id" class="group relative rounded-2xl bg-gradient-to-br from-slate-50/80 to-white/60 p-6 border border-slate-200/50 hover:border-slate-300/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                                                <span class="text-white font-bold text-lg">{{ user.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 text-lg">{{ user.name }}</h4>
                                                <p class="text-slate-600">{{ user.email }}</p>
                                                <div class="flex items-center space-x-4 mt-1 text-sm">
                                                    <span class="text-slate-500">{{ user.department?.name }}</span>
                                                    <span :class="user.active ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium'">
                                                        {{ user.active ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <button
                                            @click="updateUserStatus(user.id, !user.active)"
                                            :class="user.active
                                                ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                                : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'"
                                            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-colors duration-200"
                                        >
                                            {{ user.active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                        <Link
                                            :href="route('users.show', user.id)"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-xl font-bold text-slate-900">Nenhum funcionário cadastrado</h3>
                            <p class="mt-2 text-slate-600 max-w-sm mx-auto">Comece cadastrando o primeiro funcionário da empresa.</p>
                            <button
                                @click="toggleCreateForm"
                                class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl font-semibold text-base text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Cadastrar Primeiro Funcionário
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

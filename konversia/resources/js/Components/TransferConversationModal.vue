<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    conversation: Object,
    departments: Array,
    users: Array,
});

const emit = defineEmits(['close', 'transferred']);

const form = useForm({
    to_department_id: '',
    assigned_to_user_id: '',
    notes: '',
});

const transferring = ref(false);

// Usuários do departamento selecionado
const availableUsers = computed(() => {
    if (!form.to_department_id || !props.users) return [];
    return props.users.filter(user =>
        user.departments && user.departments.some(dept => dept.id == form.to_department_id)
    );
});

const transfer = () => {
    if (!form.to_department_id) {
        alert('Selecione um departamento de destino');
        return;
    }

    transferring.value = true;

    form.post(route('conversations.transfer', props.conversation.id), {
        preserveScroll: true,
        onSuccess: (response) => {
            transferring.value = false;
            emit('transferred', response.props?.flash?.success || 'Conversa transferida com sucesso');
            form.reset();
        },
        onError: (errors) => {
            transferring.value = false;
            // Mostrar erros de validação se houver
            if (errors.response?.data?.errors) {
                const errorMessages = Object.values(errors.response.data.errors).flat();
                alert(errorMessages.join('\n'));
            } else {
                alert('Erro ao transferir conversa. Tente novamente.');
            }
        }
    });
};

const close = () => {
    if (!transferring.value) {
        form.reset();
        emit('close');
    }
};
</script>

<template>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="close">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Transferir Conversa
                    </h3>
                    <button @click="close" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-600">
                        <strong>Contato:</strong> {{ conversation.contact?.name || conversation.contact_name }}
                    </p>
                    <p class="text-sm text-gray-600">
                        <strong>Departamento atual:</strong> {{ conversation.department?.name }}
                    </p>
                </div>

                <form @submit.prevent="transfer">
                    <div class="space-y-4">
                        <!-- Departamento de destino -->
                        <div>
                            <label for="to_department_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Departamento de destino *
                            </label>
                            <select
                                id="to_department_id"
                                v-model="form.to_department_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            >
                                <option value="">Selecione um departamento</option>
                                <option
                                    v-for="department in departments"
                                    :key="department.id"
                                    :value="department.id"
                                    :disabled="department.id == conversation.department_id"
                                >
                                    {{ department.name }}
                                    <span v-if="department.id == conversation.department_id" class="text-gray-400">
                                        (atual)
                                    </span>
                                </option>
                            </select>
                            <p v-if="form.errors.to_department_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.to_department_id }}
                            </p>
                        </div>

                        <!-- Usuário para atribuir -->
                        <div v-if="form.to_department_id">
                            <label for="assigned_to_user_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Atribuir para (opcional)
                            </label>
                            <select
                                id="assigned_to_user_id"
                                v-model="form.assigned_to_user_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Não atribuir</option>
                                <option
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Apenas usuários do departamento selecionado
                            </p>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Observações (opcional)
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Motivo da transferência..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button
                            type="button"
                            @click="close"
                            :disabled="transferring"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="transferring || !form.to_department_id"
                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="transferring" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Transferindo...
                            </span>
                            <span v-else>
                                Transferir
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
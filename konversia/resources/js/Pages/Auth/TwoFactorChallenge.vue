<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Autenticação de Dois Fatores - Konversia" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm text-slate-700">
            <template v-if="! recovery">
                Por favor, confirme o acesso à sua conta inserindo o código de autenticação fornecido pelo seu aplicativo autenticador.
            </template>

            <template v-else>
                Por favor, confirme o acesso à sua conta inserindo um dos seus códigos de recuperação de emergência.
            </template>
        </div>

        <form @submit.prevent="submit">
            <div v-if="! recovery">
                <InputLabel for="code" value="Código" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    autofocus
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div v-else>
                <InputLabel for="recovery_code" value="Código de Recuperação" />
                <TextInput
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <button type="button" class="text-sm text-slate-700 hover:text-slate-900 transition-colors underline cursor-pointer" @click.prevent="toggleRecovery">
                    <template v-if="! recovery">
                        Usar código de recuperação
                    </template>

                    <template v-else>
                        Usar código de autenticação
                    </template>
                </button>

                <button type="submit" class="ms-4 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-8 py-3 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-25" :disabled="form.processing">
                    Entrar
                </button>
            </div>
        </form>
    </AuthenticationCard>
</template>

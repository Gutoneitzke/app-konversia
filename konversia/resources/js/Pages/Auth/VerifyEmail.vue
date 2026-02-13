<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verificar Email - Konversia" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm text-slate-700">
            Antes de continuar, você pode verificar seu endereço de email clicando no link que acabamos de enviar para você? Se não recebeu o email, ficaremos felizes em enviar outro.
        </div>

        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600">
            Um novo link de verificação foi enviado para o endereço de email fornecido nas suas configurações de perfil.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                        <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-8 py-3 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-25" :disabled="form.processing">
                    Reenviar Email de Verificação
                </button>

                <div>
                    <Link
                        :href="route('profile.show')"
                        class="text-sm text-slate-700 hover:text-slate-900 transition-colors"
                    >
                        Editar Perfil
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm text-slate-700 hover:text-slate-900 transition-colors ms-2"
                    >
                        Sair
                    </Link>
                </div>
            </div>
        </form>
    </AuthenticationCard>
</template>

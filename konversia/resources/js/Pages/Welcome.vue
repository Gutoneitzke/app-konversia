<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div>
        <Head title="Konversia - Multi-atendimento WhatsApp" />

        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 dark:from-gray-900 dark:via-gray-800 dark:to-green-900">
        <!-- Header -->
        <header class="relative z-10 px-6 py-4">
            <nav class="mx-auto max-w-7xl flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-blue-500">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Konversia</span>
                </div>

                <nav v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                        class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors"
                            >
                            Entrar
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                            class="rounded-lg bg-gradient-to-r from-green-500 to-blue-500 px-4 py-2 text-sm font-medium text-white hover:from-green-600 hover:to-blue-600 transition-all"
                            >
                            Cadastrar
                            </Link>
                        </template>
                </nav>
                    </nav>
                </header>

        <!-- Hero Section -->
        <section class="relative px-6 py-20 sm:py-32">
            <div class="mx-auto max-w-7xl text-center">
                <div class="mx-auto max-w-3xl">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                        Multi-atendimento
                        <span class="bg-gradient-to-r from-green-600 to-blue-600 bg-clip-text text-transparent">
                            WhatsApp
                        </span>
                        para Empresas
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Gerencie múltiplas conversas do WhatsApp simultaneamente. Aumente sua produtividade,
                        melhore o atendimento ao cliente e escale seu negócio com nossa plataforma inteligente
                        de multi-atendimento.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="rounded-lg bg-gradient-to-r from-green-500 to-blue-500 px-8 py-3 text-base font-semibold text-white shadow-sm hover:from-green-600 hover:to-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all"
                        >
                            Começar Gratuitamente
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="rounded-lg bg-white px-8 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 transition-all"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>
                            </div>

                <!-- Hero Image/Dashboard Preview -->
                <div class="mt-16 flow-root sm:mt-24">
                    <div class="relative mx-auto max-w-4xl rounded-xl bg-white p-2 shadow-2xl ring-1 ring-gray-900/10 dark:bg-gray-800 dark:ring-gray-700">
                        <div class="rounded-lg bg-gradient-to-r from-green-500/10 to-blue-500/10 p-8">
                            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                <div class="space-y-4">
                                    <div class="h-4 w-3/4 rounded bg-gradient-to-r from-green-200 to-blue-200 dark:from-green-800 dark:to-blue-800"></div>
                                    <div class="h-4 w-1/2 rounded bg-gradient-to-r from-green-200 to-blue-200 dark:from-green-800 dark:to-blue-800"></div>
                                    <div class="h-32 rounded-lg bg-gradient-to-r from-green-100 to-blue-100 dark:from-green-900 dark:to-blue-900 flex items-center justify-center">
                                        <svg class="h-12 w-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="h-4 w-2/3 rounded bg-gradient-to-r from-blue-200 to-green-200 dark:from-blue-800 dark:to-green-800"></div>
                                    <div class="h-4 w-3/4 rounded bg-gradient-to-r from-blue-200 to-green-200 dark:from-blue-800 dark:to-green-800"></div>
                                    <div class="h-32 rounded-lg bg-gradient-to-r from-blue-100 to-green-100 dark:from-blue-900 dark:to-green-900 flex items-center justify-center">
                                        <svg class="h-12 w-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Por que escolher o Konversia?
                    </h2>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Nossa plataforma foi desenvolvida para empresas que precisam gerenciar múltiplas conversas
                        simultaneamente e oferecer um atendimento excepcional.
                    </p>
                            </div>

                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <svg class="h-5 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Atendimento Simultâneo
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                <p class="flex-auto">
                                    Gerencie centenas de conversas ao mesmo tempo. Nunca mais perca uma mensagem
                                    importante ou deixe um cliente esperando.
                                </p>
                            </dd>
                            </div>

                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <svg class="h-5 w-5 flex-none text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Respostas Automáticas
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                <p class="flex-auto">
                                    Configure respostas automáticas para perguntas frequentes e reduza o tempo
                                    de resposta em até 80%.
                                </p>
                            </dd>
                            </div>

                        <div class="flex flex-col">
                            <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                <svg class="h-5 w-5 flex-none text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Relatórios Detalhados
                            </dt>
                            <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                <p class="flex-auto">
                                    Acompanhe métricas importantes como tempo de resposta, satisfação do cliente
                                    e volume de conversas.
                                </p>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="py-24 sm:py-32 bg-gray-50 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Planos e Preços
                    </h2>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Escolha o plano ideal para seu negócio. Comece gratuitamente e escale conforme sua necessidade.
                    </p>
                </div>

                <div class="mx-auto mt-16 grid max-w-lg grid-cols-1 gap-y-6 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:gap-x-8">
                    <!-- Plano Mensal -->
                    <div class="flex flex-col justify-between rounded-3xl bg-white p-8 shadow-xl ring-1 ring-gray-900/10 dark:bg-gray-800 dark:ring-gray-700 sm:p-10">
                        <div>
                            <h3 class="text-lg font-semibold leading-8 text-gray-900 dark:text-white">
                                Plano Mensal
                            </h3>
                            <div class="mt-4 flex items-baseline gap-x-2">
                                <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                                    R$ 97
                                </span>
                                <span class="text-base text-gray-500 dark:text-gray-400">
                                    /mês
                                </span>
                            </div>
                            <p class="mt-6 text-base leading-7 text-gray-600 dark:text-gray-300">
                                Perfeito para pequenas empresas que estão começando com multi-atendimento.
                            </p>
                            <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Até 3 usuários simultâneos
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Até 1.000 conversas/mês
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Respostas automáticas
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Relatórios básicos
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Suporte por email
                                </li>
                            </ul>
                        </div>
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="mt-8 block rounded-lg bg-gradient-to-r from-green-500 to-blue-500 px-6 py-3 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:from-green-600 hover:to-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all"
                        >
                            Começar Agora
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="mt-8 block rounded-lg bg-gradient-to-r from-green-500 to-blue-500 px-6 py-3 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:from-green-600 hover:to-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>

                    <!-- Plano Anual -->
                    <div class="flex flex-col justify-between rounded-3xl bg-gradient-to-r from-green-500 to-blue-500 p-8 shadow-xl ring-1 ring-gray-900/10 sm:p-10">
                        <div class="relative">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-900 shadow-sm">
                                Mais Popular
                            </div>
                            <h3 class="text-lg font-semibold leading-8 text-white">
                                Plano Anual
                            </h3>
                            <div class="mt-4 flex items-baseline gap-x-2">
                                <span class="text-4xl font-bold tracking-tight text-white">
                                    R$ 997
                                </span>
                                <span class="text-base text-green-100">
                                    /ano
                                </span>
                            </div>
                            <p class="mt-6 text-base leading-7 text-green-100">
                                Ideal para empresas em crescimento que precisam de recursos avançados.
                            </p>
                            <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-green-100">
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Até 10 usuários simultâneos
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Conversas ilimitadas
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Respostas automáticas avançadas
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Relatórios avançados
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Suporte prioritário 24/7
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Integração com CRM
                                </li>
                                <li class="flex gap-x-3">
                                    <svg class="h-6 w-5 flex-none text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    API completa
                                </li>
                            </ul>
                        </div>
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="mt-8 block rounded-lg bg-white px-6 py-3 text-center text-sm font-semibold leading-6 text-gray-900 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all"
                        >
                            Começar Agora
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="mt-8 block rounded-lg bg-white px-6 py-3 text-center text-sm font-semibold leading-6 text-gray-900 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Pronto para revolucionar seu atendimento?
                    </h2>
                    <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Junte-se a centenas de empresas que já transformaram seu atendimento ao cliente
                        com o Konversia.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="rounded-lg bg-gradient-to-r from-green-500 to-blue-500 px-8 py-3 text-base font-semibold text-white shadow-sm hover:from-green-600 hover:to-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all"
                        >
                            Começar Gratuitamente
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="rounded-lg bg-white px-8 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 transition-all"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
                <div class="flex justify-center space-x-6 md:order-2">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-r from-green-500 to-blue-500">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white">Konversia</span>
                    </div>
                </div>
                <div class="mt-8 md:order-1 md:mt-0">
                    <p class="text-center text-xs leading-5 text-gray-400">
                        &copy; 2026 Konversia. Todos os direitos reservados.
                    </p>
            </div>
        </div>
        </footer>
        </div>
    </div>
</template>

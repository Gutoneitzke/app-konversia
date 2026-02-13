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

        <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white via-cyan-50 to-emerald-50 relative overflow-hidden">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <!-- Large gradient orbs -->
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute top-1/2 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-400/15 to-cyan-500/15 rounded-full blur-3xl animate-pulse delay-1000"></div>
                <div class="absolute -bottom-40 right-1/4 w-72 h-72 bg-gradient-to-bl from-purple-400/10 to-pink-500/10 rounded-full blur-3xl animate-pulse delay-500"></div>

                <!-- Additional decorative elements -->
                <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-gradient-to-r from-indigo-300/10 to-blue-400/10 rounded-full blur-2xl animate-pulse delay-700"></div>
                <div class="absolute bottom-1/3 right-1/3 w-48 h-48 bg-gradient-to-l from-cyan-300/15 to-teal-400/15 rounded-full blur-2xl animate-pulse delay-300"></div>

                <!-- Subtle mesh pattern overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent opacity-30"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10">
        <!-- Header -->
        <header class="relative z-10 px-6 py-6">
            <nav class="mx-auto max-w-7xl flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-slate-800">Konversia</span>
                </div>

                <nav v-if="canLogin" class="flex items-center space-x-6">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-all duration-200"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-all duration-200"
                            >
                            Entrar
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                            class="rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-6 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 transition-all duration-200 transform hover:-translate-y-0.5"
                            >
                            Cadastrar
                            </Link>
                        </template>
                </nav>
                    </nav>
                </header>

        <!-- Hero Section -->
        <section class="relative px-6 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl text-center">
                <div class="mx-auto max-w-4xl">
                    <div class="mb-8">
                        <span class="inline-flex items-center rounded-full bg-emerald-100/80 px-4 py-1.5 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200/50">
                            🚀 Plataforma de Multi-atendimento WhatsApp
                        </span>
                    </div>
                    <h1 class="text-5xl font-extrabold tracking-tight text-slate-900 sm:text-7xl lg:text-8xl">
                        Multi-atendimento
                        <span class="bg-gradient-to-r from-emerald-600 via-cyan-600 to-blue-600 bg-clip-text text-transparent">
                            WhatsApp
                        </span>
                        para Empresas
                    </h1>
                    <p class="mt-8 text-xl leading-relaxed text-slate-600 max-w-2xl mx-auto">
                        Gerencie centenas de conversas simultaneamente. Aumente sua produtividade,
                        melhore o atendimento ao cliente e escale seu negócio com nossa plataforma inteligente
                        de multi-atendimento.
                    </p>
                    <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="group rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-10 py-4 text-lg font-semibold text-white shadow-xl hover:shadow-2xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200 transform hover:-translate-y-1"
                        >
                            Começar Gratuitamente
                            <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="rounded-2xl bg-white/80 backdrop-blur-sm px-10 py-4 text-lg font-semibold text-slate-900 shadow-lg ring-1 ring-slate-200/50 hover:bg-white hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500 transition-all duration-200"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>
                            </div>

                <!-- Hero Image/Dashboard Preview -->
                <div class="mt-20 flow-root sm:mt-28">
                    <div class="relative mx-auto max-w-5xl">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/20 via-cyan-400/20 to-blue-400/20 blur-3xl rounded-3xl"></div>
                        <div class="relative rounded-3xl bg-white/70 backdrop-blur-xl p-3 shadow-2xl ring-1 ring-white/20 border border-white/30">
                            <div class="rounded-2xl bg-gradient-to-br from-slate-50/80 to-white/60 p-10">
                                <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
                                    <div class="space-y-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></div>
                                            <div class="h-4 w-3/4 rounded-lg bg-gradient-to-r from-emerald-200 to-cyan-200"></div>
                                        </div>
                                        <div class="h-4 w-1/2 rounded-lg bg-gradient-to-r from-slate-200 to-slate-300"></div>
                                        <div class="h-40 rounded-2xl bg-gradient-to-br from-emerald-50 to-cyan-50 border border-emerald-100/50 flex items-center justify-center shadow-sm">
                                            <svg class="h-16 w-16 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex space-x-2">
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-cyan-400 to-blue-400"></div>
                                            <div class="h-8 w-8 rounded-full bg-slate-300"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-3 w-3 rounded-full bg-blue-500 animate-pulse"></div>
                                            <div class="h-4 w-2/3 rounded-lg bg-gradient-to-r from-blue-200 to-emerald-200"></div>
                                        </div>
                                        <div class="h-4 w-4/5 rounded-lg bg-gradient-to-r from-slate-200 to-slate-300"></div>
                                        <div class="h-40 rounded-2xl bg-gradient-to-br from-blue-50 to-emerald-50 border border-blue-100/50 flex items-center justify-center shadow-sm">
                                            <svg class="h-16 w-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="h-2 w-full rounded-full bg-gradient-to-r from-emerald-200 to-cyan-200"></div>
                                            <div class="h-2 w-3/4 rounded-full bg-gradient-to-r from-cyan-200 to-blue-200"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-32 sm:py-40">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        Por que escolher o Konversia?
                    </h2>
                    <p class="mt-8 text-xl leading-relaxed text-slate-600">
                        Nossa plataforma foi desenvolvida para empresas que precisam gerenciar múltiplas conversas
                        simultaneamente e oferecer um atendimento excepcional.
                    </p>
                </div>

                <div class="mx-auto mt-20 max-w-5xl">
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div class="group relative rounded-3xl bg-white/60 backdrop-blur-sm p-8 shadow-lg ring-1 ring-white/20 border border-white/30 hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                            <div class="flex items-center space-x-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900">Atendimento Simultâneo</h3>
                            </div>
                            <p class="mt-6 text-slate-600 leading-relaxed">
                                Gerencie centenas de conversas ao mesmo tempo. Nunca mais perca uma mensagem
                                importante ou deixe um cliente esperando.
                            </p>
                        </div>

                        <div class="group relative rounded-3xl bg-white/60 backdrop-blur-sm p-8 shadow-lg ring-1 ring-white/20 border border-white/30 hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                            <div class="flex items-center space-x-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-500 shadow-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900">Respostas Automáticas</h3>
                            </div>
                            <p class="mt-6 text-slate-600 leading-relaxed">
                                Configure respostas automáticas para perguntas frequentes e reduza o tempo
                                de resposta em até 80%.
                            </p>
                        </div>

                        <div class="group relative rounded-3xl bg-white/60 backdrop-blur-sm p-8 shadow-lg ring-1 ring-white/20 border border-white/30 hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
                            <div class="flex items-center space-x-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-purple-500 shadow-lg">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900">Relatórios Detalhados</h3>
                            </div>
                            <p class="mt-6 text-slate-600 leading-relaxed">
                                Acompanhe métricas importantes como tempo de resposta, satisfação do cliente
                                e volume de conversas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="py-32 sm:py-40 bg-gradient-to-b from-slate-50/50 to-white">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        Planos e Preços
                    </h2>
                    <p class="mt-8 text-xl leading-relaxed text-slate-600">
                        Escolha o plano ideal para seu negócio. Comece gratuitamente e escale conforme sua necessidade.
                    </p>
                </div>

                <div class="mx-auto mt-20 grid max-w-5xl grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Plano Mensal -->
                    <div class="relative flex flex-col justify-between rounded-3xl bg-white/70 backdrop-blur-xl p-8 shadow-xl ring-1 ring-white/20 border border-white/30 sm:p-10 hover:shadow-2xl transition-all duration-300 group">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-slate-900">
                                    Plano Mensal
                                </h3>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">Recomendado</span>
                            </div>
                            <div class="mt-6 flex items-baseline gap-x-2">
                                <span class="text-5xl font-extrabold text-slate-900">
                                    R$ 97
                                </span>
                                <span class="text-lg text-slate-500">
                                    /mês
                                </span>
                            </div>
                            <p class="mt-6 text-slate-600 leading-relaxed">
                                Perfeito para pequenas empresas que estão começando com multi-atendimento.
                            </p>
                            <ul role="list" class="mt-8 space-y-4 text-sm leading-6 text-slate-600">
                                <li class="flex gap-x-3">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    Até 3 usuários simultâneos
                                </li>
                                <li class="flex gap-x-3">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    Até 1.000 conversas/mês
                                </li>
                                <li class="flex gap-x-3">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    Respostas automáticas
                                </li>
                                <li class="flex gap-x-3">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    Relatórios básicos
                                </li>
                                <li class="flex gap-x-3">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    Suporte por email
                                </li>
                            </ul>
                        </div>
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="mt-10 block rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-8 py-4 text-center text-base font-semibold text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200 group-hover:shadow-2xl"
                        >
                            Começar Agora
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="mt-10 block rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-8 py-4 text-center text-base font-semibold text-white shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>

                    <!-- Plano Anual -->
                    <div class="relative flex flex-col justify-between rounded-3xl bg-gradient-to-br from-emerald-500 via-cyan-500 to-blue-500 p-8 shadow-2xl sm:p-10 group hover:shadow-3xl transition-all duration-300">
                        <div class="relative">
                            <div class="absolute -top-6 left-1/2 -translate-x-1/2 rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-xl">
                                🚀 Mais Popular
                            </div>
                            <div class="pt-6">
                                <h3 class="text-2xl font-bold text-white">
                                    Plano Anual
                                </h3>
                                <div class="mt-6 flex items-baseline gap-x-2">
                                    <span class="text-6xl font-extrabold text-white">
                                        R$ 997
                                    </span>
                                    <span class="text-xl text-emerald-100">
                                        /ano
                                    </span>
                                </div>
                                <p class="mt-6 text-lg leading-relaxed text-emerald-50">
                                    Ideal para empresas em crescimento que precisam de recursos avançados.
                                </p>
                                <ul role="list" class="mt-10 space-y-4 text-base leading-7 text-emerald-50">
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Até 10 usuários simultâneos
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Conversas ilimitadas
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Respostas automáticas avançadas
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Relatórios avançados
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Suporte prioritário 24/7
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        Integração com CRM
                                    </li>
                                    <li class="flex gap-x-4">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        API completa
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="mt-12 block rounded-2xl bg-white px-8 py-4 text-center text-lg font-bold text-slate-900 shadow-xl hover:shadow-2xl hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all duration-200 group-hover:shadow-3xl"
                        >
                            Começar Agora
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="mt-12 block rounded-2xl bg-white px-8 py-4 text-center text-lg font-bold text-slate-900 shadow-xl hover:shadow-2xl hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-all duration-200"
                        >
                            Acessar Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-32 sm:py-40">
            <div class="mx-auto max-w-7xl px-6">
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/10 via-cyan-400/10 to-blue-400/10 blur-3xl rounded-3xl"></div>
                    <div class="relative mx-auto max-w-4xl text-center rounded-3xl bg-white/60 backdrop-blur-xl px-8 py-16 shadow-xl ring-1 ring-white/20 border border-white/30">
                        <h2 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-6xl">
                            Pronto para revolucionar seu atendimento?
                        </h2>
                        <p class="mt-8 text-xl leading-relaxed text-slate-600 max-w-2xl mx-auto">
                            Junte-se a centenas de empresas que já transformaram seu atendimento ao cliente
                            com o Konversia.
                        </p>
                        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('register')"
                                class="group rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-10 py-4 text-lg font-semibold text-white shadow-xl hover:shadow-2xl hover:from-emerald-600 hover:to-cyan-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 transition-all duration-200 transform hover:-translate-y-1"
                            >
                                Começar Gratuitamente
                                <span class="ml-2 transition-transform group-hover:translate-x-1">→</span>
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="rounded-2xl bg-white/80 backdrop-blur-sm px-10 py-4 text-lg font-semibold text-slate-900 shadow-lg ring-1 ring-slate-200/50 hover:bg-white hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500 transition-all duration-200"
                            >
                                Acessar Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900">
            <div class="mx-auto max-w-7xl px-6 py-16 md:flex md:items-center md:justify-between lg:px-8">
                <div class="flex justify-center space-x-6 md:order-2">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white">Konversia</span>
                    </div>
                </div>
                <div class="mt-8 md:order-1 md:mt-0">
                    <p class="text-center text-sm leading-6 text-slate-400">
                        &copy; 2026 Konversia. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>
            </div>
        </div>
    </div>
</template>

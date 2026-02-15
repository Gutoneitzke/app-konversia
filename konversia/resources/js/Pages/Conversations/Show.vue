<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ChatConversation from '@/Components/ChatConversation.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    conversation: Object,
});
</script>

<template>
    <AppLayout title="Conversa">
        <Head title="Conversa" />

        <template #header>
            <div class="flex items-center space-x-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Conversa</h2>
                    <p class="text-slate-600 mt-1">Chat individual com contato</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <ChatConversation
                        :conversation="conversation"
                        :show-header="false"
                        :show-back-button="true"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>


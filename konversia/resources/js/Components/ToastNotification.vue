<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    message: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'error' // error, success, warning, info
    },
    duration: {
        type: Number,
        default: 5000 // milliseconds
    },
    show: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['close'])

const visible = ref(props.show)
const timeout = ref(null)

const close = () => {
    visible.value = false
    emit('close')
}

const startTimer = () => {
    if (props.duration > 0) {
        timeout.value = setTimeout(() => {
            close()
        }, props.duration)
    }
}

const stopTimer = () => {
    if (timeout.value) {
        clearTimeout(timeout.value)
        timeout.value = null
    }
}

const getToastClasses = () => {
    const baseClasses = 'flex items-center p-4 mb-4 text-sm rounded-lg shadow-lg transition-all duration-300 ease-in-out max-w-md'

    const typeClasses = {
        error: 'bg-red-50 text-red-800 border border-red-200',
        success: 'bg-green-50 text-green-800 border border-green-200',
        warning: 'bg-yellow-50 text-yellow-800 border border-yellow-200',
        info: 'bg-blue-50 text-blue-800 border border-blue-200'
    }

    return `${baseClasses} ${typeClasses[props.type] || typeClasses.error}`
}

const getIconClasses = () => {
    const baseClasses = 'flex-shrink-0 w-5 h-5 mr-3'

    const typeClasses = {
        error: 'text-red-400',
        success: 'text-green-400',
        warning: 'text-yellow-400',
        info: 'text-blue-400'
    }

    return `${baseClasses} ${typeClasses[props.type] || typeClasses.error}`
}

const getIconSvg = () => {
    const icons = {
        error: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>`,
        success: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
        warning: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>`,
        info: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`
    }

    return icons[props.type] || icons.error
}

onMounted(() => {
    if (props.show) {
        startTimer()
    }
})

onUnmounted(() => {
    stopTimer()
})

// Watch for show prop changes
watch(() => props.show, (newShow) => {
    visible.value = newShow
    if (newShow) {
        startTimer()
    } else {
        stopTimer()
    }
})
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform translate-y-2"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-0 transform translate-y-2"
        leave-to-class="opacity-0 transform -translate-y-2"
    >
        <div v-if="visible" :class="getToastClasses()">
            <div :class="getIconClasses()" v-html="`<svg fill='none' stroke='currentColor' viewBox='0 0 24 24'>${getIconSvg()}</svg>`"></div>
            <div class="flex-1">{{ message }}</div>
            <button
                @click="close"
                class="ml-3 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </transition>
</template>
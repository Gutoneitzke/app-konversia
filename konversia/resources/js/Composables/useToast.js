import { ref } from 'vue'

const toasts = ref([])

export function useToast() {
    const addToast = (message, type = 'error', duration = 5000) => {
        const id = Date.now() + Math.random()
        const toast = {
            id,
            message,
            type,
            duration
        }

        toasts.value.push(toast)

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(id)
            }, duration)
        }

        return id
    }

    const removeToast = (id) => {
        const index = toasts.value.findIndex(toast => toast.id === id)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }

    const clearAllToasts = () => {
        toasts.value = []
    }

    return {
        toasts,
        addToast,
        removeToast,
        clearAllToasts
    }
}
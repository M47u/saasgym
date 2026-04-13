import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const sidebarOpen = ref(true);
    const toasts = ref([]);
    let _toastId = 0;

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function toast(message, type = 'success', duration = 3500) {
        const id = ++_toastId;
        toasts.value.push({ id, message, type });
        setTimeout(() => dismiss(id), duration);
        return id;
    }

    function dismiss(id) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return { sidebarOpen, toasts, toggleSidebar, toast, dismiss };
});

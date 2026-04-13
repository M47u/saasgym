import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('auth_token') || null);
    const user  = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'));

    const isAuthenticated = computed(() => !!token.value);
    const isAdmin         = computed(() => user.value?.rol === 'admin');
    const isEntrenador    = computed(() => user.value?.rol === 'entrenador');
    const isSuperAdmin    = computed(() => user.value?.rol === 'super_admin');
    const isGimnasioUser  = computed(() => !isSuperAdmin.value && !!user.value);

    function _persist(t, u) {
        token.value = t;
        user.value  = u;
        localStorage.setItem('auth_token', t);
        localStorage.setItem('auth_user', JSON.stringify(u));
    }

    async function login(email, password) {
        const { data } = await axios.post('/login', { email, password });
        _persist(data.token, data.user);
        return data.user;
    }

    async function register(payload) {
        const { data } = await axios.post('/register', payload);
        _persist(data.token, data.user);
        return data.user;
    }

    async function logout() {
        try {
            await axios.post('/logout');
        } finally {
            token.value = null;
            user.value  = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        }
    }

    async function fetchMe() {
        const { data } = await axios.get('/me');
        user.value = data;
        localStorage.setItem('auth_user', JSON.stringify(data));
        return data;
    }

    return {
        token, user,
        isAuthenticated, isAdmin, isEntrenador, isSuperAdmin, isGimnasioUser,
        login, register, logout, fetchMe,
    };
});

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const step = ref(1);
const loading = ref(false);
const error = ref('');

const form = ref({
    gimnasio_nombre: '',
    gimnasio_email: '',
    gimnasio_telefono: '',
    gimnasio_direccion: '',
    admin_nombre: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
});

function nextStep() {
    error.value = '';
    if (!form.value.gimnasio_nombre || !form.value.gimnasio_email) {
        error.value = 'Completá el nombre y email del gimnasio.';
        return;
    }
    step.value = 2;
}

async function submit() {
    error.value = '';
    if (form.value.admin_password !== form.value.admin_password_confirmation) {
        error.value = 'Las contraseñas no coinciden.';
        return;
    }
    loading.value = true;
    try {
        await auth.register(form.value);
        router.push('/dashboard');
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            error.value = Object.values(errs).flat().join(' ');
        } else {
            error.value = e.response?.data?.message || 'Error al registrar.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="w-full max-w-sm">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Crear cuenta</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ step === 1 ? 'Datos de tu gimnasio' : 'Datos del administrador' }}
            </p>
            <!-- Step indicator -->
            <div class="flex gap-1.5 mt-4">
                <div :class="['h-1 flex-1 rounded-full transition-colors', step >= 1 ? 'bg-violet-600' : 'bg-gray-200']" />
                <div :class="['h-1 flex-1 rounded-full transition-colors', step >= 2 ? 'bg-violet-600' : 'bg-gray-200']" />
            </div>
        </div>

        <!-- Error -->
        <transition name="slide-up">
            <div
                v-if="error"
                class="flex items-center gap-2 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm mb-5"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0">
                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                </svg>
                {{ error }}
            </div>
        </transition>

        <!-- Step 1: Gimnasio -->
        <transition name="fade" mode="out-in">
            <form v-if="step === 1" key="step1" @submit.prevent="nextStep" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre del gimnasio *</label>
                    <input v-model="form.gimnasio_nombre" type="text" required placeholder="Ej: PowerGym" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email del gimnasio *</label>
                    <input v-model="form.gimnasio_email" type="email" required placeholder="info@gimnasio.com" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
                    <input v-model="form.gimnasio_telefono" type="tel" placeholder="+54 9 11 0000-0000" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Dirección</label>
                    <input v-model="form.gimnasio_direccion" type="text" placeholder="Av. Principal 1234" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-colors">
                    Siguiente
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </form>

            <!-- Step 2: Admin -->
            <form v-else key="step2" @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tu nombre *</label>
                    <input v-model="form.admin_nombre" type="text" required placeholder="Juan García" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tu email *</label>
                    <input v-model="form.admin_email" type="email" required placeholder="admin@gimnasio.com" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña *</label>
                    <input v-model="form.admin_password" type="password" required placeholder="Mínimo 8 caracteres" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar contraseña *</label>
                    <input v-model="form.admin_password_confirmation" type="password" required placeholder="Repetí la contraseña" class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition" />
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="step = 1; error = ''" class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                        Volver
                    </button>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg v-if="loading" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ loading ? 'Creando...' : 'Crear cuenta' }}
                    </button>
                </div>
            </form>
        </transition>

        <p class="mt-6 text-center text-sm text-gray-500">
            ¿Ya tenés cuenta?
            <router-link to="/login" class="font-medium text-violet-600 hover:text-violet-700 transition-colors">
                Iniciá sesión
            </router-link>
        </p>
    </div>
</template>

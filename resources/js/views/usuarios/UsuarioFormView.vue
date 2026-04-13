<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const ui     = useUiStore();

const isEdit  = computed(() => !!route.params.id);
const title   = computed(() => isEdit.value ? 'Editar usuario' : 'Nuevo usuario');
const loading = ref(false);
const saving  = ref(false);
const errors  = ref({});

const form = ref({
    name:     '',
    email:    '',
    password: '',
    rol:      'entrenador',
    activo:   true,
});

onMounted(async () => {
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/usuarios/${route.params.id}`);
        const u = data.data ?? data;
        form.value = {
            name:     u.nombre || '',
            email:    u.email || '',
            password: '',
            rol:      u.rol || 'entrenador',
            activo:   u.activo ?? true,
        };
    } catch {
        ui.toast('Usuario no encontrado.', 'error');
        router.push('/usuarios');
    } finally {
        loading.value = false;
    }
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isEdit.value && !payload.password) delete payload.password;

        if (isEdit.value) {
            await axios.put(`/usuarios/${route.params.id}`, payload);
            ui.toast('Usuario actualizado.', 'success');
        } else {
            await axios.post('/usuarios', payload);
            ui.toast('Usuario creado.', 'success');
        }
        router.push('/usuarios');
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            errors.value = errs;
        } else {
            ui.toast(e.response?.data?.message || 'Error al guardar.', 'error');
        }
    } finally {
        saving.value = false;
    }
}

function fieldError(field) {
    return errors.value[field]?.[0];
}
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <button
            @click="router.push('/usuarios')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a usuarios
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos del usuario' : 'Creá un nuevo usuario para el gimnasio' }}
                </p>
            </div>

            <div v-if="loading" class="p-6 space-y-4">
                <div v-for="i in 4" :key="i" class="animate-pulse">
                    <div class="h-3.5 bg-gray-200 rounded w-1/4 mb-2" />
                    <div class="h-10 bg-gray-100 rounded-lg" />
                </div>
            </div>

            <form v-else @submit.prevent="submit" class="p-6 space-y-5">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Juan Pérez"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('name') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('name')" class="mt-1 text-xs text-red-600">{{ fieldError('name') }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="usuario@gimnasio.com"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('email') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Contraseña <span v-if="!isEdit" class="text-red-500">*</span>
                        <span v-else class="text-gray-400 font-normal text-xs ml-1">(dejá en blanco para no cambiar)</span>
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        :required="!isEdit"
                        placeholder="Mínimo 8 caracteres"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('password') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('password')" class="mt-1 text-xs text-red-600">{{ fieldError('password') }}</p>
                </div>

                <!-- Rol -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                    <div class="flex gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" v-model="form.rol" value="entrenador" class="peer sr-only" />
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">
                                Entrenador
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" v-model="form.rol" value="admin" class="peer sr-only" />
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:text-violet-700">
                                Admin
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Estado (solo en edición) -->
                <div v-if="isEdit">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <div class="flex gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" v-model="form.activo" :value="true" class="peer sr-only" />
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                Activo
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" v-model="form.activo" :value="false" class="peer sr-only" />
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-red-400 peer-checked:bg-red-50 peer-checked:text-red-700">
                                Inactivo
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="router.push('/usuarios')"
                        class="px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                    >
                        <svg v-if="saving" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear usuario') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const ui     = useUiStore();

const isEdit  = computed(() => !!route.params.id);
const title   = computed(() => isEdit.value ? 'Editar gimnasio' : 'Nuevo gimnasio');
const loading = ref(false);
const saving  = ref(false);
const errors  = ref({});

const form = ref({
    nombre:                      '',
    email:                       '',
    telefono:                    '',
    direccion:                   '',
    activo:                      true,
    admin_nombre:                '',
    admin_password:              '',
    admin_password_confirmation: '',
});

onMounted(async () => {
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/super-admin/gimnasios/${route.params.id}`);
        const g = data.data ?? data;
        form.value = {
            nombre:                      g.nombre    || '',
            email:                       g.email     || '',
            telefono:                    g.telefono  || '',
            direccion:                   g.direccion || '',
            activo:                      g.activo    ?? true,
            admin_nombre:                '',
            admin_password:              '',
            admin_password_confirmation: '',
        };
    } catch {
        ui.toast('Gimnasio no encontrado.', 'error');
        router.push('/super-admin/gimnasias');
    } finally {
        loading.value = false;
    }
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        if (isEdit.value) {
            const payload = {
                nombre:    form.value.nombre,
                email:     form.value.email,
                telefono:  form.value.telefono,
                direccion: form.value.direccion,
                activo:    form.value.activo,
            };

            if (form.value.admin_password) {
                if (form.value.admin_password !== form.value.admin_password_confirmation) {
                    ui.toast('Las contraseñas no coinciden.', 'error');
                    saving.value = false;
                    return;
                }
                payload.admin_password              = form.value.admin_password;
                payload.admin_password_confirmation = form.value.admin_password_confirmation;
            }

            await axios.patch(`/super-admin/gimnasios/${route.params.id}`, payload);
            ui.toast('Cambios guardados correctamente.', 'success');
        } else {
            if (form.value.admin_password !== form.value.admin_password_confirmation) {
                ui.toast('Las contraseñas no coinciden.', 'error');
                saving.value = false;
                return;
            }
            await axios.post('/super-admin/gimnasios', form.value);
            ui.toast('Gimnasio creado correctamente.', 'success');
        }
        router.push('/super-admin/gimnasias');
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            errors.value = errs;
            ui.toast('No se pudo guardar. Revisá los campos marcados.', 'error');
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
            @click="router.push('/super-admin/gimnasias')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a gimnasios
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos del gimnasio' : 'Registrá un nuevo gimnasio en la plataforma' }}
                </p>
            </div>

            <div v-if="loading" class="p-6 space-y-4">
                <div v-for="i in 5" :key="i" class="animate-pulse">
                    <div class="h-3.5 bg-gray-200 rounded w-1/4 mb-2" />
                    <div class="h-10 bg-gray-100 rounded-lg" />
                </div>
            </div>

            <form v-else @submit.prevent="submit" class="p-6 space-y-5">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Nombre del gimnasio <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="PowerGym"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('nombre') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('nombre')" class="mt-1 text-xs text-red-600">{{ fieldError('nombre') }}</p>
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
                        placeholder="info@powergym.com"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('email') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
                    <p v-if="!isEdit" class="mt-1.5 text-xs text-gray-400">
                        Este email se usará también como credencial de acceso del administrador del gimnasio.
                    </p>
                </div>

                <!-- Teléfono / Dirección -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
                        <input
                            v-model="form.telefono"
                            type="tel"
                            placeholder="+54 9 261 000-0000"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('telefono') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Dirección</label>
                        <input
                            v-model="form.direccion"
                            type="text"
                            placeholder="Av. Principal 1234"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('direccion') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                    </div>
                </div>

                <!-- Estado -->
                <div>
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

                <!-- Cuenta admin (solo creación) -->
                <div v-if="!isEdit" class="pt-2 border-t border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900 mb-1">Cuenta administradora</h2>
                    <p class="text-xs text-gray-400 mb-4">El admin iniciará sesión con el email del gimnasio ingresado arriba.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Nombre del administrador <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.admin_nombre"
                                type="text"
                                required
                                placeholder="Juan Pérez"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('admin_nombre') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="fieldError('admin_nombre')" class="mt-1 text-xs text-red-600">{{ fieldError('admin_nombre') }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Contraseña <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.admin_password"
                                    type="password"
                                    required
                                    placeholder="Mínimo 8 caracteres"
                                    :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('admin_password') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                />
                                <p v-if="fieldError('admin_password')" class="mt-1 text-xs text-red-600">{{ fieldError('admin_password') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Confirmar contraseña <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.admin_password_confirmation"
                                    type="password"
                                    required
                                    placeholder="Repetí la contraseña"
                                    :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('admin_password_confirmation') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                />
                                <p v-if="fieldError('admin_password_confirmation')" class="mt-1 text-xs text-red-600">{{ fieldError('admin_password_confirmation') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cambiar contraseña admin (solo edición) -->
                <div v-if="isEdit" class="pt-2 border-t border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900 mb-1">Modificar contraseña del admin</h2>
                    <p class="text-xs text-gray-400 mb-4">Dejá estos campos vacíos si no querés cambiar la contraseña actual.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nueva contraseña</label>
                            <input
                                v-model="form.admin_password"
                                type="password"
                                placeholder="Mínimo 8 caracteres"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('admin_password') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="fieldError('admin_password')" class="mt-1 text-xs text-red-600">{{ fieldError('admin_password') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar nueva contraseña</label>
                            <input
                                v-model="form.admin_password_confirmation"
                                type="password"
                                placeholder="Repetí la contraseña"
                                :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('admin_password_confirmation') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="fieldError('admin_password_confirmation')" class="mt-1 text-xs text-red-600">{{ fieldError('admin_password_confirmation') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="router.push('/super-admin/gimnasias')"
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
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear gimnasio') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const ui     = useUiStore();

const isEdit = computed(() => !!route.params.id);
const title  = computed(() => isEdit.value ? 'Editar plan' : 'Nuevo plan');

const loading = ref(false);
const saving  = ref(false);
const errors  = ref({});

const form = ref({
    nombre:          '',
    descripcion:     '',
    precio_efectivo: '',
    precio_digital:  '',
    activo:          true,
});

onMounted(async () => {
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/planes/${route.params.id}`);
        const p = data.data ?? data;
        form.value = {
            nombre:          p.nombre || '',
            descripcion:     p.descripcion || '',
            precio_efectivo: p.precio_efectivo !== null && p.precio_efectivo !== undefined ? p.precio_efectivo : '',
            precio_digital:  p.precio_digital  !== null && p.precio_digital  !== undefined ? p.precio_digital  : '',
            activo:          p.activo ?? true,
        };
    } catch {
        ui.toast('Plan no encontrado.', 'error');
        router.push('/planes');
    } finally {
        loading.value = false;
    }
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            nombre:          form.value.nombre,
            descripcion:     form.value.descripcion || null,
            precio_efectivo: form.value.precio_efectivo !== '' ? form.value.precio_efectivo : null,
            precio_digital:  form.value.precio_digital  !== '' ? form.value.precio_digital  : null,
            activo:          form.value.activo,
        };

        if (isEdit.value) {
            await axios.put(`/planes/${route.params.id}`, payload);
            ui.toast('Plan actualizado correctamente.', 'success');
        } else {
            await axios.post('/planes', payload);
            ui.toast('Plan creado correctamente.', 'success');
        }
        router.push('/planes');
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
        <!-- Back -->
        <button
            @click="router.push('/planes')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a planes
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos del plan' : 'Completá los datos para registrar un nuevo plan' }}
                </p>
            </div>

            <!-- Loading skeleton -->
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
                        Nombre del plan <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Ej: Musculación full, Cardio 3 veces/sem..."
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('nombre') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('nombre')" class="mt-1 text-xs text-red-600">{{ fieldError('nombre') }}</p>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Descripción</label>
                    <textarea
                        v-model="form.descripcion"
                        rows="3"
                        placeholder="Descripción del plan, incluye acceso a sala, horarios, actividades..."
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition resize-none', fieldError('descripcion') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('descripcion')" class="mt-1 text-xs text-red-600">{{ fieldError('descripcion') }}</p>
                </div>

                <!-- Precios por método de pago -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Precios de referencia
                        <span class="text-gray-400 font-normal">(opcionales)</span>
                    </label>
                    <p class="text-xs text-gray-400 mb-3">El precio es referencial. El monto del pago siempre puede ajustarse manualmente.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Efectivo -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-3.5 text-emerald-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                Efectivo
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm pointer-events-none">$</span>
                                <input
                                    v-model="form.precio_efectivo"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="['w-full pl-7 pr-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('precio_efectivo') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                />
                            </div>
                            <p v-if="fieldError('precio_efectivo')" class="mt-1 text-xs text-red-600">{{ fieldError('precio_efectivo') }}</p>
                        </div>
                        <!-- Digital (transferencia / tarjeta / otro) -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-3.5 text-blue-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                </svg>
                                Digital (transferencia / tarjeta)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm pointer-events-none">$</span>
                                <input
                                    v-model="form.precio_digital"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="['w-full pl-7 pr-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('precio_digital') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                />
                            </div>
                            <p v-if="fieldError('precio_digital')" class="mt-1 text-xs text-red-600">{{ fieldError('precio_digital') }}</p>
                        </div>
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
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-gray-400 peer-checked:bg-gray-100 peer-checked:text-gray-700">
                                Inactivo
                            </div>
                        </label>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Los planes inactivos no aparecen al asignar un plan a un socio.</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="router.push('/planes')"
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
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear plan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

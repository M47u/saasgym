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
    nombre:      '',
    descripcion: '',
    precio:      '',
    activo:      true,
});

onMounted(async () => {
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/planes/${route.params.id}`);
        const p = data.data ?? data;
        form.value = {
            nombre:      p.nombre || '',
            descripcion: p.descripcion || '',
            precio:      p.precio !== null && p.precio !== undefined ? p.precio : '',
            activo:      p.activo ?? true,
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
            nombre:      form.value.nombre,
            descripcion: form.value.descripcion || null,
            precio:      form.value.precio !== '' ? form.value.precio : null,
            activo:      form.value.activo,
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

                <!-- Precio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Precio de referencia
                        <span class="text-gray-400 font-normal">(opcional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm pointer-events-none">$</span>
                        <input
                            v-model="form.precio"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :class="['w-full pl-7 pr-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('precio') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                    </div>
                    <p class="mt-1 text-xs text-gray-400">El precio es referencial. El monto del pago siempre puede ajustarse manualmente.</p>
                    <p v-if="fieldError('precio')" class="mt-1 text-xs text-red-600">{{ fieldError('precio') }}</p>
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

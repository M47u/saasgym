<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const ui     = useUiStore();

const isEdit  = computed(() => !!route.params.id);
const title   = computed(() => isEdit.value ? 'Editar pago' : 'Registrar pago');
const loading = ref(false);
const saving  = ref(false);
const errors  = ref({});
const socios  = ref([]);

const metodos = ['efectivo', 'transferencia', 'tarjeta', 'otro'];

const form = ref({
    socio_id:      '',
    monto:         '',
    fecha_pago:    new Date().toISOString().slice(0, 7),
    metodo:        'efectivo',
    concepto:      '',
    observaciones: '',
});

async function loadSocios() {
    const { data } = await axios.get('/socios', { params: { estado: 'activo', per_page: 200 } });
    socios.value = data.data || [];
}

onMounted(async () => {
    await loadSocios();
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/pagos/${route.params.id}`);
        const p = data.data ?? data;
        form.value = {
            socio_id:      p.socio_id || '',
            monto:         p.monto || '',
            fecha_pago:    p.periodo_pago || (p.fecha_pago ? String(p.fecha_pago).slice(0, 7) : ''),
            metodo:        p.metodo || 'efectivo',
            concepto:      p.concepto || '',
            observaciones: p.observaciones || '',
        };
    } catch {
        ui.toast('Pago no encontrado.', 'error');
        router.push('/pagos');
    } finally {
        loading.value = false;
    }
});

async function submit() {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            ...form.value,
            // Store period as first day of month in backend.
            fecha_pago: /^\d{4}-\d{2}$/.test(String(form.value.fecha_pago))
                ? `${form.value.fecha_pago}-01`
                : form.value.fecha_pago,
        };

        if (isEdit.value) {
            await axios.put(`/pagos/${route.params.id}`, payload);
            ui.toast('Pago actualizado.', 'success');
        } else {
            await axios.post('/pagos', payload);
            ui.toast('Pago registrado.', 'success');
        }
        router.push('/pagos');
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
            @click="router.push('/pagos')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a pagos
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos del pago' : 'Registrá un nuevo pago en el sistema' }}
                </p>
            </div>

            <div v-if="loading" class="p-6 space-y-4">
                <div v-for="i in 4" :key="i" class="animate-pulse">
                    <div class="h-3.5 bg-gray-200 rounded w-1/4 mb-2" />
                    <div class="h-10 bg-gray-100 rounded-lg" />
                </div>
            </div>

            <form v-else @submit.prevent="submit" class="p-6 space-y-5">
                <!-- Socio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Socio <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.socio_id"
                        required
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white', fieldError('socio_id') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    >
                        <option value="" disabled>Seleccioná un socio</option>
                        <option v-for="s in socios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                    </select>
                    <p v-if="fieldError('socio_id')" class="mt-1 text-xs text-red-600">{{ fieldError('socio_id') }}</p>
                </div>

                <!-- Monto / Fecha -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Monto <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.monto"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="5000.00"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('monto') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('monto')" class="mt-1 text-xs text-red-600">{{ fieldError('monto') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Período a pagar <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.fecha_pago"
                            type="month"
                            required
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('fecha_pago') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('fecha_pago')" class="mt-1 text-xs text-red-600">{{ fieldError('fecha_pago') }}</p>
                    </div>
                </div>

                <!-- Método -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Método de pago <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="m in metodos" :key="m" class="relative cursor-pointer">
                            <input type="radio" v-model="form.metodo" :value="m" class="peer sr-only" />
                            <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium capitalize border-gray-200 text-gray-500 transition-all peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:text-violet-700">
                                {{ m }}
                            </div>
                        </label>
                    </div>
                    <p v-if="fieldError('metodo')" class="mt-1 text-xs text-red-600">{{ fieldError('metodo') }}</p>
                </div>

                <!-- Concepto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Concepto</label>
                    <input
                        v-model="form.concepto"
                        type="text"
                        placeholder="Ej: Cuota mensual enero"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('concepto') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('concepto')" class="mt-1 text-xs text-red-600">{{ fieldError('concepto') }}</p>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Observaciones</label>
                    <textarea
                        v-model="form.observaciones"
                        rows="3"
                        placeholder="Notas adicionales..."
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition resize-none', fieldError('observaciones') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="router.push('/pagos')"
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
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Registrar pago') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

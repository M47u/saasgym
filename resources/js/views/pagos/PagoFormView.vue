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

// Plan del socio seleccionado (para mostrar como referencia)
const planSocio = computed(() => {
    if (!form.value.socio_id) return null;
    const socio = socios.value.find(s => s.id == form.value.socio_id);
    return socio?.plan ?? null;
});

// Precio del plan según el método de pago elegido
const precioPlanSegunMetodo = computed(() => {
    const plan = planSocio.value;
    if (!plan) return null;
    const precio = form.value.metodo === 'efectivo'
        ? plan.precio_efectivo
        : plan.precio_digital;
    if (precio === null || precio === undefined) return null;
    return { valor: precio, label: `$${Number(precio).toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}` };
});

const metodos = ['efectivo', 'transferencia', 'tarjeta', 'otro'];

function getConceptoDefault() {
    const mesActual = new Intl.DateTimeFormat('es-AR', { month: 'long' }).format(new Date()).toLowerCase();
    return `Cuota mensula de ${mesActual}`;
}

const form = ref({
    socio_id:      '',
    monto:         '',
    fecha_pago:    new Date().toISOString().split('T')[0],
    metodo:        'efectivo',
    concepto:      getConceptoDefault(),
    observaciones: '',
});

// Calcula el próximo vencimiento igual que el backend (Carbon::addMonthNoOverflow + skip weekends)
function calcularProximoPago(fecha) {
    if (!fecha) return null;
    const d = new Date(fecha + 'T00:00:00');
    const day = d.getDate();
    d.setDate(1);
    d.setMonth(d.getMonth() + 1);
    const lastDayOfMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    d.setDate(Math.min(day, lastDayOfMonth));
    // 0 = domingo, 6 = sábado
    while (d.getDay() === 0 || d.getDay() === 6) {
        d.setDate(d.getDate() + 1);
    }
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

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
            fecha_pago:    p.fecha_pago || '',
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
        const payload = { ...form.value };

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

                <!-- Plan hint del socio seleccionado (reactivo al método de pago) -->
                <div
                    v-if="planSocio"
                    class="flex items-start gap-3 px-4 py-3 rounded-xl bg-violet-50 border border-violet-100 text-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 text-violet-500 shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-violet-800">{{ planSocio.nombre }}</p>
                        <p v-if="precioPlanSegunMetodo" class="text-violet-600 mt-0.5">
                            Precio {{ form.metodo === 'efectivo' ? 'efectivo' : 'digital' }}:
                            <strong>{{ precioPlanSegunMetodo.label }}</strong>
                        </p>
                        <p v-else class="text-violet-400 mt-0.5 text-xs">Sin precio definido para este método de pago.</p>
                    </div>
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
                            Fecha de pago <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.fecha_pago"
                            type="date"
                            required
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('fecha_pago') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('fecha_pago')" class="mt-1 text-xs text-red-600">{{ fieldError('fecha_pago') }}</p>
                        <p v-if="form.fecha_pago" class="mt-1.5 text-xs text-gray-400">
                            Próximo vencimiento: <span class="font-medium text-gray-600">{{ calcularProximoPago(form.fecha_pago) }}</span>
                        </p>
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

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const ui = useUiStore();

const isEdit = computed(() => !!route.params.id);
const title = computed(() => isEdit.value ? 'Editar socio' : 'Nuevo socio');

const loading = ref(false);
const saving = ref(false);
const errors = ref({});
const planes = ref([]);

const form = ref({
    nombre: '',
    email: '',
    telefono: '',
    fecha_nacimiento: '',
    estado: 'activo',
    plan_id: '',
});

async function loadPlanes() {
    try {
        const { data } = await axios.get('/planes', { params: { solo_activos: true } });
        planes.value = data.data ?? data;
    } catch {
        // silencioso: no bloquear el formulario si fallan los planes
    }
}

onMounted(async () => {
    await loadPlanes();
    if (!isEdit.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/socios/${route.params.id}`);
        const s = data.data ?? data;
        form.value = {
            nombre: s.nombre || '',
            email: s.email || '',
            telefono: s.telefono || '',
            fecha_nacimiento: s.fecha_nacimiento || '',
            estado: s.estado || 'activo',
            plan_id: s.plan_id || '',
        };
    } catch {
        ui.toast('Error al cargar el socio.', 'error');
        router.push('/socios');
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
            plan_id: form.value.plan_id || null,
        };
        if (isEdit.value) {
            await axios.put(`/socios/${route.params.id}`, payload);
            ui.toast('Socio actualizado correctamente.', 'success');
        } else {
            const { data } = await axios.post('/socios', payload);
            ui.toast('Socio creado correctamente.', 'success');
            router.push(`/socios/${(data.data ?? data).id}`);
            return;
        }
        router.push(`/socios/${route.params.id}`);
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

function formatPrecio(precio) {
    const n = Number(precio);
    return Number.isFinite(n) ? `$${n.toLocaleString('es-AR')}` : null;
}

function planOptionLabel(plan) {
    const efectivo = formatPrecio(plan?.precio_efectivo);
    const digital = formatPrecio(plan?.precio_digital);

    if (efectivo && digital) {
        return efectivo === digital
            ? `${plan.nombre} — ${efectivo}`
            : `${plan.nombre} — Efectivo: ${efectivo} | Digital: ${digital}`;
    }

    if (efectivo || digital) {
        return `${plan.nombre} — ${efectivo || digital}`;
    }

    return plan.nombre;
}
</script>

<template>
    <div class="max-w-2xl mx-auto">
        <!-- Back -->
        <button
            @click="router.push('/socios')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a socios
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos del socio' : 'Completá los datos para registrar un nuevo socio' }}
                </p>
            </div>

            <!-- Loading -->
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
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        placeholder="Juan García"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('nombre') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('nombre')" class="mt-1 text-xs text-red-600">{{ fieldError('nombre') }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="juan@email.com"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('email') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    />
                    <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
                </div>

                <!-- Teléfono / Fecha nacimiento -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
                        <input
                            v-model="form.telefono"
                            type="tel"
                            placeholder="+54 9 11 0000-0000"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('telefono') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('telefono')" class="mt-1 text-xs text-red-600">{{ fieldError('telefono') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Fecha de nacimiento</label>
                        <input
                            v-model="form.fecha_nacimiento"
                            type="date"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('fecha_nacimiento') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('fecha_nacimiento')" class="mt-1 text-xs text-red-600">{{ fieldError('fecha_nacimiento') }}</p>
                    </div>
                </div>

                <!-- Plan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan</label>
                    <select
                        v-model="form.plan_id"
                        :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white', fieldError('plan_id') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    >
                        <option value="">Sin plan asignado</option>
                        <option v-for="p in planes" :key="p.id" :value="p.id">
                            {{ planOptionLabel(p) }}
                        </option>
                    </select>
                    <p v-if="fieldError('plan_id')" class="mt-1 text-xs text-red-600">{{ fieldError('plan_id') }}</p>
                    <p v-if="planes.length === 0" class="mt-1 text-xs text-gray-400">
                        No hay planes activos.
                        <router-link to="/planes/nuevo" class="text-violet-600 hover:underline">Crear uno</router-link>
                    </p>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <div class="flex gap-3">
                        <label
                            v-for="opt in [{ value: 'activo', label: 'Activo', color: 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700' }, { value: 'inactivo', label: 'Inactivo', color: 'peer-checked:border-gray-400 peer-checked:bg-gray-100 peer-checked:text-gray-700' }, { value: 'suspendido', label: 'Suspendido', color: 'peer-checked:border-red-400 peer-checked:bg-red-50 peer-checked:text-red-700' }]"
                            :key="opt.value"
                            class="relative cursor-pointer"
                        >
                            <input type="radio" v-model="form.estado" :value="opt.value" class="peer sr-only" />
                            <div :class="['px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all', opt.color]">
                                {{ opt.label }}
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="router.push('/socios')"
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
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear socio') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

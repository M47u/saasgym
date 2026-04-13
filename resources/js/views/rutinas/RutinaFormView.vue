<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const ui     = useUiStore();

const isEdit  = computed(() => !!route.params.id);
const title   = computed(() => isEdit.value ? 'Editar rutina' : 'Nueva rutina');
const loading = ref(false);
const saving  = ref(false);
const errors  = ref({});
const socios  = ref([]);

const gruposMusculares = [
    'Pecho', 'Espalda', 'Hombros', 'Bíceps', 'Tríceps',
    'Piernas', 'Glúteos', 'Abdomen', 'Cardio', 'Otro'
];

const form = ref({
    socio_id:    '',
    nombre:      '',
    descripcion: '',
    fecha_inicio:'',
    fecha_fin:   '',
    activa:      true,
    ejercicios:  [],
});

function newEjercicio() {
    return { nombre: '', grupo_muscular: '', series: '', repeticiones: '', descanso_segundos: '', notas: '', orden: form.value.ejercicios.length };
}

function addEjercicio() {
    form.value.ejercicios.push(newEjercicio());
}

function removeEjercicio(index) {
    form.value.ejercicios.splice(index, 1);
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
        const { data } = await axios.get(`/rutinas/${route.params.id}`);
        const r = data.data ?? data;
        form.value = {
            socio_id:     r.socio_id || '',
            nombre:       r.nombre || '',
            descripcion:  r.descripcion || '',
            fecha_inicio: r.fecha_inicio || '',
            fecha_fin:    r.fecha_fin || '',
            activa:       r.activa ?? true,
            ejercicios:   (r.ejercicios || []).map(e => ({
                nombre:             e.nombre || '',
                grupo_muscular:     e.grupo_muscular || '',
                series:             e.series || '',
                repeticiones:       e.repeticiones || '',
                descanso_segundos:  e.descanso_segundos || '',
                notas:              e.notas || '',
                orden:              e.orden ?? 0,
            })),
        };
    } catch {
        ui.toast('Rutina no encontrada.', 'error');
        router.push('/rutinas');
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
            ejercicios: form.value.ejercicios.map((e, i) => ({ ...e, orden: i })),
        };
        if (isEdit.value) {
            await axios.put(`/rutinas/${route.params.id}`, payload);
            ui.toast('Rutina actualizada.', 'success');
        } else {
            await axios.post('/rutinas', payload);
            ui.toast('Rutina creada.', 'success');
        }
        router.push('/rutinas');
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
    <div class="max-w-3xl mx-auto">
        <button
            @click="router.push('/rutinas')"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a rutinas
        </button>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ isEdit ? 'Modificá los datos de la rutina' : 'Creá una nueva rutina con ejercicios' }}
                </p>
            </div>

            <div v-if="loading" class="p-6 space-y-4">
                <div v-for="i in 4" :key="i" class="animate-pulse">
                    <div class="h-3.5 bg-gray-200 rounded w-1/4 mb-2" />
                    <div class="h-10 bg-gray-100 rounded-lg" />
                </div>
            </div>

            <form v-else @submit.prevent="submit" class="p-6 space-y-6">
                <!-- Datos principales -->
                <div class="space-y-5">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Datos de la rutina</h2>

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

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            placeholder="Ej: Fuerza - Tren Superior"
                            :class="['w-full px-3.5 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition', fieldError('nombre') ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="fieldError('nombre')" class="mt-1 text-xs text-red-600">{{ fieldError('nombre') }}</p>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Descripción</label>
                        <textarea
                            v-model="form.descripcion"
                            rows="2"
                            placeholder="Objetivo, indicaciones generales..."
                            class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition resize-none"
                        />
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fecha inicio</label>
                            <input
                                v-model="form.fecha_inicio"
                                type="date"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fecha fin</label>
                            <input
                                v-model="form.fecha_fin"
                                type="date"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                            />
                        </div>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <div class="flex gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" v-model="form.activa" :value="true" class="peer sr-only" />
                                <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                    Activa
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" v-model="form.activa" :value="false" class="peer sr-only" />
                                <div class="px-4 py-2 rounded-lg border-2 text-sm font-medium border-gray-200 text-gray-500 transition-all peer-checked:border-gray-400 peer-checked:bg-gray-100 peer-checked:text-gray-700">
                                    Inactiva
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Ejercicios -->
                <div class="space-y-4 pt-2 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                            Ejercicios ({{ form.ejercicios.length }})
                        </h2>
                        <button
                            type="button"
                            @click="addEjercicio"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-50 text-violet-700 text-sm font-medium hover:bg-violet-100 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Agregar
                        </button>
                    </div>

                    <div v-if="form.ejercicios.length === 0" class="py-8 text-center text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-xl">
                        No hay ejercicios. Hacé clic en "Agregar" para añadir.
                    </div>

                    <div
                        v-for="(ej, index) in form.ejercicios"
                        :key="index"
                        class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ejercicio {{ index + 1 }}</span>
                            <button
                                type="button"
                                @click="removeEjercicio(index)"
                                class="p-1 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                                <input
                                    v-model="ej.nombre"
                                    type="text"
                                    required
                                    placeholder="Press de banca"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Grupo muscular</label>
                                <select
                                    v-model="ej.grupo_muscular"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
                                >
                                    <option value="">— Seleccioná —</option>
                                    <option v-for="g in gruposMusculares" :key="g" :value="g">{{ g }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Series</label>
                                <input
                                    v-model="ej.series"
                                    type="number"
                                    min="1"
                                    placeholder="4"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Repeticiones</label>
                                <input
                                    v-model="ej.repeticiones"
                                    type="number"
                                    min="1"
                                    placeholder="12"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Descanso (seg)</label>
                                <input
                                    v-model="ej.descanso_segundos"
                                    type="number"
                                    min="0"
                                    placeholder="60"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notas</label>
                            <input
                                v-model="ej.notas"
                                type="text"
                                placeholder="Indicaciones adicionales..."
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button
                        type="button"
                        @click="router.push('/rutinas')"
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
                        {{ saving ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear rutina') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

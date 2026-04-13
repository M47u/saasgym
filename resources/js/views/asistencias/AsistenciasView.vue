<script setup>
import { ref, watch, onMounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const ui = useUiStore();

const asistencias = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const fecha = ref(new Date().toISOString().split('T')[0]);
const page = ref(1);

// Check-in modal
const checkinOpen = ref(false);
const checkingIn = ref(false);
const socioSearch = ref('');
const socioResults = ref([]);
const socioSearching = ref(false);
const selectedSocio = ref(null);
let searchTimer = null;

const colors = ['bg-violet-100 text-violet-700', 'bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-pink-100 text-pink-700'];
function avatarColor(id) { return colors[id % colors.length]; }
function initials(n) { return (n || '').split(' ').slice(0, 2).map(c => c[0]).join('').toUpperCase(); }

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/asistencias', {
            params: { fecha: fecha.value || undefined, page: page.value }
        });
        asistencias.value = data.data;
        meta.value = data.meta;
    } finally {
        loading.value = false;
    }
}

watch(fecha, () => { page.value = 1; load(); });
onMounted(load);

function formatTime(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleDateString('es-AR');
}

function openCheckin() {
    socioSearch.value = '';
    socioResults.value = [];
    selectedSocio.value = null;
    checkinOpen.value = true;
}

watch(socioSearch, (val) => {
    clearTimeout(searchTimer);
    if (!val || val.length < 2) { socioResults.value = []; return; }
    socioSearching.value = true;
    searchTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/socios', { params: { search: val, estado: 'activo', per_page: 10 } });
            socioResults.value = data.data || [];
        } finally {
            socioSearching.value = false;
        }
    }, 300);
});

function selectSocio(s) {
    selectedSocio.value = s;
    socioSearch.value = s.nombre;
    socioResults.value = [];
}

async function doCheckin() {
    if (!selectedSocio.value) return;
    checkingIn.value = true;
    try {
        await axios.post('/asistencias', { socio_id: selectedSocio.value.id });
        ui.toast(`Check-in registrado para ${selectedSocio.value.nombre}.`, 'success');
        checkinOpen.value = false;
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Error al registrar check-in.', 'error');
    } finally {
        checkingIn.value = false;
    }
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Asistencias</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ meta.total }} registros{{ fecha ? ` para ${new Date(fecha + 'T00:00:00').toLocaleDateString('es-AR')}` : '' }}
                </p>
            </div>
            <button
                @click="openCheckin"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Registrar check-in
            </button>
        </div>

        <!-- Date filter -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Fecha:</label>
            <input
                v-model="fecha"
                type="date"
                class="px-3.5 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
            />
            <button
                v-if="fecha"
                @click="fecha = ''"
                class="text-xs text-gray-400 hover:text-gray-600 transition-colors"
            >
                Mostrar todo
            </button>
        </div>

        <!-- List -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="divide-y divide-gray-50">
                <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-6 py-4 animate-pulse">
                    <div class="w-9 h-9 rounded-full bg-gray-200 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/3" />
                        <div class="h-3 bg-gray-100 rounded w-1/4" />
                    </div>
                    <div class="h-4 bg-gray-100 rounded w-12" />
                </div>
            </div>

            <div v-else-if="asistencias.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <p class="text-sm font-medium">Sin asistencias registradas</p>
                <p class="text-xs mt-1">
                    <template v-if="fecha">No hay check-ins para esta fecha</template>
                    <template v-else>Registrá el primer check-in</template>
                </p>
            </div>

            <ul v-else class="divide-y divide-gray-50">
                <li
                    v-for="a in asistencias"
                    :key="a.id"
                    class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/60 transition-colors"
                >
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0', avatarColor(a.socio_id)]">
                        {{ initials(a.socio?.nombre) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ a.socio?.nombre || '—' }}</p>
                        <p class="text-xs text-gray-400">{{ formatDate(a.fecha_hora_entrada) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 tabular-nums">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-3.5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ formatTime(a.fecha_hora_entrada) }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                            </svg>
                            Presente
                        </span>
                    </div>
                </li>
            </ul>

            <!-- Pagination -->
            <div v-if="!loading && meta.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-50">
                <p class="text-sm text-gray-500">Página {{ meta.current_page }} de {{ meta.last_page }}</p>
                <div class="flex gap-2">
                    <button
                        @click="page--; load()"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >← Anterior</button>
                    <button
                        @click="page++; load()"
                        :disabled="page >= meta.last_page"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >Siguiente →</button>
                </div>
            </div>
        </div>

        <!-- Check-in modal -->
        <BaseModal :open="checkinOpen" title="Registrar check-in" @close="checkinOpen = false">
            <div class="space-y-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Buscar socio</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            v-model="socioSearch"
                            type="text"
                            placeholder="Nombre del socio..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        />
                    </div>
                    <!-- Results dropdown -->
                    <div v-if="socioResults.length > 0" class="absolute z-10 w-full mt-1 bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden">
                        <button
                            v-for="s in socioResults"
                            :key="s.id"
                            @click="selectSocio(s)"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-violet-50 transition-colors"
                        >
                            <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0', avatarColor(s.id)]">
                                {{ initials(s.nombre) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ s.nombre }}</p>
                                <p class="text-xs text-gray-400">{{ s.email || s.telefono || '' }}</p>
                            </div>
                        </button>
                    </div>
                    <p v-if="socioSearching" class="mt-1.5 text-xs text-gray-400">Buscando...</p>
                </div>

                <div v-if="selectedSocio" class="flex items-center gap-3 p-3 bg-violet-50 rounded-xl border border-violet-100">
                    <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0', avatarColor(selectedSocio.id)]">
                        {{ initials(selectedSocio.nombre) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ selectedSocio.nombre }}</p>
                        <p class="text-xs text-violet-600">Seleccionado</p>
                    </div>
                    <button @click="selectedSocio = null; socioSearch = ''" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button @click="checkinOpen = false" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="doCheckin"
                        :disabled="!selectedSocio || checkingIn"
                        class="px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                    >
                        <svg v-if="checkingIn" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ checkingIn ? 'Registrando...' : 'Registrar check-in' }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

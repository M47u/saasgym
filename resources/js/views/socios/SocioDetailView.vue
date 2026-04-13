<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const ui = useUiStore();
const auth = useAuthStore();

const socio = ref(null);
const loading = ref(true);
const tab = ref('info');
const pagos = ref([]);
const asistencias = ref([]);
const rutinas = ref([]);
const tabLoading = ref(false);

const tabs = [
    { key: 'info', label: 'Información' },
    { key: 'pagos', label: 'Pagos', adminOnly: true },
    { key: 'asistencias', label: 'Asistencias' },
    { key: 'rutinas', label: 'Rutinas' },
];

const visibleTabs = computed(() =>
    tabs.filter(t => !t.adminOnly || auth.isAdmin)
);

onMounted(async () => {
    await loadSocio();
});

async function loadSocio() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/socios/${route.params.id}`);
        socio.value = data.data ?? data;
    } catch {
        ui.toast('Socio no encontrado.', 'error');
        router.push('/socios');
    } finally {
        loading.value = false;
    }
}

watch(tab, async (newTab) => {
    if (newTab === 'pagos' && pagos.value.length === 0) {
        tabLoading.value = true;
        try {
            const { data } = await axios.get('/pagos', { params: { socio_id: route.params.id } });
            pagos.value = data.data || [];
        } finally { tabLoading.value = false; }
    }
    if (newTab === 'asistencias' && asistencias.value.length === 0) {
        tabLoading.value = true;
        try {
            const { data } = await axios.get('/asistencias', { params: { socio_id: route.params.id } });
            asistencias.value = data.data || [];
        } finally { tabLoading.value = false; }
    }
    if (newTab === 'rutinas' && rutinas.value.length === 0) {
        tabLoading.value = true;
        try {
            const { data } = await axios.get('/rutinas', { params: { socio_id: route.params.id } });
            rutinas.value = data.data || [];
        } finally { tabLoading.value = false; }
    }
});

function estadoVariant(e) {
    return { activo: 'green', inactivo: 'gray', suspendido: 'red' }[e] || 'gray';
}

function metodoBadge(m) {
    return { efectivo: 'green', transferencia: 'blue', tarjeta: 'purple', otro: 'gray' }[m] || 'gray';
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d + 'T00:00:00').toLocaleDateString('es-AR');
}

function formatDateTime(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-AR', { dateStyle: 'medium', timeStyle: 'short' });
}

function initials(nombre) {
    return (nombre || '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

const totalPagado = computed(() => pagos.value.reduce((s, p) => s + p.monto, 0));
</script>

<template>
    <div>
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

        <!-- Skeleton -->
        <div v-if="loading" class="animate-pulse">
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm mb-4">
                <div class="flex gap-5 items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-200 shrink-0" />
                    <div class="space-y-2 flex-1">
                        <div class="h-5 bg-gray-200 rounded w-1/3" />
                        <div class="h-3.5 bg-gray-100 rounded w-1/2" />
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="socio">
            <!-- Profile header -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-4 overflow-hidden">
                <div class="bg-gradient-to-r from-violet-600 to-violet-800 h-20" />
                <div class="px-6 pb-5">
                    <div class="flex items-end justify-between -mt-8 mb-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border-4 border-white shadow-md flex items-center justify-center text-violet-700 font-bold text-xl">
                            {{ initials(socio.nombre) }}
                        </div>
                        <div class="flex gap-2 pb-1">
                            <router-link
                                :to="`/socios/${socio.id}/editar`"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                                Editar
                            </router-link>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-xl font-bold text-gray-900">{{ socio.nombre }}</h1>
                        <BaseBadge :variant="estadoVariant(socio.estado)">{{ socio.estado }}</BaseBadge>
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1.5 mt-2">
                        <span v-if="socio.email" class="flex items-center gap-1.5 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ socio.email }}
                        </span>
                        <span v-if="socio.telefono" class="flex items-center gap-1.5 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            {{ socio.telefono }}
                        </span>
                        <span v-if="socio.fecha_nacimiento" class="flex items-center gap-1.5 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5.75a3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-1.5-.75m16.5 0 1.5-.75m0 0 1.5.75" />
                            </svg>
                            {{ formatDate(socio.fecha_nacimiento) }}
                        </span>
                        <span class="flex items-center gap-1.5 text-sm text-gray-400">
                            Alta: {{ formatDate(socio.created_at?.split('T')[0]) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex border-b border-gray-100 overflow-x-auto">
                    <button
                        v-for="t in visibleTabs"
                        :key="t.key"
                        @click="tab = t.key"
                        :class="[
                            'px-5 py-3.5 text-sm font-medium whitespace-nowrap transition-colors border-b-2 -mb-px',
                            tab === t.key
                                ? 'border-violet-600 text-violet-700'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-200'
                        ]"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <div class="p-6">
                    <!-- Loading tab -->
                    <div v-if="tabLoading" class="space-y-3 animate-pulse">
                        <div v-for="i in 4" :key="i" class="h-10 bg-gray-100 rounded-lg" />
                    </div>

                    <!-- Info tab -->
                    <div v-else-if="tab === 'info'">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Nombre completo</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ socio.nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Estado</dt>
                                <dd><BaseBadge :variant="estadoVariant(socio.estado)">{{ socio.estado }}</BaseBadge></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Email</dt>
                                <dd class="text-sm text-gray-700">{{ socio.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Teléfono</dt>
                                <dd class="text-sm text-gray-700">{{ socio.telefono || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Fecha de nacimiento</dt>
                                <dd class="text-sm text-gray-700">{{ formatDate(socio.fecha_nacimiento) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Fecha de alta</dt>
                                <dd class="text-sm text-gray-700">{{ formatDate(socio.created_at?.split('T')[0]) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Pagos tab -->
                    <div v-else-if="tab === 'pagos'">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-500">
                                {{ pagos.length }} pagos · Total:
                                <span class="font-semibold text-gray-900">${{ totalPagado.toLocaleString('es-AR') }}</span>
                            </p>
                            <router-link
                                v-if="auth.isAdmin"
                                to="/pagos/nuevo"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-medium hover:bg-violet-700 transition-colors"
                            >
                                + Registrar pago
                            </router-link>
                        </div>
                        <div v-if="pagos.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-sm">Sin pagos registrados</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="p in pagos"
                                :key="p.id"
                                class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 text-emerald-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ p.concepto || 'Pago' }}</p>
                                        <p class="text-xs text-gray-400">{{ formatDate(p.fecha_pago) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <BaseBadge :variant="metodoBadge(p.metodo)">{{ p.metodo }}</BaseBadge>
                                    <span class="text-sm font-semibold text-gray-900">${{ p.monto.toLocaleString('es-AR') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Asistencias tab -->
                    <div v-else-if="tab === 'asistencias'">
                        <p class="text-sm text-gray-500 mb-4">{{ asistencias.length }} asistencias registradas</p>
                        <div v-if="asistencias.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-sm">Sin asistencias registradas</p>
                        </div>
                        <div v-else class="space-y-1.5">
                            <div
                                v-for="a in asistencias"
                                :key="a.id"
                                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0" />
                                <span class="text-sm text-gray-700">{{ formatDateTime(a.fecha_hora_entrada) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rutinas tab -->
                    <div v-else-if="tab === 'rutinas'">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-500">{{ rutinas.length }} rutinas asignadas</p>
                            <router-link
                                to="/rutinas/nueva"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-medium hover:bg-violet-700 transition-colors"
                            >
                                + Nueva rutina
                            </router-link>
                        </div>
                        <div v-if="rutinas.length === 0" class="text-center py-12 text-gray-400">
                            <p class="text-sm">Sin rutinas asignadas</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="r in rutinas"
                                :key="r.id"
                                class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer"
                                @click="router.push(`/rutinas/${r.id}`)"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ r.nombre }}</p>
                                    <p class="text-xs text-gray-400">{{ r.descripcion || 'Sin descripción' }}</p>
                                </div>
                                <BaseBadge :variant="r.activa ? 'green' : 'gray'">
                                    {{ r.activa ? 'Activa' : 'Inactiva' }}
                                </BaseBadge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

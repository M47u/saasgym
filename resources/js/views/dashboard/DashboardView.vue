<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import axios from 'axios';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const socios = ref({ total: 0, activos: 0, inactivos: 0, suspendidos: 0 });
const pagosHoy = ref({ count: 0, total: 0 });
const asistenciasHoy = ref([]);
const recientes = ref([]);

const today = new Date().toISOString().split('T')[0];

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Buenos días';
    if (h < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

async function loadStats() {
    try {
        const [sociosRes, pagosRes, asistRes] = await Promise.all([
            axios.get('/socios', { params: { per_page: 1 } }),
            axios.get('/pagos', { params: { per_page: 100 } }),
            axios.get('/asistencias', { params: { fecha: today, per_page: 50 } }),
        ]);

        // socios por estado
        const [actRes, inactRes, suspRes] = await Promise.all([
            axios.get('/socios', { params: { estado: 'activo', per_page: 1 } }),
            axios.get('/socios', { params: { estado: 'inactivo', per_page: 1 } }),
            axios.get('/socios', { params: { estado: 'suspendido', per_page: 1 } }),
        ]);

        socios.value = {
            total: sociosRes.data.meta?.total || 0,
            activos: actRes.data.meta?.total || 0,
            inactivos: inactRes.data.meta?.total || 0,
            suspendidos: suspRes.data.meta?.total || 0,
        };

        // pagos de hoy
        const todayPagos = pagosRes.data.data?.filter(p => p.fecha_pago === today) || [];
        pagosHoy.value = {
            count: todayPagos.length,
            total: todayPagos.reduce((s, p) => s + p.monto, 0),
        };

        asistenciasHoy.value = asistRes.data.data || [];
        recientes.value = (sociosRes.data.data || []).slice(0, 5);

    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

onMounted(loadStats);

const stats = computed(() => [
    {
        label: 'Socios activos',
        value: socios.value.activos,
        sub: `${socios.value.total} en total`,
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>`,
        color: 'bg-violet-50 text-violet-600',
        trend: socios.value.activos > 0 ? 'up' : null,
    },
    {
        label: 'Check-ins hoy',
        value: asistenciasHoy.value.length,
        sub: 'asistencias registradas',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>`,
        color: 'bg-blue-50 text-blue-600',
    },
    {
        label: 'Cobros hoy',
        value: `$${pagosHoy.value.total.toLocaleString('es-AR')}`,
        sub: `${pagosHoy.value.count} pagos registrados`,
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>`,
        color: 'bg-emerald-50 text-emerald-600',
        adminOnly: true,
    },
    {
        label: 'Inactivos / Suspendidos',
        value: socios.value.inactivos + socios.value.suspendidos,
        sub: `${socios.value.suspendidos} suspendidos`,
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>`,
        color: 'bg-amber-50 text-amber-600',
    },
]);

const visibleStats = computed(() =>
    stats.value.filter(s => !s.adminOnly || auth.isAdmin)
);

function estadoBadgeVariant(estado) {
    return { activo: 'green', inactivo: 'gray', suspendido: 'red' }[estado] || 'gray';
}

function formatTime(datetime) {
    if (!datetime) return '';
    return new Date(datetime).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ greeting }}, {{ auth.user?.nombre?.split(' ')[0] }} 👋
            </h1>
            <p class="text-gray-500 mt-1 text-sm">
                {{ new Date().toLocaleDateString('es-AR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
            </p>
        </div>

        <!-- Skeleton -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-5 h-28 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-1/2 mb-3" />
                <div class="h-7 bg-gray-200 rounded w-1/3 mb-2" />
                <div class="h-3 bg-gray-100 rounded w-2/3" />
            </div>
        </div>

        <!-- Stats -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div
                v-for="stat in visibleStats"
                :key="stat.label"
                class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between mb-3">
                    <div :class="['p-2.5 rounded-xl', stat.color]" v-html="stat.icon" />
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                <p class="text-sm text-gray-500 mt-0.5">{{ stat.label }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ stat.sub }}</p>
            </div>
        </div>

        <!-- Bottom grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Check-ins de hoy -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                    <h2 class="font-semibold text-gray-900 text-sm">Check-ins de hoy</h2>
                    <router-link to="/asistencias" class="text-xs text-violet-600 hover:text-violet-700 font-medium transition-colors">
                        Ver todos →
                    </router-link>
                </div>
                <div v-if="loading" class="p-5 space-y-3">
                    <div v-for="i in 4" :key="i" class="flex gap-3 items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-200 animate-pulse shrink-0" />
                        <div class="flex-1 space-y-1.5">
                            <div class="h-3 bg-gray-200 rounded w-3/4 animate-pulse" />
                            <div class="h-2.5 bg-gray-100 rounded w-1/2 animate-pulse" />
                        </div>
                    </div>
                </div>
                <div v-else-if="asistenciasHoy.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10 mb-2 opacity-30">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <p class="text-sm">Sin check-ins hoy</p>
                </div>
                <ul v-else class="divide-y divide-gray-50">
                    <li
                        v-for="a in asistenciasHoy.slice(0, 6)"
                        :key="a.id"
                        class="flex items-center gap-3 px-5 py-3"
                    >
                        <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center shrink-0">
                            {{ a.socio?.nombre?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ a.socio?.nombre }}</p>
                        </div>
                        <span class="text-xs text-gray-400 tabular-nums">{{ formatTime(a.fecha_hora_entrada) }}</span>
                    </li>
                </ul>
            </div>

            <!-- Quick actions -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-semibold text-gray-900 text-sm">Acciones rápidas</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-3">
                    <button
                        @click="router.push('/socios/nuevo')"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-gray-200 hover:border-violet-300 hover:bg-violet-50 text-gray-500 hover:text-violet-700 transition-all group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6 group-hover:scale-110 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                        </svg>
                        <span class="text-xs font-medium">Nuevo socio</span>
                    </button>
                    <button
                        @click="router.push('/asistencias/checkin')"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-gray-200 hover:border-blue-300 hover:bg-blue-50 text-gray-500 hover:text-blue-700 transition-all group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6 group-hover:scale-110 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-xs font-medium">Check-in</span>
                    </button>
                    <button
                        v-if="auth.isAdmin"
                        @click="router.push('/pagos/nuevo')"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 text-gray-500 hover:text-emerald-700 transition-all group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6 group-hover:scale-110 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-xs font-medium">Registrar pago</span>
                    </button>
                    <button
                        @click="router.push('/rutinas/nueva')"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-gray-200 hover:border-amber-300 hover:bg-amber-50 text-gray-500 hover:text-amber-700 transition-all group"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6 group-hover:scale-110 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                        <span class="text-xs font-medium">Nueva rutina</span>
                    </button>
                </div>

                <!-- Estado socios -->
                <div class="px-5 pb-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Estado de socios</p>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full bg-emerald-500 rounded-full transition-all duration-700"
                                    :style="{ width: socios.total > 0 ? (socios.activos / socios.total * 100) + '%' : '0%' }"
                                />
                            </div>
                            <div class="flex items-center gap-1.5 w-24 justify-end">
                                <BaseBadge variant="green">{{ socios.activos }}</BaseBadge>
                                <span class="text-xs text-gray-400">activos</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full bg-gray-400 rounded-full transition-all duration-700"
                                    :style="{ width: socios.total > 0 ? (socios.inactivos / socios.total * 100) + '%' : '0%' }"
                                />
                            </div>
                            <div class="flex items-center gap-1.5 w-24 justify-end">
                                <BaseBadge variant="gray">{{ socios.inactivos }}</BaseBadge>
                                <span class="text-xs text-gray-400">inactivos</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full bg-red-400 rounded-full transition-all duration-700"
                                    :style="{ width: socios.total > 0 ? (socios.suspendidos / socios.total * 100) + '%' : '0%' }"
                                />
                            </div>
                            <div class="flex items-center gap-1.5 w-24 justify-end">
                                <BaseBadge variant="red">{{ socios.suspendidos }}</BaseBadge>
                                <span class="text-xs text-gray-400">suspendidos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

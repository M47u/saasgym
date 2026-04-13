<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const router = useRouter();
const ui = useUiStore();
const auth = useAuthStore();

const socios = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const search = ref('');
const estado = ref('');
const page = ref(1);
const deleteTarget = ref(null);
const deleting = ref(false);

let searchTimer = null;

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/socios', {
            params: { search: search.value || undefined, estado: estado.value || undefined, page: page.value }
        });
        socios.value = data.data;
        meta.value = data.meta;
    } catch (e) {
        socios.value = [];
        meta.value = { current_page: 1, last_page: 1, total: 0 };
        ui.toast(e.response?.data?.message || 'No se pudieron cargar los socios.', 'error');
    } finally {
        loading.value = false;
    }
}

watch(estado, () => { page.value = 1; load(); });
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { page.value = 1; load(); }, 350);
});

onMounted(load);

function estadoVariant(e) {
    return { activo: 'green', inactivo: 'gray', suspendido: 'red' }[e] || 'gray';
}

function formatDate(d) {
    if (!d) return '—';
    const value = String(d);
    let normalized = value;

    // YYYY-MM-DD should be treated as local date (not UTC) to avoid -1 day shifts.
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        normalized = `${value}T00:00:00`;
    } else if (value.includes(' ')) {
        normalized = value.replace(' ', 'T');
    }

    const dt = new Date(normalized);
    return Number.isNaN(dt.getTime()) ? '—' : dt.toLocaleDateString('es-AR');
}

function confirmDelete(socio) {
    deleteTarget.value = socio;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/socios/${deleteTarget.value.id}`);
        ui.toast('Socio eliminado correctamente.', 'success');
        deleteTarget.value = null;
        load();
    } catch {
        ui.toast('Error al eliminar el socio.', 'error');
    } finally {
        deleting.value = false;
    }
}

function initials(nombre) {
    return (nombre || '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

const colors = ['bg-violet-100 text-violet-700', 'bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-pink-100 text-pink-700'];
function avatarColor(id) { return colors[id % colors.length]; }
</script>

<template>
    <div>
        <!-- Page header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Socios</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ meta.total }} socios registrados
                </p>
            </div>
            <router-link
                to="/socios/nuevo"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo socio
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                />
            </div>
            <!-- Estado filter -->
            <select
                v-model="estado"
                class="px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
            >
                <option value="">Todos los estados</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
                <option value="suspendido">Suspendidos</option>
            </select>
        </div>

        <!-- Table card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Loading skeleton -->
            <div v-if="loading" class="divide-y divide-gray-50">
                <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-6 py-4 animate-pulse">
                    <div class="w-9 h-9 rounded-full bg-gray-200 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/3" />
                        <div class="h-3 bg-gray-100 rounded w-1/2" />
                    </div>
                    <div class="h-5 bg-gray-200 rounded-full w-16" />
                    <div class="w-16 h-3 bg-gray-100 rounded" />
                </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="socios.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <p class="text-sm font-medium">No se encontraron socios</p>
                <p class="text-xs mt-1">
                    <template v-if="search || estado">Probá cambiando los filtros</template>
                    <template v-else>Creá el primer socio para empezar</template>
                </p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-6 py-3">Socio</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden md:table-cell">Email</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden sm:table-cell">Teléfono</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden xl:table-cell">Último pago</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden xl:table-cell">Próximo pago</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3">Estado</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden lg:table-cell">Alta</th>
                            <th class="px-4 py-3 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="socio in socios"
                            :key="socio.id"
                            class="hover:bg-gray-50/60 transition-colors group"
                        >
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0', avatarColor(socio.id)]">
                                        {{ initials(socio.nombre) }}
                                    </div>
                                    <div>
                                        <button
                                            @click="router.push(`/socios/${socio.id}`)"
                                            class="text-sm font-medium text-gray-900 hover:text-violet-700 transition-colors text-left"
                                        >
                                            {{ socio.nombre }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden md:table-cell">{{ socio.email || '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden sm:table-cell">{{ socio.telefono || '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden xl:table-cell">{{ formatDate(socio.fecha_ultimo_pago) }}</td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden xl:table-cell">{{ formatDate(socio.fecha_proximo_pago) }}</td>
                            <td class="px-4 py-3.5">
                                <BaseBadge :variant="estadoVariant(socio.estado)">{{ socio.estado }}</BaseBadge>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-400 hidden lg:table-cell">{{ formatDate(socio.created_at) }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click="router.push(`/socios/${socio.id}`)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 transition-colors"
                                        title="Ver detalle"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="router.push(`/socios/${socio.id}/editar`)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="auth.isAdmin"
                                        @click="confirmDelete(socio)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        title="Eliminar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="!loading && meta.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-50">
                <p class="text-sm text-gray-500">
                    Página {{ meta.current_page }} de {{ meta.last_page }}
                </p>
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

        <!-- Delete confirmation modal -->
        <BaseModal :open="!!deleteTarget" title="Eliminar socio" @close="deleteTarget = null">
            <p class="text-sm text-gray-600">
                ¿Estás seguro que querés eliminar a
                <span class="font-semibold text-gray-900">{{ deleteTarget?.nombre }}</span>?
                Esta acción no se puede deshacer.
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button @click="deleteTarget = null" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="doDelete"
                        :disabled="deleting"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-60 transition-colors flex items-center gap-2"
                    >
                        <svg v-if="deleting" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Eliminar
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

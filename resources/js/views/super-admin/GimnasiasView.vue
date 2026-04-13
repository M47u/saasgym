<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const router = useRouter();
const ui     = useUiStore();

const gimnasios    = ref([]);
const meta         = ref({ current_page: 1, last_page: 1, total: 0 });
const loading      = ref(true);
const search       = ref('');
const filtroActivo = ref('');
const page         = ref(1);
const actionTarget = ref(null);  // { gimnasio, action: 'activar'|'desactivar'|'delete' }
const processing   = ref(false);

let searchTimer = null;

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/super-admin/gimnasios', {
            params: {
                search:  search.value || undefined,
                activo:  filtroActivo.value !== '' ? filtroActivo.value : undefined,
                page:    page.value,
            },
        });
        gimnasios.value = data.data;
        meta.value      = data.meta;
    } finally {
        loading.value = false;
    }
}

watch(filtroActivo, () => { page.value = 1; load(); });
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { page.value = 1; load(); }, 350);
});
onMounted(load);

async function doAction() {
    if (!actionTarget.value) return;
    processing.value = true;
    const { gimnasio, action } = actionTarget.value;
    try {
        if (action === 'delete') {
            await axios.delete(`/super-admin/gimnasios/${gimnasio.id}`);
            ui.toast('Gimnasio eliminado.', 'success');
        } else {
            await axios.post(`/super-admin/gimnasios/${gimnasio.id}/${action}`);
            ui.toast(action === 'activar' ? 'Gimnasio activado.' : 'Gimnasio desactivado.', 'success');
        }
        actionTarget.value = null;
        load();
    } catch {
        ui.toast('Error al procesar la acción.', 'error');
    } finally {
        processing.value = false;
    }
}

const modalConfig = {
    activar:    { title: 'Activar gimnasio',    btn: 'Activar',    btnClass: 'bg-emerald-600 hover:bg-emerald-700' },
    desactivar: { title: 'Desactivar gimnasio', btn: 'Desactivar', btnClass: 'bg-amber-600 hover:bg-amber-700'    },
    delete:     { title: 'Eliminar gimnasio',   btn: 'Eliminar',   btnClass: 'bg-red-600 hover:bg-red-700'        },
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gimnasios</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ meta.total }} gimnasios registrados</p>
            </div>
            <router-link
                to="/super-admin/gimnasias/nueva"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo gimnasio
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nombre o email..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                />
            </div>
            <select
                v-model="filtroActivo"
                class="px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent bg-white transition"
            >
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Skeleton -->
            <div v-if="loading" class="divide-y divide-gray-50">
                <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-6 py-4 animate-pulse">
                    <div class="w-10 h-10 rounded-xl bg-gray-200 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/3" />
                        <div class="h-3 bg-gray-100 rounded w-1/2" />
                    </div>
                    <div class="flex gap-2">
                        <div class="w-12 h-4 bg-gray-200 rounded" />
                        <div class="w-12 h-4 bg-gray-100 rounded" />
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-else-if="gimnasios.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
                <p class="text-sm font-medium">No se encontraron gimnasios</p>
            </div>

            <!-- List -->
            <div v-else class="divide-y divide-gray-50">
                <div
                    v-for="g in gimnasios"
                    :key="g.id"
                    class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/60 transition-colors group"
                >
                    <!-- Avatar -->
                    <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 font-bold text-sm flex items-center justify-center shrink-0">
                        {{ g.nombre?.charAt(0).toUpperCase() }}
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900 truncate">{{ g.nombre }}</span>
                            <BaseBadge :variant="g.activo ? 'green' : 'red'">
                                {{ g.activo ? 'Activo' : 'Inactivo' }}
                            </BaseBadge>
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ g.email }}</p>
                    </div>

                    <!-- Counters -->
                    <div class="hidden md:flex items-center gap-5 text-xs text-gray-500 shrink-0">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-3.5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span><strong>{{ g.socios_count ?? 0 }}</strong> socios</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-3.5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                            <span><strong>{{ g.usuarios_count ?? 0 }}</strong> staff</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        <button
                            @click="router.push(`/super-admin/gimnasias/${g.id}/editar`)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Editar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                        </button>
                        <button
                            v-if="g.activo"
                            @click="actionTarget = { gimnasio: g, action: 'desactivar' }"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                            title="Desactivar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </button>
                        <button
                            v-else
                            @click="actionTarget = { gimnasio: g, action: 'activar' }"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                            title="Activar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>
                        <button
                            @click="actionTarget = { gimnasio: g, action: 'delete' }"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            title="Eliminar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="!loading && meta.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-50">
                <p class="text-sm text-gray-500">Página {{ meta.current_page }} de {{ meta.last_page }}</p>
                <div class="flex gap-2">
                    <button @click="page--; load()" :disabled="page <= 1" class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">← Anterior</button>
                    <button @click="page++; load()" :disabled="page >= meta.last_page" class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">Siguiente →</button>
                </div>
            </div>
        </div>

        <!-- Confirmation modal -->
        <BaseModal
            :open="!!actionTarget"
            :title="actionTarget ? modalConfig[actionTarget.action].title : ''"
            @close="actionTarget = null"
        >
            <p class="text-sm text-gray-600">
                <template v-if="actionTarget?.action === 'delete'">
                    ¿Eliminás el gimnasio <strong class="text-gray-900">{{ actionTarget?.gimnasio.nombre }}</strong>?
                    Esta acción se puede revertir pero el gimnasio dejará de ser accesible.
                </template>
                <template v-else-if="actionTarget?.action === 'desactivar'">
                    ¿Desactivás <strong class="text-gray-900">{{ actionTarget?.gimnasio.nombre }}</strong>?
                    Sus usuarios no podrán iniciar sesión ni operar hasta que lo reactives.
                </template>
                <template v-else>
                    ¿Reactivás <strong class="text-gray-900">{{ actionTarget?.gimnasio.nombre }}</strong>?
                    Sus usuarios volverán a tener acceso completo.
                </template>
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button @click="actionTarget = null" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="doAction"
                        :disabled="processing"
                        :class="['px-4 py-2 rounded-lg text-white text-sm font-medium disabled:opacity-60 transition-colors flex items-center gap-2', actionTarget ? modalConfig[actionTarget.action].btnClass : '']"
                    >
                        <svg v-if="processing" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ actionTarget ? modalConfig[actionTarget.action].btn : '' }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

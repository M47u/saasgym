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

const rutinas = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const activa = ref('');
const page = ref(1);
const deleteTarget = ref(null);
const deleting = ref(false);

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value };
        if (activa.value !== '') params.activa = activa.value;
        const { data } = await axios.get('/rutinas', { params });
        rutinas.value = data.data;
        meta.value = data.meta;
    } finally {
        loading.value = false;
    }
}

watch(activa, () => { page.value = 1; load(); });
onMounted(load);

function confirmDelete(r) {
    deleteTarget.value = r;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/rutinas/${deleteTarget.value.id}`);
        ui.toast('Rutina eliminada.', 'success');
        deleteTarget.value = null;
        load();
    } catch {
        ui.toast('Error al eliminar la rutina.', 'error');
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Rutinas</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ meta.total }} rutinas registradas</p>
            </div>
            <router-link
                v-if="auth.isEntrenador"
                to="/rutinas/nueva"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva rutina
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
            <select
                v-model="activa"
                class="px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
            >
                <option value="">Todas</option>
                <option value="1">Activas</option>
                <option value="0">Inactivas</option>
            </select>
        </div>

        <!-- Cards grid -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-3" />
                <div class="h-3 bg-gray-100 rounded w-1/2 mb-4" />
                <div class="h-3 bg-gray-100 rounded w-2/3" />
            </div>
        </div>

        <div v-else-if="rutinas.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
            <p class="text-sm font-medium">No hay rutinas registradas</p>
            <p class="text-xs mt-1">
                <template v-if="activa !== ''">Probá cambiando el filtro</template>
                <template v-else>Creá la primera rutina</template>
            </p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="r in rutinas"
                :key="r.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group overflow-hidden"
            >
                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0 pr-2">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ r.nombre }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">Rutina general</p>
                        </div>
                        <BaseBadge :variant="r.activa ? 'green' : 'gray'">{{ r.activa ? 'Activa' : 'Inactiva' }}</BaseBadge>
                    </div>

                    <p v-if="r.descripcion" class="text-xs text-gray-500 mb-3 line-clamp-2">{{ r.descripcion }}</p>

                    <div class="flex items-center gap-3 text-xs text-gray-400">
                        <span v-if="r.ejercicios?.length" class="ml-auto">
                            {{ r.ejercicios.length }} ejercicio{{ r.ejercicios.length !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-gray-50 flex items-center justify-between">
                    <p class="text-xs text-gray-400 truncate">{{ r.entrenador?.nombre || 'Sin asignar' }}</p>
                    <div v-if="auth.isEntrenador" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            @click="router.push(`/rutinas/${r.id}/editar`)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Editar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                            </svg>
                        </button>
                        <button
                            @click="confirmDelete(r)"
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
        </div>

        <!-- Pagination -->
        <div v-if="!loading && meta.last_page > 1" class="flex items-center justify-between mt-4 px-1">
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

        <!-- Delete modal -->
        <BaseModal :open="!!deleteTarget" title="Eliminar rutina" @close="deleteTarget = null">
            <p class="text-sm text-gray-600">
                ¿Estás seguro que querés eliminar la rutina
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

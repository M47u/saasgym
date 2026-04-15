<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const router = useRouter();
const ui     = useUiStore();
const auth   = useAuthStore();

const planes        = ref([]);
const loading       = ref(true);
const deleteModal   = ref(false);
const planAEliminar = ref(null);
const deleting      = ref(false);
const toggling      = ref(null); // id del plan siendo toggled

onMounted(loadPlanes);

async function loadPlanes() {
    loading.value = true;
    try {
        const { data } = await axios.get('/planes');
        planes.value = data.data ?? data;
    } catch {
        ui.toast('Error al cargar los planes.', 'error');
    } finally {
        loading.value = false;
    }
}

function confirmDelete(plan) {
    planAEliminar.value = plan;
    deleteModal.value = true;
}

async function deletePlan() {
    if (!planAEliminar.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/planes/${planAEliminar.value.id}`);
        ui.toast('Plan eliminado.', 'success');
        planes.value = planes.value.filter(p => p.id !== planAEliminar.value.id);
        deleteModal.value = false;
        planAEliminar.value = null;
    } catch {
        ui.toast('Error al eliminar el plan.', 'error');
    } finally {
        deleting.value = false;
    }
}

async function toggleActivo(plan) {
    toggling.value = plan.id;
    try {
        const { data } = await axios.put(`/planes/${plan.id}`, { activo: !plan.activo });
        const updated = data.data ?? data;
        const idx = planes.value.findIndex(p => p.id === plan.id);
        if (idx !== -1) planes.value[idx] = updated;
        ui.toast(updated.activo ? 'Plan activado.' : 'Plan desactivado.', 'success');
    } catch {
        ui.toast('Error al cambiar el estado del plan.', 'error');
    } finally {
        toggling.value = null;
    }
}

function formatPrecio(precio) {
    if (precio === null || precio === undefined) return '—';
    return `$${Number(precio).toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Planes</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gestioná los planes que ofrece el gimnasio</p>
            </div>
            <router-link
                v-if="auth.isAdmin"
                to="/planes/nuevo"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-colors shadow-sm shadow-violet-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo plan
            </router-link>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div v-for="i in 4" :key="i" class="p-5 animate-pulse flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-200 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 rounded w-1/3" />
                        <div class="h-3 bg-gray-100 rounded w-1/2" />
                    </div>
                    <div class="h-6 bg-gray-100 rounded w-20" />
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="planes.length === 0"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 flex flex-col items-center text-center"
        >
            <div class="w-14 h-14 rounded-2xl bg-violet-100 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-7 text-violet-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">Sin planes registrados</h3>
            <p class="text-sm text-gray-500 max-w-xs mb-5">
                Creá los planes que ofrece tu gimnasio para asignarlos a los socios.
            </p>
            <router-link
                v-if="auth.isAdmin"
                to="/planes/nuevo"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors"
            >
                + Crear primer plan
            </router-link>
        </div>

        <!-- Planes list -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div
                    v-for="plan in planes"
                    :key="plan.id"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-gray-50/60 transition-colors"
                >
                    <!-- Icon -->
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors', plan.activo ? 'bg-violet-100' : 'bg-gray-100']">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" :class="['size-5', plan.activo ? 'text-violet-600' : 'text-gray-400']">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ plan.nombre }}</p>
                        <p v-if="plan.descripcion" class="text-xs text-gray-500 truncate mt-0.5">{{ plan.descripcion }}</p>
                    </div>

                    <!-- Precio -->
                    <span class="text-sm font-semibold text-gray-900 shrink-0 hidden sm:block">
                        {{ formatPrecio(plan.precio) }}
                    </span>

                    <!-- Estado badge -->
                    <BaseBadge :variant="plan.activo ? 'green' : 'gray'" class="shrink-0">
                        {{ plan.activo ? 'Activo' : 'Inactivo' }}
                    </BaseBadge>

                    <!-- Actions (admin only) -->
                    <div v-if="auth.isAdmin" class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <!-- Toggle activo -->
                        <button
                            @click="toggleActivo(plan)"
                            :disabled="toggling === plan.id"
                            :title="plan.activo ? 'Desactivar' : 'Activar'"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-40"
                        >
                            <svg v-if="toggling === plan.id" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else-if="plan.activo" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>

                        <!-- Edit -->
                        <router-link
                            :to="`/planes/${plan.id}/editar`"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            title="Editar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                            </svg>
                        </router-link>

                        <!-- Delete -->
                        <button
                            @click="confirmDelete(plan)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
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

        <!-- Delete modal -->
        <BaseModal v-model="deleteModal">
            <template #header>Eliminar plan</template>
            <template #body>
                <p class="text-sm text-gray-600">
                    ¿Estás seguro que querés eliminar el plan
                    <span class="font-semibold text-gray-900">{{ planAEliminar?.nombre }}</span>?
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Los socios que tengan este plan asignado quedarán sin plan. Esta acción no se puede deshacer.
                </p>
            </template>
            <template #footer>
                <button
                    @click="deleteModal = false"
                    class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    Cancelar
                </button>
                <button
                    @click="deletePlan"
                    :disabled="deleting"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                >
                    <svg v-if="deleting" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                </button>
            </template>
        </BaseModal>
    </div>
</template>

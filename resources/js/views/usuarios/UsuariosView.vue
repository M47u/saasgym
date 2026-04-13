<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const router = useRouter();
const auth = useAuthStore();
const ui = useUiStore();

const usuarios = ref([]);
const loading = ref(true);
const rol = ref('');
const deleteTarget = ref(null);
const deleting = ref(false);

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/usuarios', { params: { rol: rol.value || undefined } });
        usuarios.value = data.data;
    } finally {
        loading.value = false;
    }
}

watch(rol, load);
onMounted(load);

function rolVariant(r) {
    return { admin: 'purple', entrenador: 'blue' }[r] || 'gray';
}

function confirmDelete(u) {
    deleteTarget.value = u;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/usuarios/${deleteTarget.value.id}`);
        ui.toast('Usuario eliminado.', 'success');
        deleteTarget.value = null;
        load();
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Error al eliminar el usuario.', 'error');
    } finally {
        deleting.value = false;
    }
}

function initials(n) {
    return (n || '').split(' ').slice(0, 2).map(c => c[0]).join('').toUpperCase();
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ usuarios.length }} usuarios del gimnasio</p>
            </div>
            <router-link
                to="/usuarios/nuevo"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo usuario
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
            <select
                v-model="rol"
                class="px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
            >
                <option value="">Todos los roles</option>
                <option value="admin">Admin</option>
                <option value="entrenador">Entrenador</option>
            </select>
        </div>

        <!-- List -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="divide-y divide-gray-50">
                <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-6 py-4 animate-pulse">
                    <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/3" />
                        <div class="h-3 bg-gray-100 rounded w-1/2" />
                    </div>
                    <div class="h-5 bg-gray-200 rounded-full w-20" />
                </div>
            </div>

            <div v-else-if="usuarios.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <p class="text-sm font-medium">No hay usuarios registrados</p>
                <p class="text-xs mt-1">
                    <template v-if="rol">Probá cambiando el filtro</template>
                    <template v-else>Creá el primer usuario del gimnasio</template>
                </p>
            </div>

            <ul v-else class="divide-y divide-gray-50">
                <li
                    v-for="u in usuarios"
                    :key="u.id"
                    class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/60 transition-colors group"
                >
                    <div class="w-10 h-10 rounded-full bg-violet-100 text-violet-700 text-sm font-bold flex items-center justify-center shrink-0">
                        {{ initials(u.nombre) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ u.nombre }}
                            <span v-if="u.id === auth.user?.id" class="ml-1.5 text-xs text-gray-400">(vos)</span>
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ u.email }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <BaseBadge :variant="rolVariant(u.rol)" class="capitalize">{{ u.rol }}</BaseBadge>
                        <BaseBadge :variant="u.activo ? 'green' : 'gray'">{{ u.activo ? 'Activo' : 'Inactivo' }}</BaseBadge>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            @click="router.push(`/usuarios/${u.id}/editar`)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Editar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                            </svg>
                        </button>
                        <button
                            v-if="u.id !== auth.user?.id"
                            @click="confirmDelete(u)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            title="Eliminar"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Delete modal -->
        <BaseModal :open="!!deleteTarget" title="Eliminar usuario" @close="deleteTarget = null">
            <p class="text-sm text-gray-600">
                ¿Estás seguro que querés eliminar a
                <span class="font-semibold text-gray-900">{{ deleteTarget?.nombre }}</span>?
                Perderá acceso al sistema. Esta acción no se puede deshacer.
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

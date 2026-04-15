<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import axios from 'axios';

const router = useRouter();
const ui = useUiStore();

const pagos = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const metodo = ref('');
const page = ref(1);
const deleteTarget = ref(null);
const deleting = ref(false);

const metodos = ['efectivo', 'transferencia', 'tarjeta', 'otro'];

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/pagos', {
            params: { metodo: metodo.value || undefined, page: page.value }
        });
        pagos.value = data.data;
        meta.value = data.meta;
    } finally {
        loading.value = false;
    }
}

watch(metodo, () => { page.value = 1; load(); });
onMounted(load);

function metodoBadgeVariant(m) {
    return { efectivo: 'green', transferencia: 'blue', tarjeta: 'purple', otro: 'gray' }[m] || 'gray';
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d + 'T00:00:00').toLocaleDateString('es-AR');
}

function formatDateTime(d) {
    if (!d) return '—';
    const normalized = String(d).includes(' ') ? String(d).replace(' ', 'T') : d;
    const dt = new Date(normalized);
    return Number.isNaN(dt.getTime())
        ? '—'
        : dt.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function formatMoney(v) {
    return '$' + Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2 });
}

function confirmDelete(pago) {
    deleteTarget.value = pago;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/pagos/${deleteTarget.value.id}`);
        ui.toast('Pago eliminado correctamente.', 'success');
        deleteTarget.value = null;
        load();
    } catch {
        ui.toast('Error al eliminar el pago.', 'error');
    } finally {
        deleting.value = false;
    }
}

const totalPagina = () => pagos.value.reduce((s, p) => s + p.monto, 0);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pagos</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ meta.total }} registros en total</p>
            </div>
            <router-link
                to="/pagos/nuevo"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Registrar pago
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
            <select
                v-model="metodo"
                class="px-3.5 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
            >
                <option value="">Todos los métodos</option>
                <option v-for="m in metodos" :key="m" :value="m" class="capitalize">{{ m }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="loading" class="divide-y divide-gray-50">
                <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-6 py-4 animate-pulse">
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 bg-gray-200 rounded w-1/4" />
                        <div class="h-3 bg-gray-100 rounded w-1/3" />
                    </div>
                    <div class="h-5 bg-gray-200 rounded-full w-20" />
                    <div class="h-4 bg-gray-200 rounded w-16" />
                </div>
            </div>

            <div v-else-if="pagos.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>
                <p class="text-sm font-medium">No hay pagos registrados</p>
                <p class="text-xs mt-1">
                    <template v-if="metodo">Probá cambiando el filtro</template>
                    <template v-else>Registrá el primer pago para empezar</template>
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-6 py-3">Socio</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3">Monto</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden sm:table-cell">Fecha</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3">Método</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden md:table-cell">Concepto</th>
                            <th class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider px-4 py-3 hidden lg:table-cell">Registrado</th>
                            <th class="px-4 py-3 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="pago in pagos"
                            :key="pago.id"
                            class="hover:bg-gray-50/60 transition-colors group"
                        >
                            <td class="px-6 py-3.5">
                                <p class="text-sm font-medium text-gray-900">{{ pago.socio?.nombre || '—' }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-sm font-semibold text-emerald-700">{{ formatMoney(pago.monto) }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden sm:table-cell">{{ formatDate(pago.fecha_pago) }}</td>
                            <td class="px-4 py-3.5">
                                <BaseBadge :variant="metodoBadgeVariant(pago.metodo)" class="capitalize">{{ pago.metodo }}</BaseBadge>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden md:table-cell">{{ pago.concepto || '—' }}</td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 hidden lg:table-cell">{{ formatDateTime(pago.fecha_registro || pago.created_at) }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click="router.push(`/pagos/${pago.id}/editar`)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(pago)"
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
                    <tfoot v-if="pagos.length > 0">
                        <tr class="border-t border-gray-100 bg-gray-50/50">
                            <td class="px-6 py-3 text-xs text-gray-400 font-medium">Total página</td>
                            <td class="px-4 py-3 text-sm font-bold text-emerald-700">{{ formatMoney(totalPagina()) }}</td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

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

        <!-- Delete modal -->
        <BaseModal :open="!!deleteTarget" title="Eliminar pago" @close="deleteTarget = null">
            <p class="text-sm text-gray-600">
                ¿Estás seguro que querés eliminar el pago de
                <span class="font-semibold text-gray-900">{{ formatMoney(deleteTarget?.monto || 0) }}</span>
                de <span class="font-semibold text-gray-900">{{ deleteTarget?.socio?.nombre }}</span>?
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

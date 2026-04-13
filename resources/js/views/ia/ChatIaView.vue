<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import axios from 'axios';

const ui = useUiStore();

const socios = ref([]);
const selectedSocioId = ref('');
const messages = ref([]);
const input = ref('');
const sending = ref(false);
const loadingHistory = ref(false);
const chatEl = ref(null);

async function loadSocios() {
    const { data } = await axios.get('/socios', { params: { estado: 'activo', per_page: 200 } });
    socios.value = data.data || [];
}

async function loadHistory() {
    if (!selectedSocioId.value) return;
    loadingHistory.value = true;
    try {
        const { data } = await axios.get('/ia/historial', { params: { socio_id: selectedSocioId.value } });
        const hist = (data.data || []).reverse();
        messages.value = hist.flatMap(h => [
            { role: 'user', content: h.mensaje_usuario, ts: h.created_at },
            { role: 'assistant', content: h.respuesta_ia, ts: h.created_at, tokens: h.tokens_usados },
        ]);
    } finally {
        loadingHistory.value = false;
        scrollToBottom();
    }
}

watch(selectedSocioId, loadHistory);
onMounted(loadSocios);

async function sendMessage() {
    const text = input.value.trim();
    if (!text || !selectedSocioId.value || sending.value) return;

    messages.value.push({ role: 'user', content: text, ts: new Date().toISOString() });
    input.value = '';
    sending.value = true;
    scrollToBottom();

    try {
        const { data } = await axios.post('/ia/chat', {
            socio_id: selectedSocioId.value,
            mensaje_usuario: text,
        });
        const r = data.data ?? data;
        messages.value.push({
            role: 'assistant',
            content: r.respuesta_ia,
            ts: r.created_at,
            tokens: r.tokens_usados,
        });
    } catch (e) {
        ui.toast(e.response?.data?.message || 'Error al comunicarse con la IA.', 'error');
        // Remove optimistic user message
        messages.value.pop();
    } finally {
        sending.value = false;
        scrollToBottom();
    }
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

async function scrollToBottom() {
    await nextTick();
    if (chatEl.value) {
        chatEl.value.scrollTop = chatEl.value.scrollHeight;
    }
}

function formatTime(ts) {
    if (!ts) return '';
    return new Date(ts).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

const selectedSocioName = () => socios.value.find(s => s.id == selectedSocioId.value)?.nombre || '';
</script>

<template>
    <div class="flex flex-col h-full" style="height: calc(100vh - 9rem)">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Chat IA</h1>
            <p class="text-sm text-gray-500 mt-0.5">Asistente de entrenamiento potenciado por inteligencia artificial</p>
        </div>

        <div class="flex flex-col flex-1 min-h-0 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Socio selector -->
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-4">
                <div class="flex items-center gap-2 shrink-0">
                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                    <span class="text-xs font-medium text-gray-500">IA activa</span>
                </div>
                <select
                    v-model="selectedSocioId"
                    class="flex-1 max-w-xs px-3.5 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition bg-white"
                >
                    <option value="" disabled>Seleccioná un socio para consultar</option>
                    <option v-for="s in socios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                </select>
                <button
                    v-if="messages.length > 0"
                    @click="messages = []"
                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors"
                >
                    Limpiar
                </button>
            </div>

            <!-- Messages area -->
            <div
                ref="chatEl"
                class="flex-1 overflow-y-auto px-5 py-4 space-y-4"
            >
                <!-- Empty state -->
                <div v-if="!selectedSocioId" class="flex flex-col items-center justify-center h-full text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 mb-3 opacity-30">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                    </svg>
                    <p class="text-sm font-medium">Seleccioná un socio para comenzar</p>
                    <p class="text-xs mt-1">La IA puede ayudarte con rutinas, nutrición y más</p>
                </div>

                <div v-else-if="loadingHistory" class="space-y-4">
                    <div v-for="i in 3" :key="i" class="animate-pulse">
                        <div :class="['flex', i % 2 === 0 ? 'justify-end' : 'justify-start']">
                            <div class="rounded-2xl bg-gray-200 h-12 w-64" />
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="messages.length === 0"
                    class="flex flex-col items-center justify-center h-full text-gray-400"
                >
                    <div class="w-12 h-12 rounded-2xl bg-violet-100 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-violet-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-600">Hola! Soy tu asistente IA</p>
                    <p class="text-xs mt-1">Consultando sobre: <span class="font-medium text-violet-600">{{ selectedSocioName() }}</span></p>
                    <p class="text-xs mt-3 text-gray-400 max-w-sm text-center">Preguntame sobre rutinas, ejercicios, nutrición, progreso y más.</p>
                </div>

                <template v-else>
                    <div
                        v-for="(msg, i) in messages"
                        :key="i"
                        :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']"
                    >
                        <div :class="['max-w-[75%] space-y-1', msg.role === 'user' ? 'items-end' : 'items-start', 'flex flex-col']">
                            <div :class="[
                                'px-4 py-3 rounded-2xl text-sm leading-relaxed',
                                msg.role === 'user'
                                    ? 'bg-violet-600 text-white rounded-br-sm'
                                    : 'bg-gray-100 text-gray-800 rounded-bl-sm'
                            ]">
                                <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                            </div>
                            <div class="flex items-center gap-2 px-1">
                                <span class="text-xs text-gray-400">{{ formatTime(msg.ts) }}</span>
                                <span v-if="msg.tokens" class="text-xs text-gray-300">· {{ msg.tokens }} tokens</span>
                            </div>
                        </div>
                    </div>

                    <!-- Typing indicator -->
                    <div v-if="sending" class="flex justify-start">
                        <div class="bg-gray-100 px-4 py-3 rounded-2xl rounded-bl-sm">
                            <div class="flex gap-1">
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <div class="px-5 py-4 border-t border-gray-100">
                <div class="flex items-end gap-3">
                    <textarea
                        v-model="input"
                        @keydown="handleKeydown"
                        :disabled="!selectedSocioId || sending"
                        placeholder="Escribí tu consulta... (Enter para enviar, Shift+Enter para nueva línea)"
                        rows="1"
                        class="flex-1 px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition resize-none disabled:bg-gray-50 disabled:text-gray-400"
                        style="min-height: 48px; max-height: 120px;"
                        @input="($event.target.style.height = 'auto', $event.target.style.height = Math.min($event.target.scrollHeight, 120) + 'px')"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="!input.trim() || !selectedSocioId || sending"
                        class="flex items-center justify-center w-11 h-11 rounded-xl bg-violet-600 text-white hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shrink-0"
                    >
                        <svg v-if="sending" class="animate-spin size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

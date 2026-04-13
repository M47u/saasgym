<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route  = useRoute();
const auth   = useAuthStore();

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}

const userInitials = computed(() => {
    const name = auth.user?.nombre || '';
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});

const nav = [
    {
        label: 'Gimnasios',
        to: '/super-admin/gimnasias',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>`,
    },
];

function isActive(to) {
    return route.path === to || route.path.startsWith(to);
}
</script>

<template>
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside class="flex flex-col w-64 bg-slate-950 shrink-0">
            <!-- Logo + role badge -->
            <div class="px-6 py-5 border-b border-slate-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-violet-600 shadow-lg shadow-violet-900/40">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-white">
                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                        </svg>
                    </div>
                    <span class="text-white font-semibold text-lg tracking-tight">SaaSGym</span>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-900/60 text-violet-300 border border-violet-700/40">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3">
                        <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Z" clip-rule="evenodd" />
                    </svg>
                    Super Admin
                </span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                <router-link
                    v-for="item in nav"
                    :key="item.to"
                    :to="item.to"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive(item.to)
                            ? 'bg-violet-600 text-white shadow-sm shadow-violet-900/40'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    ]"
                >
                    <span v-html="item.icon" class="shrink-0" />
                    {{ item.label }}
                </router-link>
            </nav>

            <!-- User -->
            <div class="px-3 py-4 border-t border-slate-800">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-violet-700 text-white text-xs font-bold shrink-0">
                        {{ userInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth.user?.nombre }}</p>
                        <p class="text-xs text-violet-400 font-medium">Super Admin</p>
                    </div>
                    <button @click="handleLogout" class="text-slate-500 hover:text-slate-300 transition-colors" title="Cerrar sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="flex items-center gap-4 px-6 h-16 bg-white border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-50 text-violet-700 text-xs font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5">
                            <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Z" clip-rule="evenodd" />
                        </svg>
                        Panel de control global
                    </span>
                </div>
                <div class="flex-1" />
                <span class="text-sm text-gray-500 hidden sm:block">{{ auth.user?.nombre }}</span>
            </header>

            <!-- Page -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

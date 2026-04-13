<template>
    <div class="min-h-full flex">
        <!-- Left panel -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-2/5 flex-col bg-slate-900 relative overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-violet-500 blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-80 h-80 rounded-full bg-violet-700 blur-3xl"></div>
            </div>

            <div class="relative flex flex-col justify-center flex-1 px-12">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-16">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-600 shadow-lg shadow-violet-900/50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-white">
                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                        </svg>
                    </div>
                    <span class="text-white font-semibold text-xl tracking-tight">SaaSGym</span>
                </div>

                <h1 class="text-white text-4xl font-bold leading-tight mb-4">
                    Gestiona tu gimnasio<br />con inteligencia
                </h1>
                <p class="text-slate-400 text-lg leading-relaxed">
                    Control de socios, pagos, asistencias y rutinas en una sola plataforma.
                </p>

                <!-- Features -->
                <ul class="mt-10 space-y-3">
                    <li v-for="f in features" :key="f" class="flex items-center gap-3 text-slate-300 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-violet-400 shrink-0">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                        {{ f }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right panel -->
        <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 bg-white">
            <!-- Mobile logo -->
            <div class="flex items-center gap-2 mb-10 lg:hidden">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-violet-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-white">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                    </svg>
                </div>
                <span class="font-semibold text-gray-900">SaaSGym</span>
            </div>
            <slot />
        </div>
    </div>
</template>

<script setup>
const features = [
    'Multi-tenant: cada gimnasio tiene sus datos aislados',
    'Control de socios, pagos y asistencias',
    'Rutinas de entrenamiento personalizadas',
    'Chat con Inteligencia Artificial integrado',
];
</script>

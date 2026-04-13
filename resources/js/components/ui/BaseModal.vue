<script setup>
defineProps({ title: String, open: Boolean });
defineEmits(['close']);
</script>

<template>
    <teleport to="body">
        <transition name="fade">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />
                <transition name="slide-up">
                    <div v-if="open" class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
                            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <slot />
                        </div>
                        <!-- Footer -->
                        <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

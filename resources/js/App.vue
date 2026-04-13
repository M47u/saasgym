<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import ToastNotifications from '@/components/ui/ToastNotifications.vue';

const route = useRoute();

const layout = computed(() => {
    if (route.meta.layout === 'auth')       return AuthLayout;
    if (route.meta.layout === 'superAdmin') return SuperAdminLayout;
    return AppLayout;
});
</script>

<template>
    <component :is="layout">
        <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
                <component :is="Component" :key="route.path" />
            </transition>
        </router-view>
    </component>
    <ToastNotifications />
</template>

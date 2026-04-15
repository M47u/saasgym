import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    // ── Auth ──────────────────────────────────────────────────────────────
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { layout: 'auth', guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue'),
        meta: { layout: 'auth', guest: true },
    },

    // ── Root redirect (role-aware) ────────────────────────────────────────
    {
        path: '/',
        redirect: () => {
            const auth = useAuthStore();
            return auth.isSuperAdmin ? '/super-admin/gimnasias' : '/dashboard';
        },
    },

    // ── Gym app ───────────────────────────────────────────────────────────
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/socios',
        name: 'socios',
        component: () => import('@/views/socios/SociosView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/socios/nuevo',
        name: 'socios.create',
        component: () => import('@/views/socios/SocioFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/socios/:id',
        name: 'socios.show',
        component: () => import('@/views/socios/SocioDetailView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/socios/:id/editar',
        name: 'socios.edit',
        component: () => import('@/views/socios/SocioFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Pagos ─────────────────────────────────────────────────────────────
    {
        path: '/pagos',
        name: 'pagos',
        component: () => import('@/views/pagos/PagosView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/pagos/nuevo',
        name: 'pagos.create',
        component: () => import('@/views/pagos/PagoFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/pagos/:id/editar',
        name: 'pagos.edit',
        component: () => import('@/views/pagos/PagoFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Asistencias ───────────────────────────────────────────────────────
    {
        path: '/asistencias',
        name: 'asistencias',
        component: () => import('@/views/asistencias/AsistenciasView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Rutinas ───────────────────────────────────────────────────────────
    {
        path: '/rutinas',
        name: 'rutinas',
        component: () => import('@/views/rutinas/RutinasView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/rutinas/nueva',
        name: 'rutinas.create',
        component: () => import('@/views/rutinas/RutinaFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/rutinas/:id/editar',
        name: 'rutinas.edit',
        component: () => import('@/views/rutinas/RutinaFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Usuarios ──────────────────────────────────────────────────────────
    {
        path: '/usuarios',
        name: 'usuarios',
        component: () => import('@/views/usuarios/UsuariosView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/usuarios/nuevo',
        name: 'usuarios.create',
        component: () => import('@/views/usuarios/UsuarioFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/usuarios/:id/editar',
        name: 'usuarios.edit',
        component: () => import('@/views/usuarios/UsuarioFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Planes ────────────────────────────────────────────────────────────
    {
        path: '/planes',
        name: 'planes',
        component: () => import('@/views/planes/PlanesView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/planes/nuevo',
        name: 'planes.create',
        component: () => import('@/views/planes/PlanFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },
    {
        path: '/planes/:id/editar',
        name: 'planes.edit',
        component: () => import('@/views/planes/PlanFormView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Chat IA ───────────────────────────────────────────────────────────
    {
        path: '/ia/chat',
        name: 'ia.chat',
        component: () => import('@/views/ia/ChatIaView.vue'),
        meta: { requiresAuth: true, requiresGym: true },
    },

    // ── Super admin ───────────────────────────────────────────────────────
    {
        path: '/super-admin',
        redirect: '/super-admin/gimnasias',
    },
    {
        path: '/super-admin/gimnasias',
        name: 'super-admin.gimnasias',
        component: () => import('@/views/super-admin/GimnasiasView.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true, layout: 'superAdmin' },
    },
    {
        path: '/super-admin/gimnasias/nueva',
        name: 'super-admin.gimnasias.create',
        component: () => import('@/views/super-admin/GimnasioFormView.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true, layout: 'superAdmin' },
    },
    {
        path: '/super-admin/gimnasias/:id/editar',
        name: 'super-admin.gimnasias.edit',
        component: () => import('@/views/super-admin/GimnasioFormView.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true, layout: 'superAdmin' },
    },

    // ── Fallback ──────────────────────────────────────────────────────────
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
    const auth = useAuthStore();

    // Not authenticated → send to login
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    // Authenticated guests → redirect based on role
    if (to.meta.guest && auth.isAuthenticated) {
        return auth.isSuperAdmin
            ? { name: 'super-admin.gimnasias' }
            : { name: 'dashboard' };
    }

    // Super-admin-only routes
    if (to.meta.requiresSuperAdmin && !auth.isSuperAdmin) {
        return { name: 'dashboard' };
    }

    // Gym routes blocked for super_admin
    if (to.meta.requiresGym && auth.isSuperAdmin) {
        return { name: 'super-admin.gimnasias' };
    }
});

export default router;

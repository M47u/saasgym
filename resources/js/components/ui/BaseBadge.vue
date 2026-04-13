<script setup>
defineProps({
    variant: { type: String, default: 'gray' }, // gray | green | red | yellow | blue | purple
});

const classes = {
    gray:   'bg-gray-100 text-gray-700',
    green:  'bg-emerald-100 text-emerald-700',
    red:    'bg-red-100 text-red-700',
    yellow: 'bg-amber-100 text-amber-700',
    blue:   'bg-blue-100 text-blue-700',
    purple: 'bg-violet-100 text-violet-700',
};
</script>

<template>
    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', classes[variant] || classes.gray]">
        <slot />
    </span>
</template>

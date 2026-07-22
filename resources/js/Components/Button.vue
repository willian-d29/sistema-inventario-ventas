<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    type: {
        type: String,
        default: 'default'
    },
    buttonType: {
        type: String,
        default: 'button'
    },
    href: {
        type: String,
    },
    target: {
        type: String,
        default: null,
    },
    rel: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <button
        v-if="buttonType === 'button'"
        class="app-button mr-1"
        :class="[
            type === 'red'
            ? 'border-[var(--color-danger)] bg-[var(--color-danger)] text-white hover:opacity-90'
            : type === 'gray'
            ? 'app-button-secondary'
            : 'app-button-primary'
        ]"
    >
        <slot/>
    </button>
    <a
        v-else-if="target"
        :href="href"
        :target="target"
        :rel="target === '_blank' ? rel || 'noopener noreferrer' : rel"
        class="app-button mr-1"
        :class="[
            type === 'red'
            ? 'border-[var(--color-danger)] bg-[var(--color-danger)] text-white hover:opacity-90'
            : type === 'gray'
            ? 'app-button-secondary'
            : 'app-button-primary'
        ]"
    >
        <slot/>
    </a>
    <Link
        v-else
        :href="href"
        class="app-button mr-1"
        :class="[
            type === 'red'
            ? 'border-[var(--color-danger)] bg-[var(--color-danger)] text-white hover:opacity-90'
            : type === 'gray'
            ? 'app-button-secondary'
            : 'app-button-primary'
        ]"
    >
        <slot/>
    </Link>
</template>

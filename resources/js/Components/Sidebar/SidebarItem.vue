<template>
    <li class="items-center mb-1">
        <Link
            :href="route(routeName)"
            class="flex min-h-11 items-center rounded-md px-3 py-2.5 text-sm font-semibold transition-colors"
            :class="[
                  route().current(routeName)
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900',
                ]"
        >
            <i
                v-if="route().current(routeName)"
                class="mr-3 w-5 text-center text-sm opacity-75"
                :class="icon"
            ></i>
            <i v-else
                class="mr-3 w-5 text-center text-sm text-slate-400"
                :class="icon"
            ></i>

            {{ name }}
        </Link>
    </li>
</template>

<script setup>
import {Link} from "@inertiajs/vue3";

defineProps({
    name: {
        type: String,
    },
    routeName: {
        type: [String, Array],
    },
    icon: {
        type: String,
    },
});
</script>

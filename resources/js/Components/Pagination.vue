<script setup>
import {Link} from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
    },
});

function normalizedLabel(label) {
    const text = String(label || '').replace(/&laquo;|&raquo;/g, '').trim();
    if (label?.includes('Previous')) return 'Anterior';
    if (label?.includes('Next')) return 'Siguiente';
    return text;
}
</script>

<template>
    <div v-if="links.length > 3">
        <nav class="block">
            <ul class="flex pl-0 rounded list-none flex-wrap">
                <li v-for="(link, key) in links" :key="key">
                    <p
                        v-if="link.url === null"
                        class="first:ml-0 text-sm font-semibold flex w-10 h-10 mx-1 p-0 rounded-md items-center justify-center leading-tight relative border border-solid border-slate-200 text-slate-400 bg-slate-100">
                        <i v-if="link.label.includes('Previous')" class="fas fa-chevron-left -ml-px"></i>
                        <i v-else-if="link.label.includes('Next')" class="fas fa-chevron-right -mr-px"></i>
                        <span v-else>{{ normalizedLabel(link.label) }}</span>
                    </p>
                    <Link
                        v-else
                        class="first:ml-0 text-sm font-semibold flex w-10 h-10 mx-1 p-0 rounded-md items-center justify-center leading-tight relative border border-solid border-emerald-200"
                        :class="[
                            link.active ? 'text-white bg-emerald-600' : 'bg-white text-emerald-700 hover:bg-emerald-50'
                            ]"
                        :href="link.url"
                        :aria-label="normalizedLabel(link.label)"
                        :aria-current="link.active ? 'page' : null"
                    >
                        <i v-if="link.label.includes('Previous')" class="fas fa-chevron-left -ml-px"></i>
                        <i v-else-if="link.label.includes('Next')" class="fas fa-chevron-right -mr-px"></i>
                        <p v-else>{{ normalizedLabel(link.label) }}</p>
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped>

</style>

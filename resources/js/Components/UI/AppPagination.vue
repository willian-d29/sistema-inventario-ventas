<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/Composables/useI18n.js';
import { computed } from 'vue';

const props = defineProps({
  links: { type: Array, default: () => [] },
  label: { type: String, default: 'Paginación' },
});

const visibleLinks = computed(() => props.links?.filter((link) => link.url || link.active) || []);
const { t } = useI18n();

function normalizedLabel(label) {
  const labels = {
    '&laquo; Previous': t('pagination.previous'),
    'Previous': t('pagination.previous'),
    'Next &raquo;': t('pagination.next'),
    'Next': t('pagination.next'),
    '&laquo; Anterior': t('pagination.previous'),
    'Siguiente &raquo;': t('pagination.next'),
  };

  return labels[label] || String(label || '').replace(/&laquo;|&raquo;/g, '').trim();
}
</script>

<template>
  <nav v-if="visibleLinks.length > 1" class="app-ui-pagination" :aria-label="label">
    <Link
      v-for="link in visibleLinks"
      :key="`${link.label}-${link.url}`"
      :href="link.url || '#'"
      preserve-scroll
      class="app-ui-pagination-link"
      :class="{ 'is-active': link.active, 'is-disabled': !link.url }"
      :aria-current="link.active ? 'page' : null"
      :aria-label="normalizedLabel(link.label)"
      :tabindex="link.url ? null : -1"
    >
      {{ normalizedLabel(link.label) }}
    </Link>
  </nav>
</template>

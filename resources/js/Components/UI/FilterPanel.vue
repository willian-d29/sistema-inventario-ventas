<script setup>
import { useI18n } from '@/Composables/useI18n.js';

const { t } = useI18n();
</script>

<template>
  <section class="app-ui-filter-panel" :aria-label="t('common.filters')">
    <slot />
  </section>
</template>

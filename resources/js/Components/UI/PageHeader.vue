<script setup>
import AppButton from './AppButton.vue';
import { useI18n } from '@/Composables/useI18n.js';

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  count: { type: [Number, String], default: null },
  actionLabel: { type: String, default: null },
  actionHref: { type: String, default: null },
  actionIcon: { type: String, default: 'fa-plus' },
});

const emit = defineEmits(['action']);
const { t } = useI18n();
</script>

<template>
  <header class="app-ui-page-header" data-tour="page-header">
    <div>
      <h1>{{ title }}</h1>
      <p v-if="description">{{ description }}</p>
      <p v-if="count !== null" class="app-ui-page-count">{{ t('formats.records', { count }) }}</p>
    </div>
    <div v-if="$slots.actions || actionLabel" class="flex flex-wrap gap-2" data-tour="primary-action">
      <slot name="actions">
        <AppButton :href="actionHref" :icon="actionIcon" @click="emit('action')">{{ actionLabel }}</AppButton>
      </slot>
    </div>
  </header>
</template>

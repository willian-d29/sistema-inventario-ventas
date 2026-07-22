<script setup>
import { useI18n } from '@/Composables/useI18n.js';

const props = defineProps({
  variant: { type: String, default: 'info' },
  title: { type: String, default: null },
  message: { type: String, default: null },
  dismissible: { type: Boolean, default: false },
});

const emit = defineEmits(['dismiss']);
const { t } = useI18n();

const icons = {
  success: 'fa-check-circle',
  info: 'fa-circle-info',
  warning: 'fa-triangle-exclamation',
  danger: 'fa-circle-exclamation',
  error: 'fa-circle-exclamation',
};
</script>

<template>
  <div class="app-ui-alert" :class="`app-ui-alert-${variant}`" role="status">
    <i class="fas mt-0.5" :class="icons[variant] || icons.info" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">
      <p v-if="title" class="font-black">{{ title }}</p>
      <p v-if="message" class="text-sm"><slot>{{ message }}</slot></p>
      <slot v-else />
      <div v-if="$slots.action" class="mt-2"><slot name="action" /></div>
    </div>
    <button
      v-if="dismissible"
      type="button"
      class="ihc-icon-button h-8 min-h-8 w-8"
      :aria-label="t('common.close_alert')"
      @click="emit('dismiss')"
    >
      <i class="fas fa-times" aria-hidden="true"></i>
    </button>
  </div>
</template>

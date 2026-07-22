<script setup>
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';
import { useI18n } from '@/Composables/useI18n.js';

defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar acción' },
  action: { type: String, required: true },
  consequence: { type: String, required: true },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'danger' },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
});

const emit = defineEmits(['cancel', 'confirm']);
const { t } = useI18n();
</script>

<template>
  <AppModal :show="show" :title="title" size="sm" @close="emit('cancel')">
    <div class="space-y-4">
      <AppAlert :variant="variant === 'danger' ? 'danger' : 'warning'" :title="action" :message="consequence" />
      <AppAlert v-if="error" variant="danger" :title="t('common.could_not_complete')" :message="error" />
    </div>
    <template #footer>
      <AppButton variant="secondary" :disabled="loading" @click="emit('cancel')">{{ cancelText }}</AppButton>
      <AppButton :variant="variant" :loading="loading" :loading-text="t('common.confirming')" @click="emit('confirm')">
        {{ confirmText }}
      </AppButton>
    </template>
  </AppModal>
</template>

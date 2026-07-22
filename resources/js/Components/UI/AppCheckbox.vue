<script setup>
import { computed, getCurrentInstance } from 'vue';

const model = defineModel({ type: Boolean, default: false });

const props = defineProps({
  id: { type: String, default: null },
  label: { type: String, required: true },
  helpText: { type: String, default: null },
  error: { type: String, default: null },
  disabled: { type: Boolean, default: false },
});

const generatedId = getCurrentInstance()?.uid ?? Math.random().toString(36).slice(2);
const inputId = computed(() => props.id || `app-checkbox-${generatedId}`);
const helpId = computed(() => props.helpText ? `${inputId.value}-help` : null);
const errorId = computed(() => props.error ? `${inputId.value}-error` : null);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || null);
</script>

<template>
  <div class="app-ui-checkbox">
    <input
      :id="inputId"
      v-model="model"
      type="checkbox"
      :disabled="disabled"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="describedBy"
    />
    <label :for="inputId" class="min-w-0">
      <span class="block text-sm font-bold text-[var(--color-text-primary)]">{{ label }}</span>
      <span v-if="helpText" :id="helpId" class="app-ui-help">{{ helpText }}</span>
      <span v-if="error" :id="errorId" class="app-ui-error">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ error }}
      </span>
    </label>
  </div>
</template>

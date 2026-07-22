<script setup>
import { computed, getCurrentInstance } from 'vue';

const model = defineModel({ type: [String, Number, Boolean, null], default: '' });

const props = defineProps({
  id: { type: String, default: null },
  label: { type: String, required: true },
  options: { type: Array, default: () => [] },
  required: { type: Boolean, default: false },
  helpText: { type: String, default: null },
  error: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: null },
});

const generatedId = getCurrentInstance()?.uid ?? Math.random().toString(36).slice(2);
const inputId = computed(() => props.id || `app-select-${generatedId}`);
const helpId = computed(() => props.helpText ? `${inputId.value}-help` : null);
const errorId = computed(() => props.error ? `${inputId.value}-error` : null);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || null);
</script>

<template>
  <label class="app-ui-field" :for="inputId">
    <span class="app-ui-label">
      {{ label }} <span v-if="required" aria-hidden="true">*</span>
    </span>
    <select
      :id="inputId"
      v-model="model"
      class="ihc-field"
      :class="{ 'app-ui-field-error': error }"
      :required="required"
      :disabled="disabled"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="describedBy"
    >
      <option v-if="placeholder !== null" value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="String(option.value)" :value="option.value">
        {{ option.label }}
      </option>
      <slot />
    </select>
    <span v-if="helpText" :id="helpId" class="app-ui-help">{{ helpText }}</span>
    <span v-if="error" :id="errorId" class="app-ui-error">
      <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ error }}
    </span>
  </label>
</template>

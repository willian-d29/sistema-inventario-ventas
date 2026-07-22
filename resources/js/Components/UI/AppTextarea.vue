<script setup>
import { computed, getCurrentInstance } from 'vue';

const model = defineModel({ type: [String, null], default: '' });

const props = defineProps({
  id: { type: String, default: null },
  label: { type: String, required: true },
  rows: { type: [String, Number], default: 3 },
  required: { type: Boolean, default: false },
  helpText: { type: String, default: null },
  error: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
});

const generatedId = getCurrentInstance()?.uid ?? Math.random().toString(36).slice(2);
const inputId = computed(() => props.id || `app-textarea-${generatedId}`);
const helpId = computed(() => props.helpText ? `${inputId.value}-help` : null);
const errorId = computed(() => props.error ? `${inputId.value}-error` : null);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || null);
</script>

<template>
  <label class="app-ui-field" :for="inputId">
    <span class="app-ui-label">
      {{ label }} <span v-if="required" aria-hidden="true">*</span>
    </span>
    <textarea
      :id="inputId"
      v-model="model"
      class="ihc-field"
      :class="{ 'app-ui-field-error': error }"
      :rows="rows"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :placeholder="placeholder"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="describedBy"
    ></textarea>
    <span v-if="helpText" :id="helpId" class="app-ui-help">{{ helpText }}</span>
    <span v-if="error" :id="errorId" class="app-ui-error">
      <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ error }}
    </span>
  </label>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue';

const model = defineModel({ type: [String, Number, null], default: '' });

const props = defineProps({
  id: { type: String, default: null },
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  required: { type: Boolean, default: false },
  helpText: { type: String, default: null },
  error: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
  autocomplete: { type: String, default: null },
  autofocus: { type: Boolean, default: false },
  inputmode: { type: String, default: null },
  min: { type: [String, Number], default: null },
  max: { type: [String, Number], default: null },
  step: { type: [String, Number], default: null },
  maxlength: { type: [String, Number], default: null },
});

const generatedId = getCurrentInstance()?.uid ?? Math.random().toString(36).slice(2);
const inputRef = ref(null);
const inputId = computed(() => props.id || `app-input-${generatedId}`);
const helpId = computed(() => props.helpText ? `${inputId.value}-help` : null);
const errorId = computed(() => props.error ? `${inputId.value}-error` : null);
const describedBy = computed(() => [helpId.value, errorId.value].filter(Boolean).join(' ') || null);

defineExpose({ focus: () => inputRef.value?.focus?.() });
</script>

<template>
  <label class="app-ui-field" :for="inputId">
    <span class="app-ui-label">
      {{ label }} <span v-if="required" aria-hidden="true">*</span>
    </span>
    <input
      :id="inputId"
      ref="inputRef"
      v-model="model"
      class="ihc-field"
      :class="{ 'app-ui-field-error': error }"
      :type="type"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :inputmode="inputmode"
      :min="min"
      :max="max"
      :step="step"
      :maxlength="maxlength"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="describedBy"
    />
    <span v-if="helpText" :id="helpId" class="app-ui-help">{{ helpText }}</span>
    <span v-if="error" :id="errorId" class="app-ui-error">
      <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ error }}
    </span>
  </label>
</template>

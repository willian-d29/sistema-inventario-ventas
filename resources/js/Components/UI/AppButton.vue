<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  type: { type: String, default: 'button' },
  href: { type: String, default: null },
  method: { type: String, default: 'get' },
  as: { type: String, default: null },
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  loadingText: { type: String, default: 'Procesando' },
  icon: { type: String, default: null },
  iconPosition: { type: String, default: 'left' },
  fullWidth: { type: Boolean, default: false },
  ariaLabel: { type: String, default: null },
  title: { type: String, default: null },
  target: { type: String, default: null },
  rel: { type: String, default: null },
});

const emit = defineEmits(['click']);
const attrs = useAttrs();

const isDisabled = computed(() => props.disabled || props.loading);
const safeRel = computed(() => props.target === '_blank' ? props.rel || 'noopener noreferrer' : props.rel);
const linkAs = computed(() => props.as || 'a');
const isEditIcon = computed(() => props.icon?.includes('pencil') || props.icon?.includes('edit'));
const isDeleteIcon = computed(() => props.icon?.includes('trash') || props.icon?.includes('delete'));
const isNativeLink = computed(() => Boolean(props.href && props.target));
const passthroughAttrs = computed(() => {
  const { class: _class, ...rest } = attrs;

  return rest;
});

const classes = computed(() => [
  'app-ui-button',
  `app-ui-button-${props.variant}`,
  `app-ui-button-${props.size}`,
  attrs.class,
  {
    'w-full': props.fullWidth,
    'is-loading': props.loading,
  },
]);

const iconClasses = computed(() => [
  'app-ui-button-icon',
  props.icon,
  {
    'app-ui-button-icon-edit': isEditIcon.value,
    'app-ui-button-icon-delete': isDeleteIcon.value,
    'app-ui-button-icon-danger': props.variant === 'danger' && !isDeleteIcon.value,
    'app-ui-button-icon-pdf': props.icon?.includes('file-pdf'),
    'app-ui-button-icon-excel': props.icon?.includes('file-excel'),
    'app-ui-button-icon-view': props.icon?.includes('eye'),
    'app-ui-button-icon-print': props.icon?.includes('print'),
    'app-ui-button-icon-filter': props.icon?.includes('filter'),
    'app-ui-button-icon-clear': props.icon?.includes('eraser'),
    'app-ui-button-icon-save': props.icon?.includes('save'),
    'app-ui-button-icon-download': props.icon?.includes('download'),
  },
]);

function onClick(event) {
  if (isDisabled.value) {
    event.preventDefault();
    return;
  }

  emit('click', event);
}
</script>

<template>
  <a
    v-if="href && isNativeLink"
    v-bind="passthroughAttrs"
    :href="href"
    :class="classes"
    :aria-label="ariaLabel"
    :title="title"
    :aria-disabled="isDisabled ? 'true' : null"
    :tabindex="isDisabled ? -1 : null"
    :target="target"
    :rel="safeRel"
    @click="onClick"
  >
    <i v-if="loading" class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
    <i v-else-if="icon && iconPosition === 'left'" class="fas" :class="iconClasses" aria-hidden="true"></i>
    <span><slot>{{ loading ? loadingText : '' }}</slot></span>
    <span v-if="loading" class="sr-only">{{ loadingText }}</span>
    <i v-if="!loading && icon && iconPosition === 'right'" class="fas" :class="iconClasses" aria-hidden="true"></i>
  </a>

  <Link
    v-else-if="href"
    v-bind="passthroughAttrs"
    :href="href"
    :method="method"
    :as="linkAs"
    :class="classes"
    :aria-label="ariaLabel"
    :title="title"
    :aria-disabled="isDisabled ? 'true' : null"
    :tabindex="isDisabled ? -1 : null"
    :target="target"
    :rel="safeRel"
    @click="onClick"
  >
    <i v-if="loading" class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
    <i v-else-if="icon && iconPosition === 'left'" class="fas" :class="iconClasses" aria-hidden="true"></i>
    <span><slot>{{ loading ? loadingText : '' }}</slot></span>
    <span v-if="loading" class="sr-only">{{ loadingText }}</span>
    <i v-if="!loading && icon && iconPosition === 'right'" class="fas" :class="iconClasses" aria-hidden="true"></i>
  </Link>

  <button
    v-else
    v-bind="passthroughAttrs"
    :type="type"
    :disabled="isDisabled"
    :class="classes"
    :aria-label="ariaLabel"
    :title="title"
    @click="onClick"
  >
    <i v-if="loading" class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
    <i v-else-if="icon && iconPosition === 'left'" class="fas" :class="iconClasses" aria-hidden="true"></i>
    <span><slot>{{ loading ? loadingText : '' }}</slot></span>
    <span v-if="loading" class="sr-only">{{ loadingText }}</span>
    <i v-if="!loading && icon && iconPosition === 'right'" class="fas" :class="iconClasses" aria-hidden="true"></i>
  </button>
</template>

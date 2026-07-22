<script setup>
defineProps({
  variant: { type: String, default: 'neutral' },
  size: { type: String, default: 'md' },
  icon: { type: String, default: null },
  shape: { type: String, default: 'pill' },
});
</script>

<template>
  <span class="app-ui-badge" :class="[`app-ui-badge-${variant}`, `app-ui-badge-${size}`, `app-ui-badge-${shape}`]">
    <i v-if="icon" class="fas" :class="icon" aria-hidden="true"></i>
    <span><slot /></span>
  </span>
</template>

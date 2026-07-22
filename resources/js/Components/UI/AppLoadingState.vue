<script setup>
defineProps({
  text: { type: String, default: 'Cargando información' },
});
</script>

<template>
  <div class="app-ui-loading-state" role="status" aria-live="polite">
    <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
    <span>{{ text }}</span>
  </div>
</template>

<script setup>
import AppButton from './AppButton.vue';

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  icon: { type: String, default: 'fa-box-open' },
  actionLabel: { type: String, default: null },
  actionHref: { type: String, default: null },
});

const emit = defineEmits(['action']);
</script>

<template>
  <div class="app-ui-empty-state">
    <i class="fas" :class="icon" aria-hidden="true"></i>
    <h2>{{ title }}</h2>
    <p v-if="description">{{ description }}</p>
    <AppButton v-if="actionLabel" class="mt-4" :href="actionHref" @click="emit('action')">{{ actionLabel }}</AppButton>
  </div>
</template>

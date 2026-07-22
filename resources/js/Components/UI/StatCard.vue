<script setup>
defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },
  description: { type: String, default: null },
  icon: { type: String, default: null },
  variant: { type: String, default: 'neutral' },
});
</script>

<template>
  <article class="app-ui-stat-card" :class="`app-ui-stat-card-${variant}`">
    <div>
      <p>{{ title }}</p>
      <strong>{{ value }}</strong>
      <span v-if="description">{{ description }}</span>
    </div>
    <i v-if="icon" class="fas" :class="icon" aria-hidden="true"></i>
  </article>
</template>

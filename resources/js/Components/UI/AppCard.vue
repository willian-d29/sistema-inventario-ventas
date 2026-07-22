<script setup>
defineProps({
  title: { type: String, default: null },
  description: { type: String, default: null },
  padded: { type: Boolean, default: true },
});
</script>

<template>
  <section class="app-ui-card" :class="{ 'p-0': !padded }">
    <header v-if="title || description || $slots.header" class="app-ui-card-header">
      <slot name="header">
        <div>
          <h2 v-if="title" class="app-ui-card-title">{{ title }}</h2>
          <p v-if="description" class="app-ui-card-description">{{ description }}</p>
        </div>
      </slot>
    </header>
    <slot />
  </section>
</template>

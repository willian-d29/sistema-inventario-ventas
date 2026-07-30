<script setup>
import defaultImage from '@/assets/img/default-image.jpg';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  src: { type: String, default: null },
  alt: { type: String, default: '' },
  imgClass: { type: [String, Array, Object], default: '' },
  fallbackIcon: { type: String, default: 'fa-box-open' },
  eager: { type: Boolean, default: false },
  contain: { type: Boolean, default: false },
});

const loaded = ref(false);
const failed = ref(false);
const displaySrc = ref('');

const normalizedSrc = computed(() => {
  const value = String(props.src || '').trim();

  return value || defaultImage;
});

const loadingMode = computed(() => (props.eager ? 'eager' : 'lazy'));
const fetchPriority = computed(() => (props.eager ? 'high' : 'low'));

watch(normalizedSrc, (src) => {
  displaySrc.value = src;
  loaded.value = false;
  failed.value = false;
}, { immediate: true });

function handleError() {
  if (displaySrc.value && displaySrc.value !== defaultImage) {
    displaySrc.value = defaultImage;
    loaded.value = false;
    failed.value = true;
    return;
  }

  displaySrc.value = '';
  loaded.value = true;
  failed.value = true;
}
</script>

<template>
  <span
    class="smart-image"
    :class="{ 'is-loaded': loaded, 'is-contain': contain, 'is-error': failed }"
  >
    <span v-if="!loaded" class="smart-image-placeholder" aria-hidden="true"></span>
    <img
      v-if="displaySrc"
      :src="displaySrc"
      :alt="alt"
      :class="imgClass"
      :loading="loadingMode"
      decoding="async"
      :fetchpriority="fetchPriority"
      draggable="false"
      @load="loaded = true"
      @error="handleError"
    />
    <i v-else class="fas" :class="fallbackIcon" aria-hidden="true"></i>
    <slot />
  </span>
</template>

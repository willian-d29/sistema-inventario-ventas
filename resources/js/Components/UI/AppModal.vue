<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import { useI18n } from '@/Composables/useI18n.js';

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, required: true },
  size: { type: String, default: 'md' },
  closeable: { type: Boolean, default: true },
  initialFocus: { type: Object, default: null },
});

const emit = defineEmits(['close']);
const { t } = useI18n();
const dialogRef = ref(null);
let previouslyFocusedElement = null;

const modalTitle = computed(() => String(props.title || 'Dialogo'));
const titleId = computed(() => `app-modal-${modalTitle.value.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`);
const sizeClass = computed(() => ({
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-xl',
  lg: 'sm:max-w-3xl',
  xl: 'sm:max-w-5xl',
})[props.size] || 'sm:max-w-xl');

function focusableElements() {
  if (!dialogRef.value) return [];
  return [...dialogRef.value.querySelectorAll('a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')]
    .filter((element) => element.offsetParent !== null);
}

async function focusInitialElement() {
  await nextTick();
  props.initialFocus?.value?.focus?.();
  if (!props.initialFocus?.value) {
    focusableElements()[0]?.focus?.();
  }
}

function close() {
  if (props.closeable) emit('close');
}

function handleKeydown(event) {
  if (!props.show) return;

  if (event.key === 'Escape') {
    close();
    return;
  }

  if (event.key !== 'Tab') return;
  const elements = focusableElements();
  if (elements.length === 0) return;

  const first = elements[0];
  const last = elements[elements.length - 1];
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
}

watch(() => props.show, async (isOpen) => {
  if (isOpen) {
    previouslyFocusedElement = document.activeElement;
    document.body.style.overflow = 'hidden';
    await focusInitialElement();
  } else {
    document.body.style.overflow = null;
    previouslyFocusedElement?.focus?.();
  }
});

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = null;
});
</script>

<template>
  <Teleport to="body">
    <Transition>
      <div v-if="show" class="app-ui-modal-root">
        <div class="app-ui-modal-overlay" aria-hidden="true" @click="close"></div>
        <section
          ref="dialogRef"
          class="app-ui-modal"
          :class="sizeClass"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          tabindex="-1"
        >
          <header class="app-ui-modal-header">
            <h2 :id="titleId" class="app-ui-card-title">{{ modalTitle }}</h2>
            <button v-if="closeable" type="button" class="ihc-icon-button" :aria-label="t('common.close_modal')" @click="close">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </header>
          <div class="app-ui-modal-body">
            <slot />
          </div>
          <footer v-if="$slots.footer" class="app-ui-modal-footer">
            <slot name="footer" />
          </footer>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

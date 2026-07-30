<script setup>
import { helpFaqItems, helpManualSections, guidedTours, tourKeyForCurrentRoute } from '@/Help/helpContent.js';
import { useI18n } from '@/Composables/useI18n.js';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const { t } = useI18n();
const page = usePage();

const helpOpen = ref(false);
const activeTab = ref('manual');
const tourOpen = ref(false);
const stepIndex = ref(0);
const targetRect = ref(null);
const activeTarget = ref(null);
const executedStepActions = ref(new Set());

const currentTourKey = computed(() => {
  page.url;

  return tourKeyForCurrentRoute();
});
const currentSteps = computed(() => guidedTours[currentTourKey.value] || guidedTours.default);
const activeStep = computed(() => currentSteps.value[stepIndex.value] || currentSteps.value[0]);
const tooltipKey = computed(() => `${currentTourKey.value}-${stepIndex.value}`);
const progress = computed(() => currentSteps.value.length
  ? Math.round(((stepIndex.value + 1) / currentSteps.value.length) * 100)
  : 0);

const tooltipStyle = computed(() => {
  const rect = targetRect.value;
  const margin = 16;
  const width = Math.min(380, Math.max(300, (typeof window === 'undefined' ? 360 : window.innerWidth) - 32));

  if (!rect || typeof window === 'undefined') {
    return {
      left: '50%',
      top: '50%',
      width: `${width}px`,
      transform: 'translate(-50%, -50%)',
    };
  }

  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const below = rect.bottom + margin + 230 < viewportHeight;
  const top = below ? rect.bottom + margin : Math.max(margin, rect.top - 248);
  const left = Math.min(Math.max(margin, rect.left), viewportWidth - width - margin);

  return {
    left: `${left}px`,
    top: `${top}px`,
    width: `${width}px`,
  };
});

const spotlightStyle = computed(() => {
  const rect = targetRect.value;

  if (!rect) return null;

  return {
    left: `${Math.max(8, rect.left - 8)}px`,
    top: `${Math.max(8, rect.top - 8)}px`,
    width: `${Math.min(window.innerWidth - 16, rect.width + 16)}px`,
    height: `${Math.min(window.innerHeight - 16, rect.height + 16)}px`,
  };
});

function openHelp(tab = 'manual') {
  activeTab.value = tab;
  helpOpen.value = true;
}

function closeHelp() {
  helpOpen.value = false;
}

function visibleTarget(selector) {
  if (!selector || typeof document === 'undefined') return null;

  return Array.from(document.querySelectorAll(selector)).find((element) => {
    const rect = element.getBoundingClientRect();

    return rect.width > 0 && rect.height > 0;
  }) || null;
}

function runStepAction(step, key) {
  if (!step?.action || executedStepActions.value.has(key) || typeof document === 'undefined') return;

  const target = visibleTarget(step.action.selector);
  if (!target) return;

  if (step.action.type === 'click') {
    target.click();
    executedStepActions.value.add(key);
  }
}

async function measureStep() {
  if (!tourOpen.value || !activeStep.value || typeof document === 'undefined') return;

  runStepAction(activeStep.value, tooltipKey.value);
  await nextTick();

  const target = visibleTarget(activeStep.value.selector) || visibleTarget(activeStep.value.fallbackSelector);
  activeTarget.value?.classList.remove('app-tour-target-active');
  activeTarget.value = target;

  if (!target) {
    targetRect.value = null;
    return;
  }

  target.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });

  window.setTimeout(() => {
    const rect = target.getBoundingClientRect();
    targetRect.value = {
      left: rect.left,
      top: rect.top,
      right: rect.right,
      bottom: rect.bottom,
      width: rect.width,
      height: rect.height,
    };
    target.classList.add('app-tour-target-active');
  }, 240);
}

async function startTour() {
  stepIndex.value = 0;
  executedStepActions.value = new Set();
  closeHelp();
  tourOpen.value = true;
  document.body.classList.add('app-tour-lock');
  await nextTick();
  measureStep();
}

function stopTour() {
  tourOpen.value = false;
  targetRect.value = null;
  activeTarget.value?.classList.remove('app-tour-target-active');
  activeTarget.value = null;
  document.body.classList.remove('app-tour-lock');
}

function nextStep() {
  if (stepIndex.value >= currentSteps.value.length - 1) {
    stopTour();
    return;
  }

  stepIndex.value += 1;
  measureStep();
}

function previousStep() {
  if (stepIndex.value <= 0) return;

  stepIndex.value -= 1;
  measureStep();
}

function handleKeydown(event) {
  if (!tourOpen.value) return;

  if (event.key === 'Escape') {
    event.preventDefault();
    stopTour();
  }

  if (event.key === 'ArrowRight') {
    event.preventDefault();
    nextStep();
  }

  if (event.key === 'ArrowLeft') {
    event.preventDefault();
    previousStep();
  }
}

watch(stepIndex, () => measureStep());
watch(currentTourKey, () => {
  if (!tourOpen.value) return;

  stepIndex.value = 0;
  nextTick(measureStep);
});
watch(tourOpen, (isOpen) => {
  if (!isOpen) return;
  nextTick(measureStep);
});

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
  window.addEventListener('resize', measureStep);
  window.addEventListener('scroll', measureStep, true);
});

onUnmounted(() => {
  stopTour();
  window.removeEventListener('keydown', handleKeydown);
  window.removeEventListener('resize', measureStep);
  window.removeEventListener('scroll', measureStep, true);
});
</script>

<template>
  <div class="app-help-wrap">
    <button
      type="button"
      class="ihc-icon-button app-help-button"
      :aria-label="t('help.button')"
      :title="t('help.button')"
      :aria-expanded="helpOpen"
      data-tour="topbar-help"
      @click="openHelp('manual')"
    >
      <i class="fas fa-graduation-cap"></i>
    </button>

    <section v-if="helpOpen" class="app-help-panel" aria-live="polite">
      <header class="app-help-header">
        <div>
          <p>{{ t('help.button') }}</p>
          <h2>{{ t('help.title') }}</h2>
          <span>{{ t('help.subtitle') }}</span>
        </div>
        <button type="button" class="ihc-icon-button h-9 w-9" :aria-label="t('actions.close')" :title="t('actions.close')" @click="closeHelp">
          <i class="fas fa-times"></i>
        </button>
      </header>

      <div class="app-help-tabs" role="tablist" :aria-label="t('help.title')">
        <button type="button" :class="{ 'is-active': activeTab === 'manual' }" @click="activeTab = 'manual'">
          <i class="fas fa-book-open"></i>{{ t('help.tabs.manual') }}
        </button>
        <button type="button" :class="{ 'is-active': activeTab === 'faq' }" @click="activeTab = 'faq'">
          <i class="fas fa-question-circle"></i>{{ t('help.tabs.faq') }}
        </button>
        <button type="button" :class="{ 'is-active': activeTab === 'tour' }" @click="activeTab = 'tour'">
          <i class="fas fa-route"></i>{{ t('help.tabs.tour') }}
        </button>
      </div>

      <div class="app-help-body">
        <div v-if="activeTab === 'manual'" class="app-help-manual-grid">
          <article v-for="section in helpManualSections" :key="section.titleKey" class="app-help-card" :class="`is-${section.color}`">
            <span class="app-help-card-icon"><i :class="section.icon"></i></span>
            <div class="min-w-0">
              <h3>{{ t(section.titleKey) }}</h3>
              <p>{{ t(section.bodyKey) }}</p>
              <ul>
                <li v-for="bullet in section.bullets" :key="bullet">
                  <i class="fas fa-check"></i>{{ t(bullet) }}
                </li>
              </ul>
            </div>
          </article>
        </div>

        <div v-else-if="activeTab === 'faq'" class="app-help-faq-list">
          <details v-for="item in helpFaqItems" :key="item.questionKey" class="app-help-faq-item">
            <summary>
              <span><i :class="item.icon"></i></span>
              <strong>{{ t(item.questionKey) }}</strong>
              <i class="fas fa-chevron-down"></i>
            </summary>
            <p>{{ t(item.answerKey) }}</p>
          </details>
        </div>

        <div v-else class="app-help-tour-card">
          <div class="app-help-tour-visual">
            <span><i class="fas fa-magic"></i></span>
            <div>
              <h3>{{ t('help.start_tour') }}</h3>
              <p>{{ t('help.tip_body') }}</p>
            </div>
          </div>
          <button type="button" class="app-help-tour-action" @click="startTour">
            <i class="fas fa-play"></i>{{ t('help.start_tour') }}
          </button>
          <p class="app-ui-help">{{ t('help.step_counter', { current: 1, total: currentSteps.length }) }}</p>
        </div>
      </div>
    </section>
  </div>

  <Teleport to="body">
    <div v-if="tourOpen" class="app-tour-layer">
      <div class="app-tour-backdrop"></div>
      <div v-if="spotlightStyle" :key="`spotlight-${tooltipKey}`" class="app-tour-spotlight" :style="spotlightStyle">
        <span></span>
      </div>

      <section :key="tooltipKey" class="app-tour-tooltip" :style="tooltipStyle" role="dialog" aria-modal="true">
        <div class="app-tour-progress">
          <span :style="{ width: `${progress}%` }"></span>
        </div>
        <header>
          <span class="app-tour-icon"><i :class="activeStep?.icon"></i></span>
          <div>
            <small>{{ t('help.step_counter', { current: stepIndex + 1, total: currentSteps.length }) }}</small>
            <h2>{{ t(activeStep?.titleKey) }}</h2>
          </div>
        </header>
        <p>{{ targetRect ? t(activeStep?.bodyKey) : t('help.missing_target') }}</p>
        <footer>
          <button type="button" class="app-tour-secondary" @click="stopTour">{{ t('help.skip') }}</button>
          <div>
            <button type="button" class="app-tour-secondary" :disabled="stepIndex === 0" @click="previousStep">
              <i class="fas fa-arrow-left"></i>{{ t('help.previous') }}
            </button>
            <button type="button" class="app-tour-primary" @click="nextStep">
              {{ stepIndex >= currentSteps.length - 1 ? t('help.finish') : t('help.next') }}
              <i class="fas" :class="stepIndex >= currentSteps.length - 1 ? 'fa-check' : 'fa-arrow-right'"></i>
            </button>
          </div>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { applyAppearance, normalizePreferences, resolvePreferenceSource } from '@/Composables/useAppearance.js';
import { useI18n } from '@/Composables/useI18n.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const { t } = useI18n();
const savedLabel = ref('');
const skipLocaleAutosave = ref(false);

const form = useForm(normalizePreferences(resolvePreferenceSource(page.props)));

const themeOptions = computed(() => [
  { value: 'system', label: t('preferences.system'), icon: 'fa-circle-half-stroke' },
  { value: 'light', label: t('preferences.light'), icon: 'fa-sun' },
  { value: 'dark', label: t('preferences.dark'), icon: 'fa-moon' },
  { value: 'high_contrast', label: t('preferences.high_contrast'), icon: 'fa-circle-exclamation' },
  { value: 'color_accessible', label: t('preferences.accessible_palette'), icon: 'fa-eye' },
]);

const fontScaleOptions = computed(() => [
  { value: '100', label: t('preferences.normal') },
  { value: '112', label: '112%' },
  { value: '125', label: '125%' },
  { value: '150', label: '150%' },
]);

const localeOptions = computed(() => [
  { value: 'es', label: t('preferences.spanish') },
  { value: 'en', label: t('preferences.english') },
]);

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

watch(
  () => resolvePreferenceSource(page.props),
  (preferences) => form.defaults(normalizePreferences(preferences)),
);

watch(
  () => ({
    theme: form.theme,
    high_contrast: form.high_contrast,
    reduced_motion: form.reduced_motion,
    font_scale: form.font_scale,
    compact_mode: form.compact_mode,
    sidebar_collapsed: form.sidebar_collapsed,
    locale: form.locale,
  }),
  (preferences) => applyAppearance(preferences),
  { deep: true, immediate: true },
);

watch(
  () => form.locale,
  (locale, previousLocale) => {
    if (skipLocaleAutosave.value || !previousLocale || locale === previousLocale) return;

    savedLabel.value = '';
    form.patch(route('profile.preferences.update'), {
      preserveScroll: true,
      preserveState: true,
      only: ['auth', 'userPreferences', 'flash', 'currentCashRegister'],
      onSuccess: () => {
        savedLabel.value = t('preferences.saved');
        form.defaults();
      },
      onError: () => {
        skipLocaleAutosave.value = true;
        form.locale = previousLocale;
        applyAppearance({ ...form.data(), locale: previousLocale });
        savedLabel.value = '';
        skipLocaleAutosave.value = false;
      },
    });
  },
);

function submit() {
  savedLabel.value = '';

  form.patch(route('profile.preferences.update'), {
    preserveScroll: true,
    preserveState: true,
    only: ['auth', 'userPreferences', 'flash', 'currentCashRegister'],
    onSuccess: () => {
      savedLabel.value = t('preferences.saved');
      form.defaults();
    },
    onError: () => {
      savedLabel.value = '';
    },
  });
}

function selectTheme(theme) {
  if (form.theme === theme) return;

  const previousTheme = form.theme;
  form.theme = theme;
  savedLabel.value = '';
  applyAppearance({ ...form.data(), theme });

  form.patch(route('profile.preferences.update'), {
    preserveScroll: true,
    preserveState: true,
    only: ['auth', 'userPreferences', 'flash', 'currentCashRegister'],
    onSuccess: () => {
      savedLabel.value = t('preferences.saved');
      form.defaults();
    },
    onError: () => {
      form.theme = previousTheme;
      applyAppearance({ ...form.data(), theme: previousTheme });
      savedLabel.value = '';
    },
  });
}

</script>

<template>
  <section class="ihc-panel p-5" data-tour="appearance-panel">
    <div class="flex flex-col gap-2 border-b border-[var(--color-border)] pb-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 class="ihc-section-title">{{ t('preferences.title') }}</h2>
        <p class="ihc-help">{{ t('preferences.description') }}</p>
      </div>
      <div class="min-h-6 text-sm font-bold" aria-live="polite">
        <span v-if="form.processing" class="text-[var(--color-info)]">{{ t('preferences.saving') }}</span>
        <span v-else-if="savedLabel" class="text-[var(--color-success)]">
          <i class="fas fa-check-circle mr-1"></i>{{ savedLabel }}
        </span>
        <span v-else-if="hasErrors" class="text-[var(--color-danger)]">
          <i class="fas fa-circle-exclamation mr-1"></i>{{ t('preferences.save_failed') }}
        </span>
      </div>
    </div>

    <form class="mt-5 space-y-6" @submit.prevent="submit">
      <fieldset>
        <legend class="ihc-label mb-2">{{ t('preferences.theme') }}</legend>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
          <label
            v-for="option in themeOptions"
            :key="option.value"
            class="appearance-choice"
            :class="{ 'is-selected': form.theme === option.value }"
          >
            <input
              class="sr-only"
              type="radio"
              name="theme"
              :value="option.value"
              :checked="form.theme === option.value"
              @change="selectTheme(option.value)"
            />
            <i class="fas" :class="option.icon"></i>
            <span>{{ option.label }}</span>
          </label>
        </div>
        <p v-if="form.errors.theme" class="mt-2 text-sm font-semibold text-[var(--color-danger)]">{{ form.errors.theme }}</p>
      </fieldset>

      <div class="grid gap-4 lg:grid-cols-3">
        <label class="block">
          <span class="ihc-label">{{ t('preferences.text') }}</span>
          <select v-model="form.font_scale" class="ihc-field">
            <option v-for="option in fontScaleOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
          <span v-if="form.errors.font_scale" class="mt-1 block text-sm font-semibold text-[var(--color-danger)]">{{ form.errors.font_scale }}</span>
        </label>

        <label class="block">
          <span class="ihc-label">{{ t('preferences.density') }}</span>
          <select v-model="form.compact_mode" class="ihc-field">
            <option :value="false">{{ t('preferences.comfortable') }}</option>
            <option :value="true">{{ t('preferences.compact') }}</option>
          </select>
        </label>

        <label class="block">
          <span class="ihc-label">{{ t('preferences.motion') }}</span>
          <select v-model="form.reduced_motion" class="ihc-field">
            <option :value="false">{{ t('preferences.normal') }}</option>
            <option :value="true">{{ t('preferences.reduced') }}</option>
          </select>
        </label>

        <label class="block">
          <span class="ihc-label">{{ t('preferences.language') }}</span>
          <select v-model="form.locale" class="ihc-field">
            <option v-for="option in localeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </label>
      </div>

      <label class="flex items-start gap-3 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] p-3">
        <input v-model="form.high_contrast" type="checkbox" class="mt-1 h-4 w-4 rounded border-[var(--color-border)] text-[var(--color-primary)]" />
        <span>
          <span class="block text-sm font-bold text-[var(--color-text-primary)]">{{ t('preferences.reinforce_contrast') }}</span>
          <span class="ihc-help">{{ t('preferences.contrast_help') }}</span>
        </span>
      </label>

      <p class="app-ui-help">{{ t('preferences.accessible_palette_help') }}</p>

      <div class="overflow-hidden rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] p-3 sm:p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
          <h3 class="text-sm font-black text-[var(--color-text-primary)]">{{ t('preferences.preview') }}</h3>
          <span class="app-badge app-badge-info">
            <i class="fas fa-circle-info"></i> {{ t('preferences.visible_state') }}
          </span>
        </div>
        <div class="grid min-w-0 gap-4 lg:grid-cols-2">
          <div class="ihc-panel min-w-0 p-3 sm:p-4">
            <p class="text-sm font-bold text-[var(--color-text-primary)]">{{ t('preferences.sale_card') }}</p>
            <p class="mt-1 text-sm text-[var(--color-text-secondary)]">Total S/ 128.50</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <button type="button" class="app-button app-button-primary">
                <i class="fas fa-check"></i> {{ t('preferences.charge') }}
              </button>
              <button type="button" class="app-button app-button-secondary">
                <i class="fas fa-print"></i> {{ t('actions.print') }}
              </button>
            </div>
          </div>
          <div class="min-w-0 space-y-3">
            <input class="ihc-field mt-0" :value="t('products.search_placeholder')" readonly />
            <div class="app-alert app-alert-warning">
              <i class="fas fa-triangle-exclamation"></i>
              <span>{{ t('preferences.restock_warning') }}</span>
            </div>
            <table class="w-full table-fixed text-left text-sm">
              <thead>
                <tr>
                  <th class="px-3 py-2">{{ t('common.name') }}</th>
                  <th class="px-3 py-2">{{ t('common.status') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="px-3 py-2 font-semibold">Arroz 1 kg</td>
                  <td class="px-3 py-2">
                    <span class="app-badge app-badge-success">
                      <i class="fas fa-check-circle"></i> {{ t('common.available') }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="app-button app-button-primary" :disabled="form.processing">
          <i class="fas" :class="form.processing ? 'fa-circle-notch fa-spin' : 'fa-save'"></i>
          {{ form.processing ? t('preferences.saving') : t('actions.save_preferences') }}
        </button>
      </div>
    </form>
  </section>
</template>

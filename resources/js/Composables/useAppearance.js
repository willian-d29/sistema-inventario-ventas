import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { setActiveLocale } from '@/Composables/useI18n.js';

const defaults = {
  theme: 'system',
  high_contrast: false,
  reduced_motion: false,
  font_scale: '100',
  compact_mode: false,
  sidebar_collapsed: false,
  locale: 'es',
};

export function normalizePreferences(preferences = {}) {
  const locale = ['es', 'en'].includes(preferences?.locale) ? preferences.locale : defaults.locale;

  return {
    ...defaults,
    ...(preferences || {}),
    font_scale: String(preferences?.font_scale || defaults.font_scale),
    high_contrast: Boolean(preferences?.high_contrast ?? defaults.high_contrast),
    reduced_motion: Boolean(preferences?.reduced_motion ?? defaults.reduced_motion),
    compact_mode: Boolean(preferences?.compact_mode ?? defaults.compact_mode),
    sidebar_collapsed: Boolean(preferences?.sidebar_collapsed ?? defaults.sidebar_collapsed),
    locale,
  };
}

export function resolvePreferenceSource(pageProps = {}) {
  return pageProps.auth?.user?.preferences || pageProps.userPreferences || {};
}

export function applyAppearance(preferences = {}) {
  const normalized = normalizePreferences(preferences);
  setActiveLocale(normalized.locale);

  if (typeof window !== 'undefined' && typeof window.__laratoryApplyAppearance === 'function') {
    window.__laratoryApplyAppearance(normalized);
    return normalized;
  }

  if (typeof document === 'undefined') {
    return normalized;
  }

  const root = document.documentElement;
  const systemDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches;
  const systemReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
  const effectiveTheme = normalized.theme === 'system'
    ? (systemDark ? 'dark' : 'light')
    : normalized.theme;

  root.dataset.theme = normalized.theme;
  root.dataset.themeEffective = effectiveTheme;
  root.dataset.contrast = normalized.high_contrast || normalized.theme === 'high_contrast' ? 'high' : 'normal';
  root.dataset.fontScale = normalized.font_scale;
  root.dataset.density = normalized.compact_mode ? 'compact' : 'comfortable';
  root.dataset.motion = normalized.reduced_motion || systemReducedMotion ? 'reduced' : 'normal';
  root.lang = normalized.locale;

  return normalized;
}

export function useAppearance() {
  const page = usePage();
  const preferences = computed(() => normalizePreferences(resolvePreferenceSource(page.props)));

  function savePreferences(nextPreferences, options = {}) {
    const mergedPreferences = applyAppearance({
      ...preferences.value,
      ...nextPreferences,
    });

    router.patch(route('profile.preferences.update'), mergedPreferences, {
      preserveScroll: true,
      preserveState: true,
      only: ['userPreferences', 'flash', 'auth', 'currentCashRegister'],
      ...options,
    });
  }

  return {
    preferences,
    applyAppearance,
    savePreferences,
  };
}

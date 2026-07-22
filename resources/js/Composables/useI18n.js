import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { translate } from '@/I18n/index.js';

const allowedLocales = ['es', 'en'];
const activeLocale = ref(null);

export function setActiveLocale(locale) {
  activeLocale.value = allowedLocales.includes(locale) ? locale : null;
}

export function useI18n() {
  const page = usePage();
  const locale = computed(() => {
    if (allowedLocales.includes(activeLocale.value)) {
      return activeLocale.value;
    }

    const preferenceLocale = page.props.auth?.user?.preferences?.locale || page.props.userPreferences?.locale;
    return allowedLocales.includes(preferenceLocale) ? preferenceLocale : 'es';
  });

  function t(key, replacements = {}) {
    return translate(locale.value, key, replacements);
  }

  return { locale, t };
}

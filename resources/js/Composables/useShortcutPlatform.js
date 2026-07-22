import { computed } from 'vue';

function platformText() {
  if (typeof navigator === 'undefined') return '';

  return [
    navigator.platform,
    navigator.userAgent,
    navigator.userAgentData?.platform,
  ].filter(Boolean).join(' ').toLowerCase();
}

export function useShortcutPlatform() {
  const isMac = computed(() => /mac|iphone|ipad|ipod/.test(platformText()));
  const primaryKey = computed(() => isMac.value ? '⌘' : 'Ctrl');

  function formatShortcut(...keys) {
    const normalizedKeys = keys.map((key) => {
      if (isMac.value && String(key).toLowerCase() === 'shift') return '⇧';
      if (isMac.value && String(key).toLowerCase() === 'alt') return '⌥';

      return key;
    });

    return [primaryKey.value, ...normalizedKeys].join(isMac.value ? '' : ' + ');
  }

  function isPrimaryShortcut(event, key = null, options = {}) {
    const hasPrimaryModifier = isMac.value ? event.metaKey : event.ctrlKey;
    if (!hasPrimaryModifier || (event.altKey && !options.allowAlt)) return false;

    return key === null || event.key.toLowerCase() === String(key).toLowerCase();
  }

  return {
    isMac,
    primaryKey,
    formatShortcut,
    isPrimaryShortcut,
  };
}

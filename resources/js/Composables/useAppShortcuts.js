import { computed } from 'vue';
import { useShortcutPlatform } from '@/Composables/useShortcutPlatform.js';

const routeShortcutKeys = {
  dashboard: 'H',
  'carts.index': 'V',
  'sales.index': 'Y',
  'cash-registers.index': 'X',
  'products.index': 'M',
  'categories.index': 'T',
  'unit-types.index': 'N',
  'suppliers.index': 'L',
  'employees.index': 'E',
  'reports.index': 'G',
  'settings.edit': 'A',
  'profile.edit': 'O',
};

export function useAppShortcuts() {
  const { isPrimaryShortcut } = useShortcutPlatform();

  function shortcutKeyForRoute(routeName) {
    return routeShortcutKeys[routeName] || null;
  }

  function shortcutForRoute(routeName) {
    const key = shortcutKeyForRoute(routeName);

    return key ? `g ${key.toLowerCase()}` : null;
  }

  function matchesRouteShortcut(event, routeName) {
    const key = shortcutKeyForRoute(routeName);

    return Boolean(key && event.key.toLowerCase() === key.toLowerCase());
  }

  const commandPaletteShortcut = computed(() => '/');
  const sidebarShortcut = computed(() => '[');

  return {
    commandPaletteShortcut,
    sidebarShortcut,
    shortcutForRoute,
    matchesRouteShortcut,
    isPrimaryShortcut,
  };
}

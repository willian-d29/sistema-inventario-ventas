<script setup>
import Sidebar from '@/Components/Sidebar/Sidebar.vue';
import FooterAdmin from '@/Components/Footers/FooterAdmin.vue';
import { useAppearance } from '@/Composables/useAppearance.js';
import { useAppShortcuts } from '@/Composables/useAppShortcuts.js';
import { useI18n } from '@/Composables/useI18n.js';
import { currentRouteTitle, menuForRole } from '@/Navigation/menu.js';
import { Notification, Notivue, pastelTheme } from 'notivue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
  workspace: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const { preferences, applyAppearance, savePreferences } = useAppearance();
const { t } = useI18n();
const {
  commandPaletteShortcut,
  sidebarShortcut,
  shortcutForRoute,
  matchesRouteShortcut,
  isPrimaryShortcut,
} = useAppShortcuts();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(preferences.value.sidebar_collapsed);
const commandPaletteOpen = ref(false);
const commandQuery = ref('');
const commandInput = ref(null);
const awaitingRouteShortcut = ref(false);
let routeShortcutTimer = null;

const user = computed(() => page.props.auth.user);
const title = computed(() => currentRouteTitle(user.value?.role, t));
const currentCashRegister = computed(() => page.props.currentCashRegister);
const navigationCommands = computed(() => menuForRole(user.value?.role, t)
  .flatMap((group) => group.items)
  .map((item, index) => ({
    ...item,
    shortcut: shortcutForRoute(item.routeName),
    index: index + 1,
    href: route(item.routeName),
  }))
  .filter((item) => item.shortcut));
const filteredCommands = computed(() => {
  const needle = commandQuery.value.trim().toLowerCase();
  if (!needle) return navigationCommands.value;

  return navigationCommands.value.filter((command) => command.label.toLowerCase().includes(needle));
});

watch(preferences, (nextPreferences) => {
  applyAppearance(nextPreferences);
  sidebarCollapsed.value = nextPreferences.sidebar_collapsed;
}, { immediate: true });

function toggleSidebarCollapsed() {
  const nextValue = !sidebarCollapsed.value;
  sidebarCollapsed.value = nextValue;
  savePreferences(
    { sidebar_collapsed: nextValue },
    {
      onError: () => {
        sidebarCollapsed.value = preferences.value.sidebar_collapsed;
      },
    },
  );
}

function isTextField(target) {
  return ['input', 'textarea', 'select'].includes(target?.tagName?.toLowerCase()) || target?.isContentEditable;
}

function openCommandPalette() {
  commandPaletteOpen.value = true;
  commandQuery.value = '';
  nextTick(() => commandInput.value?.focus());
}

function closeCommandPalette() {
  commandPaletteOpen.value = false;
}

function clearRouteShortcut() {
  awaitingRouteShortcut.value = false;
  if (routeShortcutTimer) {
    clearTimeout(routeShortcutTimer);
    routeShortcutTimer = null;
  }
}

function startRouteShortcut() {
  awaitingRouteShortcut.value = true;
  if (routeShortcutTimer) clearTimeout(routeShortcutTimer);
  routeShortcutTimer = setTimeout(clearRouteShortcut, 1400);
}

function runCommand(command) {
  if (!command) return;

  clearRouteShortcut();
  closeCommandPalette();
  router.visit(command.href);
}

function handleGlobalCommands(event) {
  if (event.key === 'Escape') {
    clearRouteShortcut();
    if (commandPaletteOpen.value) {
      event.preventDefault();
      closeCommandPalette();
    }
    return;
  }

  if (isPrimaryShortcut(event, '/') || (!isTextField(event.target) && event.key === '/')) {
    event.preventDefault();
    clearRouteShortcut();
    openCommandPalette();
    return;
  }

  if (!isTextField(event.target) && event.key === '[') {
    event.preventDefault();
    clearRouteShortcut();
    toggleSidebarCollapsed();
    return;
  }

  if (isTextField(event.target)) return;

  if (!awaitingRouteShortcut.value && event.key.toLowerCase() === 'g') {
    event.preventDefault();
    startRouteShortcut();
    return;
  }

  if (awaitingRouteShortcut.value) {
    const command = navigationCommands.value.find((item) => matchesRouteShortcut(event, item.routeName));
    if (command) {
      event.preventDefault();
      runCommand(command);
      return;
    }

    clearRouteShortcut();
  }
}

onMounted(() => window.addEventListener('keydown', handleGlobalCommands));
onUnmounted(() => {
  clearRouteShortcut();
  window.removeEventListener('keydown', handleGlobalCommands);
});
</script>

<template>
  <Notivue v-slot="item">
    <Notification :item="item" :theme="pastelTheme" />
  </Notivue>

  <div class="app-shell min-h-screen text-[var(--color-text-primary)]" :class="workspace ? 'md:h-screen md:overflow-hidden' : ''">
    <Sidebar
      :open="sidebarOpen"
      :collapsed="sidebarCollapsed"
      @close="sidebarOpen = false"
      @toggle-collapsed="toggleSidebarCollapsed"
    />

    <div
      class="min-w-0 transition-all duration-200"
      :class="[
        sidebarCollapsed ? 'md:ml-[var(--sidebar-width-collapsed)]' : 'md:ml-[var(--sidebar-width)]',
        workspace ? 'md:h-screen md:overflow-hidden' : ''
      ]"
    >
      <header class="app-topbar sticky top-0 z-20">
        <div class="flex min-h-16 items-center justify-between gap-3 px-4 md:px-6">
          <div class="flex min-w-0 items-center gap-3">
            <button
              type="button"
              class="ihc-icon-button md:hidden"
              aria-controls="app-sidebar"
              :aria-expanded="sidebarOpen"
              :aria-label="t('actions.expand_menu')"
              @click="sidebarOpen = true"
            >
              <i class="fas fa-bars"></i>
            </button>
            <div class="min-w-0">
              <p class="truncate text-lg font-black text-[var(--color-text-primary)]">{{ title }}</p>
              <p class="hidden text-xs font-semibold text-[var(--color-text-muted)] sm:block">
                <slot name="breadcrumb">{{ title }}</slot>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button type="button" class="app-command-search" :aria-label="t('commands.open')" :title="t('commands.open')" @click="openCommandPalette">
              <i class="fas fa-search"></i>
              <span>{{ t('commands.search_placeholder') }}</span>
              <kbd>{{ commandPaletteShortcut }}</kbd>
            </button>
            <div
              v-if="user?.role === 'cajero'"
              class="hidden rounded-md px-3 py-1.5 text-xs font-bold sm:block"
              :class="currentCashRegister ? 'app-ui-badge app-ui-badge-success app-ui-badge-pill' : 'app-ui-badge app-ui-badge-warning app-ui-badge-pill'"
            >
              <i class="fas mr-1" :class="currentCashRegister ? 'fa-lock-open' : 'fa-lock'"></i>
              {{ currentCashRegister ? t('cash.current_open') : t('cash.current_closed') }}
            </div>
            <button type="button" class="ihc-icon-button" aria-label="Notificaciones" title="Notificaciones">
              <i class="fas fa-bell"></i>
            </button>
            <Link :href="route('profile.edit')" class="app-topbar-avatar" :aria-label="t('navigation.profile')" :title="t('navigation.profile')">
              <img v-if="user?.photo" :src="user.photo" :alt="user.name" />
              <i v-else class="fas fa-user"></i>
            </Link>
          </div>
        </div>
      </header>

      <main
        id="main-content"
        class="mx-auto w-full"
        :class="workspace ? 'h-[calc(100dvh-4rem)] min-h-0 overflow-hidden p-3 md:p-4' : 'px-4 py-5 md:px-6 md:py-6'"
      >
        <slot />
        <FooterAdmin v-if="!workspace" />
      </main>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="commandPaletteOpen" class="fixed inset-0 z-[70] flex items-start justify-center bg-[var(--color-overlay)] px-3 pt-24" @click.self="closeCommandPalette">
      <section class="w-full max-w-xl overflow-hidden rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] shadow-[var(--shadow-lg)]">
        <div class="flex items-center gap-3 border-b border-[var(--color-border)] px-4 py-3">
          <i class="fas fa-terminal text-[var(--color-primary)]" aria-hidden="true"></i>
          <input
            ref="commandInput"
            v-model="commandQuery"
            class="min-w-0 flex-1 bg-transparent text-sm font-semibold text-[var(--color-text-primary)] outline-none"
            :placeholder="t('commands.search_placeholder')"
            @keydown.enter.prevent="runCommand(filteredCommands[0])"
          />
          <kbd class="rounded border border-[var(--color-border)] px-2 py-1 text-xs font-black text-[var(--color-text-muted)]">Esc</kbd>
        </div>
        <div class="max-h-80 overflow-y-auto p-2">
          <button
            v-for="command in filteredCommands"
            :key="command.routeName"
            type="button"
            class="flex w-full items-center gap-3 rounded-md px-3 py-3 text-left hover:bg-[var(--color-surface-alt)]"
            @click="runCommand(command)"
          >
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[var(--color-primary-soft)] text-[var(--color-primary)]">
              <i :class="command.icon" aria-hidden="true"></i>
            </span>
            <span class="min-w-0 flex-1">
              <strong class="block truncate text-sm text-[var(--color-text-primary)]">{{ command.label }}</strong>
              <span class="text-xs font-semibold text-[var(--color-text-muted)]">{{ t('commands.go_to') }}</span>
            </span>
            <kbd class="rounded border border-[var(--color-border)] px-2 py-1 text-xs font-black text-[var(--color-text-muted)]">{{ command.shortcut }}</kbd>
          </button>
          <p v-if="!filteredCommands.length" class="px-3 py-6 text-center text-sm font-semibold text-[var(--color-text-muted)]">
            {{ t('commands.no_results') }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-[var(--color-border)] px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)]">
          <span>{{ commandPaletteShortcut }} {{ t('commands.open') }}</span>
          <span>{{ sidebarShortcut }} {{ t('commands.toggle_sidebar') }}</span>
          <span>{{ t('commands.route_prefix') }}</span>
        </div>
      </section>
    </div>
  </Teleport>
</template>

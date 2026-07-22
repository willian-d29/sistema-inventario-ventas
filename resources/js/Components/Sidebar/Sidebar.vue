<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { menuForRole } from '@/Navigation/menu.js';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'toggleCollapsed']);
const page = usePage();
const { t } = useI18n();
const closeButton = ref(null);
let restoreFocusTo = null;

const user = computed(() => page.props.auth.user);
const groups = computed(() => menuForRole(user.value?.role, t));

function isActive(item) {
  return item.active.some((pattern) => route().current(pattern));
}

function closeDrawer() {
  emit('close');
}

function logout() {
  router.post(route('logout'));
}

function handleKeydown(event) {
  if (event.key === 'Escape' && props.open) {
    closeDrawer();
  }
}

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    restoreFocusTo = document.activeElement;
    document.body.classList.add('overflow-hidden');
    await nextTick();
    closeButton.value?.focus();
  } else {
    document.body.classList.remove('overflow-hidden');
    restoreFocusTo?.focus?.();
  }
});

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.classList.remove('overflow-hidden');
});
</script>

<template>
  <div>
    <div
      v-show="open"
      class="fixed inset-0 z-30 bg-[var(--color-overlay)] md:hidden"
      aria-hidden="true"
      @click="closeDrawer"
    ></div>

    <aside
      id="app-sidebar"
      class="app-sidebar fixed inset-y-0 left-0 z-40 flex flex-col transition-all duration-200 md:translate-x-0"
      :class="[
        open ? 'translate-x-0' : '-translate-x-full',
        collapsed ? 'md:w-[var(--sidebar-width-collapsed)]' : 'md:w-[var(--sidebar-width)]',
        'w-72'
      ]"
      :aria-label="t('navigation.main')"
    >
      <div class="app-sidebar-brand flex min-h-16 items-center justify-between px-4">
        <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3" @click="closeDrawer">
          <ApplicationLogo type="short" class="h-10 shrink-0" />
          <span class="truncate text-sm font-black uppercase text-[var(--color-text-primary)]" :class="collapsed ? 'md:hidden' : ''">LaraTory</span>
        </Link>
        <button
          ref="closeButton"
          type="button"
          class="ihc-icon-button md:hidden"
          :aria-label="t('actions.close')"
          :title="t('actions.close')"
          @click="closeDrawer"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="px-3 py-3" :class="collapsed ? 'md:px-2' : ''">
        <div class="app-sidebar-user flex items-center gap-3 p-2" :class="collapsed ? 'md:justify-center' : ''">
          <div class="app-sidebar-avatar">
            <img v-if="user?.photo" :src="user.photo" :alt="user.name" />
            <i v-else class="fas fa-user"></i>
          </div>
          <div class="min-w-0" :class="collapsed ? 'md:hidden' : ''">
            <p class="truncate text-sm font-bold text-[var(--color-text-primary)]">{{ user?.name }}</p>
            <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">{{ t(`roles.${user?.role || 'cajero'}`) }}</p>
          </div>
        </div>
      </div>

      <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
        <section v-for="group in groups" :key="`${user?.role}-${group.group}`" class="mb-5">
          <h2 class="app-sidebar-group-title" :class="collapsed ? 'md:hidden' : ''">
            {{ group.group }}
          </h2>
          <ul class="space-y-1">
            <li v-for="item in group.items" :key="item.routeName">
              <Link
                :href="route(item.routeName)"
                class="app-sidebar-link group"
                :class="[
                  isActive(item)
                    ? 'is-active'
                    : item.highlight
                      ? 'is-highlight'
                      : '',
                  collapsed ? 'md:justify-center md:px-2' : ''
                ]"
                :aria-current="isActive(item) ? 'page' : null"
                :title="collapsed ? item.label : null"
                @click="closeDrawer"
              >
                <i class="w-5 shrink-0 text-center" :class="item.icon"></i>
                <span class="truncate" :class="collapsed ? 'md:hidden' : ''">{{ item.label }}</span>
                <span
                  v-if="collapsed"
                  class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 hidden -translate-y-1/2 whitespace-nowrap rounded bg-[var(--color-text-primary)] px-2 py-1 text-xs font-semibold text-[var(--color-surface)] shadow-[var(--shadow-lg)] md:group-hover:block"
                  role="tooltip"
                >
                  {{ item.label }}
                </span>
              </Link>
            </li>
          </ul>
        </section>
      </nav>

      <div class="border-t border-[var(--color-border)] p-3">
        <button
          type="button"
          class="hidden min-h-10 w-full items-center justify-center rounded-md border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] md:flex"
          :aria-label="collapsed ? t('actions.expand_menu') : t('actions.collapse_menu')"
          :title="collapsed ? t('actions.expand_menu') : t('actions.collapse_menu')"
          @click="$emit('toggleCollapsed')"
        >
          <i class="fas" :class="collapsed ? 'fa-angle-double-right' : 'fa-angle-double-left'"></i>
        </button>
        <button
          type="button"
          class="mt-2 flex min-h-10 w-full items-center justify-center gap-2 rounded-md text-sm font-bold text-[var(--color-danger)] hover:bg-[color-mix(in_srgb,var(--color-danger)_10%,transparent)]"
          :title="collapsed ? t('actions.logout') : null"
          @click="logout"
        >
          <i class="fas fa-sign-out-alt"></i>
          <span :class="collapsed ? 'md:hidden' : ''">{{ t('actions.logout') }}</span>
        </button>
      </div>
    </aside>
  </div>
</template>

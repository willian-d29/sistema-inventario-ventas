<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { showToast } from '@/Utils/Helper.js';

const props = defineProps({
  currentRegister: Object,
  currentSummary: Object,
  cashiers: Array,
  paymentMethods: Array,
  filters: Object,
  isAdmin: Boolean,
  employeeCashStates: { type: Array, default: () => [] },
});

const { t } = useI18n();
const selectedEmployee = ref(null);
const closeTarget = ref(null);
const showCloseModal = ref(false);
const clockNow = ref(Date.now());
const clockTimer = ref(null);
const refreshTimer = ref(null);

const employeeFilter = ref({
  cashier_id: props.filters?.cashier_id || '',
  status: props.filters?.status || '',
});

const openForm = useForm({ opening_amount: 0, notes: '' });
const closeForm = useForm({
  declared_amounts: { cash: 0 },
  confirmed: true,
});

function money(value) {
  return Number(value || 0).toFixed(2);
}

function statusState(status) {
  return status === 'open'
    ? { label: t('states.open'), variant: 'success', icon: 'fa-lock-open' }
    : { label: t('states.closed'), variant: 'warning', icon: 'fa-lock' };
}

function elapsedSince(value) {
  if (!value) return '--:--:--';

  const startedAt = Date.parse(value);
  if (Number.isNaN(startedAt)) return '--:--:--';

  const totalSeconds = Math.max(Math.floor((clockNow.value - startedAt) / 1000), 0);
  const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
  const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
  const seconds = String(totalSeconds % 60).padStart(2, '0');

  return `${hours}:${minutes}:${seconds}`;
}

function reloadCashData() {
  router.reload({
    only: ['currentRegister', 'currentSummary', 'employeeCashStates'],
    preserveScroll: true,
    preserveState: true,
  });
}

function openRegister() {
  openForm.post(route('cash-registers.open'), {
    preserveScroll: true,
    onSuccess: showToast,
  });
}

const activeCloseTarget = computed(() => closeTarget.value || {
  register_id: props.currentRegister?.id,
  opened_at: props.currentRegister?.opened_at,
  opening_amount: props.currentRegister?.opening_amount,
  expected_cash: props.currentSummary?.expected_cash,
  sales_count: props.currentSummary?.sales_count,
  user: null,
});

function openCloseModal(target = null) {
  closeTarget.value = target;
  const expected = target?.expected_cash ?? props.currentSummary?.expected_cash ?? target?.opening_amount ?? props.currentRegister?.opening_amount ?? 0;

  closeForm.declared_amounts.cash = Number(expected || 0);
  closeForm.confirmed = true;
  showCloseModal.value = true;
}

function closeRegister() {
  if (!activeCloseTarget.value?.register_id) return;

  closeForm.put(route('cash-registers.close', activeCloseTarget.value.register_id), {
    preserveScroll: true,
    onSuccess: () => {
      showCloseModal.value = false;
      closeTarget.value = null;
      selectedEmployee.value = null;
      showToast();
    },
  });
}

const filteredEmployeeStates = computed(() => props.employeeCashStates.filter((item) => {
  if (employeeFilter.value.cashier_id && Number(item.user.id) !== Number(employeeFilter.value.cashier_id)) return false;
  if (employeeFilter.value.status && item.status !== employeeFilter.value.status) return false;

  return true;
}));

const adminSummary = computed(() => {
  const employees = props.employeeCashStates;
  const open = employees.filter((item) => item.status === 'open');
  const closed = employees.length - open.length;

  return {
    employees: employees.length,
    open: open.length,
    closed,
    sales: open.reduce((sum, item) => sum + Number(item.sales_total || 0), 0),
    expectedCash: open.reduce((sum, item) => sum + Number(item.expected_cash || 0), 0),
  };
});

const currentExpectedCash = computed(() => Number(activeCloseTarget.value?.expected_cash ?? activeCloseTarget.value?.opening_amount ?? 0));
const closeDifference = computed(() => Number(closeForm.declared_amounts.cash || 0) - currentExpectedCash.value);
const closeDifferenceState = computed(() => {
  if (Math.abs(closeDifference.value) <= 0.01) {
    return { label: t('states.balanced'), variant: 'success', icon: 'fa-scale-balanced' };
  }

  return closeDifference.value < 0
    ? { label: t('states.missing'), variant: 'danger', icon: 'fa-arrow-trend-down' }
    : { label: t('states.surplus'), variant: 'warning', icon: 'fa-arrow-trend-up' };
});

onMounted(() => {
  clockTimer.value = window.setInterval(() => {
    clockNow.value = Date.now();
  }, 1000);
  refreshTimer.value = window.setInterval(reloadCashData, 30000);
});

onUnmounted(() => {
  window.clearInterval(clockTimer.value);
  window.clearInterval(refreshTimer.value);
});
</script>

<template>
  <Head :title="isAdmin ? t('cash.admin_title') : t('cash.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ isAdmin ? t('cash.admin_title') : t('cash.title') }}</template>

    <div class="space-y-5 px-4">
      <template v-if="isAdmin">
        <PageHeader
          :title="t('cash.admin_title')"
          :description="t('cash.status_board_description')"
          :count="employeeCashStates.length"
        />

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" data-tour="summary-cards">
          <StatCard :title="t('cash.employees')" :value="adminSummary.employees" icon="fa-users" variant="info" />
          <StatCard :title="t('cash.open_boxes')" :value="adminSummary.open" icon="fa-lock-open" variant="success" />
          <StatCard :title="t('cash.closed_boxes')" :value="adminSummary.closed" icon="fa-lock" variant="warning" />
          <StatCard :title="t('cash.expected_cash')" :value="`S/ ${money(adminSummary.expectedCash)}`" icon="fa-wallet" variant="analytics" />
        </div>

        <section class="ihc-panel p-4" data-tour="filters-panel">
          <div class="grid gap-3 md:grid-cols-[1fr_220px_220px] md:items-end">
            <label class="ihc-label">
              {{ t('cash.employee') }}
              <select v-model="employeeFilter.cashier_id" class="ihc-field">
                <option value="">{{ t('cash.all_employees') }}</option>
                <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">{{ cashier.name }}</option>
              </select>
            </label>
            <label class="ihc-label">
              {{ t('common.status') }}
              <select v-model="employeeFilter.status" class="ihc-field">
                <option value="">{{ t('common.all') }}</option>
                <option value="open">{{ t('states.open') }}</option>
                <option value="closed">{{ t('states.closed') }}</option>
              </select>
            </label>
            <AppButton
              type="button"
              variant="secondary"
              icon="fa-eraser"
              @click="employeeFilter.cashier_id = ''; employeeFilter.status = ''"
            >
              {{ t('actions.clear_filters') }}
            </AppButton>
          </div>
        </section>

        <section class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3" data-tour="records-list">
          <article
            v-for="employee in filteredEmployeeStates"
            :key="employee.user.id"
            class="cash-employee-card"
            :class="employee.status === 'open' ? 'is-open' : 'is-closed'"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h2 class="truncate text-base font-black text-[var(--color-text-primary)]">{{ employee.user.name }}</h2>
                <p class="truncate text-xs font-semibold text-[var(--color-text-muted)]">{{ employee.user.email }}</p>
              </div>
              <AppBadge :variant="statusState(employee.status).variant" :icon="statusState(employee.status).icon">
                {{ statusState(employee.status).label }}
              </AppBadge>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
              <div class="cash-mini-metric" :class="employee.status === 'open' ? 'is-success' : 'is-muted'">
                <span>{{ employee.status === 'open' ? t('cash.elapsed_time') : t('cash.closed_status') }}</span>
                <strong>{{ employee.status === 'open' ? elapsedSince(employee.opened_at) : '-' }}</strong>
              </div>
              <div class="cash-mini-metric">
                <span>{{ t('cash.opening_amount') }}</span>
                <strong>S/ {{ money(employee.opening_amount) }}</strong>
              </div>
              <div class="cash-mini-metric is-success">
                <span>{{ t('cash.shift_sales') }}</span>
                <strong>S/ {{ money(employee.sales_total) }}</strong>
                <small>{{ t('cash.sales_count', { count: employee.sales_count }) }}</small>
              </div>
              <div class="cash-mini-metric is-primary">
                <span>{{ t('cash.expected_cash') }}</span>
                <strong>S/ {{ money(employee.expected_cash) }}</strong>
              </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <AppButton type="button" size="sm" icon="fa-eye" @click="selectedEmployee = employee">
                {{ t('actions.view') }}
              </AppButton>
              <AppButton
                v-if="employee.status === 'open'"
                data-tour="row-actions"
                :href="route('cash-registers.index', { cashier_id: employee.user.id, status: 'open' })"
                size="sm"
                variant="secondary"
                icon="fa-filter"
              >
                {{ t('cash.view_box') }}
              </AppButton>
              <AppButton
                v-if="employee.status === 'open'"
                type="button"
                size="sm"
                variant="danger"
                icon="fa-lock"
                @click="openCloseModal(employee)"
              >
                {{ t('cash.close_register') }}
              </AppButton>
            </div>
          </article>
        </section>

        <AppEmptyState
          v-if="!filteredEmployeeStates.length"
          :title="t('cash.no_employee_boxes')"
          :description="t('cash.no_employee_boxes_help')"
          icon="fa-cash-register"
        />
      </template>

      <template v-else>
        <PageHeader
          :title="t('cash.title')"
          :description="currentRegister ? t('cash.cashier_open_description') : t('cash.cashier_closed_description')"
        >
          <template v-if="currentRegister" #actions>
            <Link :href="route('carts.index')" class="app-ui-button app-ui-button-primary app-ui-button-md">
              <i class="fas fa-cash-register mr-2"></i>{{ t('cash.go_pos') }}
            </Link>
          </template>
        </PageHeader>

        <section v-if="!currentRegister" class="cash-status-hero is-closed" data-tour="cash-status">
          <div class="grid gap-4 lg:grid-cols-[1fr_1.2fr] lg:items-center">
            <div>
              <span class="cash-status-pill"><i class="fas fa-lock"></i>{{ t('states.closed') }}</span>
              <h2>{{ t('cash.current_closed') }}</h2>
            </div>
            <form class="grid gap-3 sm:grid-cols-[1fr_1.5fr_auto]" @submit.prevent="openRegister">
              <label class="ihc-label">
                {{ t('cash.opening_amount') }}
                <input v-model.number="openForm.opening_amount" type="number" min="0" step="0.01" class="ihc-field" />
                <InputError :message="openForm.errors.opening_amount" />
              </label>
              <label class="ihc-label">
                {{ t('cash.notes') }}
                <input v-model="openForm.notes" class="ihc-field" :placeholder="t('pos.optional')" />
              </label>
              <AppButton type="submit" class="self-end" icon="fa-lock-open" :loading="openForm.processing">
                {{ t('cash.open_register') }}
              </AppButton>
            </form>
          </div>
        </section>

        <section v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" data-tour="summary-cards">
          <StatCard :title="t('cash.elapsed_time')" :value="elapsedSince(currentRegister.opened_at)" icon="fa-clock" variant="success" />
          <StatCard :title="t('cash.opening_amount')" :value="`S/ ${money(currentRegister.opening_amount)}`" icon="fa-cash-register" />
          <StatCard :title="t('cash.shift_sales')" :value="currentSummary?.sales_count || 0" icon="fa-receipt" variant="info" />
          <StatCard :title="t('cash.total_charged')" :value="`S/ ${money(currentSummary?.sales)}`" icon="fa-chart-line" variant="analytics" />
          <StatCard :title="t('cash.expected_cash')" :value="`S/ ${money(currentSummary?.expected_cash)}`" icon="fa-wallet" variant="success" />
        </section>

        <section v-if="currentRegister" class="cash-status-hero is-open" data-tour="cash-status">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <span class="cash-status-pill"><i class="fas fa-lock-open"></i>{{ t('states.open') }}</span>
              <h2>{{ t('cash.current_open') }}</h2>
              <p>{{ t('cash.opened_since', { date: currentRegister.opened_at }) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Link :href="route('carts.index')" class="app-ui-button app-ui-button-primary app-ui-button-md">
                <i class="fas fa-cash-register mr-2"></i>{{ t('cash.go_pos') }}
              </Link>
              <AppButton variant="danger" icon="fa-lock" @click="openCloseModal">{{ t('cash.close_register') }}</AppButton>
            </div>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div v-for="method in paymentMethods" :key="method.value" class="cash-mini-metric">
              <span>{{ method.label }}</span>
              <strong>S/ {{ money(currentSummary?.system_amounts?.[method.value]) }}</strong>
            </div>
          </div>
        </section>
      </template>
    </div>

    <Modal
      :title="closeTarget?.user?.name ? t('cash.close_employee_register', { name: closeTarget.user.name }) : t('cash.close_register')"
      :show="showCloseModal"
      :formProcessing="closeForm.processing"
      :submit-button-text="t('cash.finish_shift')"
      @close="showCloseModal = false; closeTarget = null"
      @submitAction="closeRegister"
      maxWidth="lg"
    >
      <div class="space-y-4">
        <div class="cash-status-hero is-open p-4">
          <span class="cash-status-pill"><i class="fas fa-stopwatch"></i>{{ t('cash.elapsed_time') }}</span>
          <h2>{{ elapsedSince(activeCloseTarget?.opened_at) }}</h2>
          <p>{{ t('cash.close_shift_help') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <div class="cash-mini-metric is-success">
            <span>{{ t('cash.expected_cash') }}</span>
            <strong>S/ {{ money(currentExpectedCash) }}</strong>
          </div>
          <div class="cash-mini-metric" :class="closeDifferenceState.variant === 'success' ? 'is-success' : closeDifferenceState.variant === 'danger' ? 'is-danger' : 'is-warning'">
            <span>{{ t('cash.difference') }}</span>
            <strong>S/ {{ money(closeDifference) }}</strong>
            <small>{{ closeDifferenceState.label }}</small>
          </div>
          <div class="cash-mini-metric is-primary">
            <span>{{ t('cash.shift_sales') }}</span>
            <strong>{{ activeCloseTarget?.sales_count || 0 }}</strong>
          </div>
        </div>

        <label class="ihc-label sm:col-span-2">
          {{ t('cash.counted_cash') }}
          <input v-model.number="closeForm.declared_amounts.cash" type="number" min="0" step="0.01" class="ihc-field" />
          <span class="app-ui-help">{{ t('cash.counted_cash_help') }}</span>
          <InputError :message="closeForm.errors['declared_amounts.cash']" />
        </label>
      </div>
    </Modal>

    <Modal
      :title="selectedEmployee?.user?.name || t('cash.employee')"
      :show="Boolean(selectedEmployee)"
      :show-submit-button="false"
      @close="selectedEmployee = null"
      maxWidth="2xl"
    >
      <div v-if="selectedEmployee" class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-black text-[var(--color-text-primary)]">{{ selectedEmployee.user.name }}</h3>
            <p class="app-ui-help">{{ selectedEmployee.user.email }}</p>
          </div>
          <AppBadge :variant="statusState(selectedEmployee.status).variant" :icon="statusState(selectedEmployee.status).icon">
            {{ statusState(selectedEmployee.status).label }}
          </AppBadge>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="cash-mini-metric" :class="selectedEmployee.status === 'open' ? 'is-success' : 'is-muted'">
            <span>{{ selectedEmployee.status === 'open' ? t('cash.elapsed_time') : t('cash.closed_status') }}</span>
            <strong>{{ selectedEmployee.status === 'open' ? elapsedSince(selectedEmployee.opened_at) : '-' }}</strong>
          </div>
          <div class="cash-mini-metric">
            <span>{{ t('cash.opening_amount') }}</span>
            <strong>S/ {{ money(selectedEmployee.opening_amount) }}</strong>
          </div>
          <div class="cash-mini-metric is-success">
            <span>{{ t('cash.total_charged') }}</span>
            <strong>S/ {{ money(selectedEmployee.sales_total) }}</strong>
          </div>
          <div class="cash-mini-metric is-primary">
            <span>{{ t('cash.expected_cash') }}</span>
            <strong>S/ {{ money(selectedEmployee.expected_cash) }}</strong>
          </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
          <p v-for="method in paymentMethods" :key="method.value" class="cash-mini-metric">
            <span>{{ method.label }}</span>
            <strong>S/ {{ money(selectedEmployee.system_amounts?.[method.value]) }}</strong>
          </p>
        </div>

        <div v-if="selectedEmployee.status === 'open'" class="flex justify-end">
          <AppButton type="button" variant="danger" icon="fa-lock" @click="openCloseModal(selectedEmployee)">
            {{ t('cash.close_register') }}
          </AppButton>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

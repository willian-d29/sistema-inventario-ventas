<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { getCurrency, numberFormat } from '@/Utils/Helper.js';

const props = defineProps({
  dashboardRole: String,
  date: String,
  total_orders: Object,
  total_profit: Object,
  total_loss: Object,
  cost_of_goods_sold: Object,
  month_discounts: Object,
  cost_warnings: Object,
  today_sales: Object,
  payments_by_method: Array,
  top_products: Array,
  products_with_highest_profit: Array,
  low_stock_products: Array,
  recent_sales: Array,
  current_cash_registers: Array,
  open_cash_registers_count: Number,
  pending_differences_count: Number,
  products_without_cost_count: Number,
  active_products_count: Number,
  cashierDashboard: Object,
  profit_line_chart: Object,
  orders_bar_chart: Object,
  operating_result: Object,
  total_expense: Object,
});

const form = useForm({ date: props.date });
const { t } = useI18n();

watch(() => form.date, () => {
  if (props.dashboardRole === 'admin') {
    form.get(route('dashboard'), { preserveScroll: true });
  }
});

function money(value) {
  return `${getCurrency()}${numberFormat(Number(value || 0))}`;
}

const paymentTotal = computed(() => (props.payments_by_method || []).reduce((sum, payment) => sum + Number(payment.total || 0), 0));
const topProductsMax = computed(() => Math.max(...(props.top_products || []).map((product) => Number(product.quantity || 0)), 1));
const profitMax = computed(() => Math.max(...(props.products_with_highest_profit || []).map((product) => Number(product.gross_profit || 0)), 1));
const visualPalette = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#e11d48', '#0284c7'];
const chartMonths = computed(() => props.profit_line_chart?.months || []);
const profitValues = computed(() => props.profit_line_chart?.current_year || []);
const lastYearProfitValues = computed(() => props.profit_line_chart?.last_year || []);
const orderValues = computed(() => props.orders_bar_chart?.current_year || []);
const trendMax = computed(() => Math.max(...profitValues.value, ...lastYearProfitValues.value, 1));
const orderMax = computed(() => Math.max(...orderValues.value, 1));
const hasTrend = computed(() => profitValues.value.some((value) => Number(value || 0) > 0) || lastYearProfitValues.value.some((value) => Number(value || 0) > 0));
const currentTrendTotal = computed(() => profitValues.value.reduce((sum, value) => sum + Number(value || 0), 0));
const lastYearTrendTotal = computed(() => lastYearProfitValues.value.reduce((sum, value) => sum + Number(value || 0), 0));
const orderTrendTotal = computed(() => orderValues.value.reduce((sum, value) => sum + Number(value || 0), 0));
const paymentSegments = computed(() => {
  let start = 0;

  return (props.payments_by_method || []).map((payment, index) => {
    const percentage = paymentTotal.value > 0 ? (Number(payment.total || 0) / paymentTotal.value) * 100 : 0;
    const segment = {
      ...payment,
      color: visualPalette[index % visualPalette.length],
      percentage,
      start,
      end: start + percentage,
    };

    start += percentage;

    return segment;
  });
});
const paymentDonutStyle = computed(() => {
  if (!paymentSegments.value.length) {
    return { '--donut-fill': 'conic-gradient(#d7e2ea 0% 100%)' };
  }

  const slices = paymentSegments.value
    .map((segment) => `${segment.color} ${segment.start}% ${segment.end}%`)
    .join(', ');

  return { '--donut-fill': `conic-gradient(${slices})` };
});
const profitPath = computed(() => chartPath(profitValues.value, trendMax.value));
const profitAreaPath = computed(() => chartAreaPath(profitValues.value, trendMax.value));
const lastYearPath = computed(() => chartPath(lastYearProfitValues.value, trendMax.value));
const orderColumns = computed(() => orderValues.value.map((value, index) => ({
  label: chartMonths.value[index] || '',
  shortLabel: shortMonth(chartMonths.value[index] || ''),
  value,
  height: ratio(value, orderMax.value),
})));

const paymentIcons = {
  cash: 'fa-money-bill-wave',
  yape: 'fa-mobile-screen-button',
  plin: 'fa-bolt',
  card: 'fa-credit-card',
  transfer: 'fa-building-columns',
};

function ratio(value, total) {
  const percentage = total > 0 ? (Number(value || 0) / total) * 100 : 0;
  return Math.min(100, Math.max(percentage, Number(value || 0) > 0 ? 5 : 0));
}

function heightStyle(value, total) {
  return { height: `${ratio(value, total)}%` };
}

function visualStyle(index) {
  return { '--accent': visualPalette[index % visualPalette.length] };
}

function paymentIcon(method) {
  return paymentIcons[method] || 'fa-wallet';
}

function shortMonth(month) {
  return String(month || '').slice(0, 3);
}

function chartPoints(values = [], max = 1) {
  const count = values.length;

  if (!count) {
    return [];
  }

  return values.map((value, index) => {
    const x = count === 1 ? 50 : (index / (count - 1)) * 100;
    const y = 88 - ((Number(value || 0) / Math.max(Number(max || 1), 1)) * 72);

    return `${x.toFixed(2)},${Math.max(10, Math.min(88, y)).toFixed(2)}`;
  });
}

function chartPath(values = [], max = 1) {
  const points = chartPoints(values, max);

  return points.length ? `M ${points.join(' L ')}` : '';
}

function chartAreaPath(values = [], max = 1) {
  const points = chartPoints(values, max);

  return points.length ? `M 0,96 L ${points.join(' L ')} L 100,96 Z` : '';
}
</script>

<template>
  <Head :title="t('navigation.dashboard')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ dashboardRole === 'admin' ? t('dashboard.admin_title') : t('dashboard.cashier_title') }}</template>

    <div v-if="dashboardRole === 'admin'" class="space-y-5">
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <PageHeader
          :title="t('dashboard.admin_title')"
          :description="t('dashboard.selected_period', { date })"
        />
        <label class="ihc-label w-full md:w-56" data-tour="dashboard-period">{{ t('dashboard.period') }}
          <input v-model="form.date" type="month" class="ihc-field" />
        </label>
      </div>

      <section class="dashboard-hero" data-tour="dashboard-hero">
        <div class="dashboard-hero-main">
          <span class="dashboard-hero-kicker">{{ t('dashboard.admin_description') }}</span>
          <h2>{{ money(today_sales?.total) }}</h2>
          <p>{{ t('dashboard.today_income') }} · {{ today_sales?.count || 0 }} venta(s)</p>
          <div class="dashboard-hero-actions">
            <Link :href="route('reports.index')" class="app-ui-button app-ui-button-primary app-ui-button-md">
              <i class="fas fa-chart-line mr-2"></i>{{ t('navigation.reports') }}
            </Link>
            <Link :href="route('carts.index')" class="app-ui-button app-ui-button-secondary app-ui-button-md">
              <i class="fas fa-cash-register mr-2"></i>{{ t('dashboard.new_sale') }}
            </Link>
          </div>
        </div>
        <div class="dashboard-hero-visual is-positive">
          <div class="dashboard-hero-bars" aria-hidden="true">
            <span :style="heightStyle(today_sales?.total, Math.max(Number(today_sales?.total || 0), 1))"></span>
            <span :style="heightStyle(today_sales?.gross_profit, Math.max(Number(today_sales?.total || 0), 1))"></span>
            <span :style="heightStyle(today_sales?.cost, Math.max(Number(today_sales?.total || 0), 1))"></span>
          </div>
          <div class="dashboard-result-card">
            <small>{{ t('dashboard.today_gross_profit') }}</small>
            <strong>{{ money(today_sales?.gross_profit) }}</strong>
            <span>{{ t('dashboard.cogs', { amount: money(today_sales?.cost) }) }}</span>
          </div>
        </div>
      </section>

      <div class="ihc-card-grid" data-tour="summary-cards">
        <StatCard :title="t('dashboard.today_income')" :value="money(today_sales?.total)" :description="`${today_sales?.count || 0} venta(s)`" icon="fa-receipt" variant="info" />
        <StatCard :title="t('dashboard.today_gross_profit')" :value="money(today_sales?.gross_profit)" :description="t('dashboard.cogs', { amount: money(today_sales?.cost) })" icon="fa-chart-line" variant="success" />
        <StatCard :title="t('dashboard.active_products')" :value="active_products_count || 0" :description="t('dashboard.products_ready_for_sale')" icon="fa-boxes" variant="analytics" />
        <StatCard :title="t('dashboard.open_registers')" :value="open_cash_registers_count || 0" :description="t('dashboard.pending_differences', { count: pending_differences_count || 0 })" icon="fa-lock-open" variant="warning" />
      </div>

      <section v-if="pending_differences_count || products_without_cost_count || low_stock_products?.length" class="app-alert-card p-4" data-tour="dashboard-alerts">
        <h2 class="ihc-section-title">{{ t('dashboard.operational_alerts') }}</h2>
        <div class="mt-3 grid gap-3 lg:grid-cols-3">
          <Link :href="route('cash-registers.index')" class="app-alert-link">
            <i class="fas fa-triangle-exclamation"></i>
            {{ t('dashboard.cash_differences', { count: pending_differences_count || 0 }) }}
          </Link>
          <Link :href="route('products.index')" class="app-alert-link">
            <i class="fas fa-boxes"></i>
            {{ t('dashboard.low_stock_products', { count: low_stock_products?.length || 0 }) }}
          </Link>
          <Link :href="route('products.index')" class="app-alert-link">
            <i class="fas fa-tag"></i>
            {{ t('dashboard.products_without_cost', { count: products_without_cost_count || 0 }) }}
          </Link>
        </div>
      </section>

      <div class="grid gap-5 xl:grid-cols-3" data-tour="dashboard-analytics">
        <section class="app-premium-panel p-4">
          <div class="dashboard-panel-title">
            <div>
              <span>{{ t('dashboard.collection_mix') }}</span>
              <h2 class="ihc-section-title">{{ t('dashboard.payments_by_method') }}</h2>
            </div>
            <strong>{{ money(paymentTotal) }}</strong>
          </div>
          <div v-if="payments_by_method?.length" class="dashboard-donut-layout">
            <div class="dashboard-donut" :style="paymentDonutStyle" aria-hidden="true">
              <div>
                <span>{{ t('dashboard.total') }}</span>
                <strong>{{ money(paymentTotal) }}</strong>
              </div>
            </div>
            <div class="dashboard-donut-legend">
              <div v-for="(payment, index) in paymentSegments" :key="payment.method" class="dashboard-donut-item" :style="visualStyle(index)">
                <i class="fas" :class="paymentIcon(payment.method)"></i>
                <span>{{ payment.label }}</span>
                <strong>{{ money(payment.total) }}</strong>
                <small>{{ payment.count }} venta(s) · {{ Number(payment.percentage || 0).toFixed(1) }}%</small>
              </div>
            </div>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('common.empty') }}</p>
        </section>

        <section class="app-premium-panel p-4">
          <div class="dashboard-panel-title">
            <div>
              <span>{{ t('dashboard.product_velocity') }}</span>
              <h2 class="ihc-section-title">{{ t('dashboard.top_products') }}</h2>
            </div>
            <i class="fas fa-ranking-star"></i>
          </div>
          <div v-if="top_products?.length" class="dashboard-rank-stack">
            <div v-for="(product, index) in top_products" :key="product.name" class="dashboard-rank-card" :style="{ ...visualStyle(index + 1), '--rank-size': `${ratio(product.quantity, topProductsMax)}%` }">
              <span class="dashboard-rank-number">{{ index + 1 }}</span>
              <div class="min-w-0">
                <strong>{{ product.name }}</strong>
                <small>{{ Number(product.quantity).toFixed(0) }} und. · {{ money(product.total) }}</small>
              </div>
              <span class="dashboard-rank-pulse" aria-hidden="true"></span>
            </div>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('common.empty') }}</p>
        </section>

        <section class="app-premium-panel p-4">
          <div class="dashboard-panel-title">
            <div>
              <span>{{ t('dashboard.margin_focus') }}</span>
              <h2 class="ihc-section-title">{{ t('dashboard.highest_profit') }}</h2>
            </div>
            <i class="fas fa-sack-dollar"></i>
          </div>
          <div v-if="products_with_highest_profit?.length" class="dashboard-profit-cloud">
            <div v-for="(product, index) in products_with_highest_profit" :key="product.name" class="dashboard-profit-chip" :style="{ ...visualStyle(index + 2), '--chip-size': `${ratio(product.gross_profit, profitMax)}%` }">
              <i class="fas fa-chart-simple"></i>
              <div>
                <strong>{{ money(product.gross_profit) }}</strong>
                <span>{{ product.name }}</span>
              </div>
            </div>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('common.empty') }}</p>
        </section>
      </div>

      <section class="dashboard-trend-panel" data-tour="dashboard-trend">
        <div class="dashboard-panel-title">
          <div>
            <span>{{ t('dashboard.monthly_pulse') }}</span>
            <h2 class="ihc-section-title">{{ t('dashboard.revenue_trend') }}</h2>
          </div>
          <strong>{{ money(operating_result?.selected) }}</strong>
        </div>
        <div class="dashboard-trend-stats">
          <div>
            <span>{{ t('dashboard.current_period') }}</span>
            <strong>{{ money(currentTrendTotal) }}</strong>
          </div>
          <div>
            <span>{{ t('dashboard.previous_year') }}</span>
            <strong>{{ money(lastYearTrendTotal) }}</strong>
          </div>
          <div>
            <span>{{ t('dashboard.sales_volume') }}</span>
            <strong>{{ Number(orderTrendTotal || 0).toFixed(0) }}</strong>
          </div>
        </div>
        <div v-if="hasTrend" class="dashboard-trend-grid">
          <div class="dashboard-chart-card">
            <div class="dashboard-chart-legend">
              <span><i class="is-current"></i>{{ t('dashboard.current_period') }}</span>
              <span><i class="is-last"></i>{{ t('dashboard.previous_year') }}</span>
            </div>
            <div class="dashboard-line-chart" aria-hidden="true">
              <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                <path class="dashboard-line-area" :d="profitAreaPath"></path>
                <path class="dashboard-line-last" :d="lastYearPath"></path>
                <path class="dashboard-line-current" :d="profitPath"></path>
              </svg>
            </div>
            <div class="dashboard-chart-axis">
              <span v-for="(month, index) in chartMonths" :key="`${month}-${index}`">{{ shortMonth(month) }}</span>
            </div>
          </div>
          <div class="dashboard-chart-card is-compact">
            <div class="dashboard-chart-legend">
              <span><i class="is-volume"></i>{{ t('dashboard.sales_volume') }}</span>
            </div>
            <div class="dashboard-order-bars" aria-hidden="true">
              <div v-for="(month, index) in orderColumns" :key="`${month.label}-${index}`" class="dashboard-order-column">
                <span :style="{ height: `${month.height}%`, animationDelay: `${index * 70}ms` }"></span>
                <small>{{ month.shortLabel }}</small>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="dashboard-empty-visual">
          <i class="fas fa-chart-line"></i>
          <span>{{ t('common.empty') }}</span>
        </div>
      </section>

      <div class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
        <section class="app-premium-panel p-4" data-tour="recent-records">
          <div class="flex items-center justify-between gap-3">
            <h2 class="ihc-section-title">{{ t('dashboard.recent_sales') }}</h2>
            <Link :href="route('sales.index')" class="text-sm font-bold text-[var(--color-primary)]">{{ t('dashboard.view_history') }}</Link>
          </div>
          <div v-if="recent_sales?.length" class="mt-3 space-y-2 lg:hidden">
            <Link
              v-for="sale in recent_sales"
              :key="`card-${sale.id}`"
              :href="route('sales.show', sale.id)"
              class="block rounded-md border border-[var(--color-border)] p-3 text-sm"
            >
              <span class="flex items-start justify-between gap-3">
                <strong class="break-words">{{ sale.document }}</strong>
                <strong class="shrink-0">{{ money(sale.total) }}</strong>
              </span>
              <span class="mt-1 block ihc-help">{{ sale.cashier }} · {{ sale.sold_at }}</span>
            </Link>
          </div>
          <div v-if="recent_sales?.length" class="mt-3 hidden overflow-x-auto lg:block">
            <table class="w-full text-sm">
              <thead class="text-left text-xs uppercase text-[var(--color-text-muted)]">
                <tr><th class="px-2 py-2">{{ t('common.document') }}</th><th>{{ t('common.cashier') }}</th><th>{{ t('common.date') }}</th><th class="text-right">{{ t('common.total') }}</th></tr>
              </thead>
              <tbody>
                <tr v-for="sale in recent_sales" :key="sale.id">
                  <td class="px-2 py-2 font-bold">{{ sale.document }}</td>
                  <td>{{ sale.cashier }}</td>
                  <td>{{ sale.sold_at }}</td>
                  <td class="text-right font-bold">{{ money(sale.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('empty.no_recent_sales') }}</p>
        </section>

        <section class="app-premium-panel p-4" data-tour="quick-access">
          <h2 class="ihc-section-title">{{ t('dashboard.quick_access') }}</h2>
          <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <Link :href="route('carts.index')" class="app-ui-button app-ui-button-primary app-ui-button-md"><i class="fas fa-cash-register mr-2"></i>{{ t('dashboard.new_sale') }}</Link>
            <Link :href="route('cash-registers.index')" class="app-ui-button app-ui-button-secondary app-ui-button-md"><i class="fas fa-lock-open mr-2"></i>{{ t('dashboard.review_registers') }}</Link>
            <Link :href="route('products.index')" class="app-ui-button app-ui-button-secondary app-ui-button-md"><i class="fas fa-plus mr-2"></i>{{ t('dashboard.register_product') }}</Link>
            <Link :href="route('reports.index')" class="app-ui-button app-ui-button-secondary app-ui-button-md"><i class="fas fa-chart-bar mr-2"></i>{{ t('navigation.reports') }}</Link>
          </div>
        </section>
      </div>
    </div>

    <div v-else class="space-y-5">
      <section class="app-premium-panel p-5" data-tour="dashboard-cashier-state">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-sm font-bold text-[var(--color-primary)]">{{ t('dashboard.cashier_title') }}</p>
            <h1 class="text-2xl font-black text-[var(--color-text-primary)]">
              {{ cashierDashboard?.is_open ? t('dashboard.cashier_open') : t('dashboard.cashier_closed') }}
            </h1>
            <p class="ihc-help">
              {{ cashierDashboard?.is_open ? t('dashboard.opened_at', { date: cashierDashboard.opened_at }) : t('dashboard.open_first') }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Link :href="route('carts.index')" class="app-ui-button app-ui-button-primary app-ui-button-md">
              <i class="fas fa-cash-register mr-2"></i>{{ t('dashboard.go_pos') }}
            </Link>
            <Link :href="route('cash-registers.index')" class="app-ui-button app-ui-button-secondary app-ui-button-md">
              <i class="fas fa-lock-open mr-2"></i>{{ cashierDashboard?.is_open ? t('dashboard.view_my_register') : t('dashboard.open_register') }}
            </Link>
          </div>
        </div>
      </section>

      <div class="ihc-card-grid" data-tour="summary-cards">
        <StatCard :title="t('dashboard.expected_cash')" :value="money(cashierDashboard?.expected_cash)" icon="fa-money-bill-wave" variant="success" />
        <StatCard :title="t('dashboard.shift_sales')" :value="cashierDashboard?.sales_count || 0" icon="fa-receipt" variant="info" />
        <StatCard :title="t('dashboard.total_sold')" :value="money(cashierDashboard?.total_sold)" icon="fa-chart-line" variant="analytics" />
        <StatCard :title="t('dashboard.last_sale')" :value="cashierDashboard?.last_sale?.full_document_number || t('dashboard.no_sales')" icon="fa-clock" variant="warning" />
      </div>

      <section v-if="cashierDashboard?.pending_difference" class="app-alert-card p-4">
        <h2 class="ihc-section-title">{{ t('dashboard.pending_difference') }}</h2>
        <p class="mt-1 text-sm font-semibold text-[var(--color-warning)]">
          Caja #{{ cashierDashboard.pending_difference.id }} con diferencia de {{ money(cashierDashboard.pending_difference.total_difference) }}.
        </p>
      </section>

      <div class="grid gap-5 lg:grid-cols-2">
        <section class="app-premium-panel p-4" data-tour="recent-records">
          <div class="flex items-center justify-between gap-3">
            <h2 class="ihc-section-title">{{ t('dashboard.my_recent_sales') }}</h2>
            <Link :href="route('sales.index')" class="text-sm font-bold text-[var(--color-primary)]">{{ t('dashboard.view_my_sales') }}</Link>
          </div>
          <div v-if="cashierDashboard?.recent_sales?.length" class="mt-3 space-y-2">
            <Link v-for="sale in cashierDashboard.recent_sales" :key="sale.id" :href="route('sales.show', sale.id)" class="block rounded-md border border-[var(--color-border)] px-3 py-2 text-sm hover:bg-[var(--color-surface-alt)]">
              <span class="flex justify-between gap-3">
                <strong>{{ sale.document }}</strong>
                <strong>{{ money(sale.total) }}</strong>
              </span>
              <span class="ihc-help">{{ sale.payments || '-' }} · {{ sale.sold_at }}</span>
            </Link>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('dashboard.no_turn_sales') }}</p>
        </section>

        <section class="app-premium-panel p-4" data-tour="dashboard-movements">
          <h2 class="ihc-section-title">{{ t('dashboard.recent_movements') }}</h2>
          <div v-if="cashierDashboard?.movements?.length" class="mt-3 space-y-2">
            <div v-for="item in cashierDashboard.movements" :key="`${item.kind}-${item.id}`" class="rounded-md border border-[var(--color-border)] px-3 py-2 text-sm">
              <div class="flex justify-between gap-3">
                <strong>{{ item.type_label }}</strong>
                <span>{{ item.signed_amount !== null ? money(Math.abs(Number(item.signed_amount))) : '-' }}</span>
              </div>
              <p class="ihc-help">{{ item.description || item.relation || t('cash.timeline') }}</p>
            </div>
          </div>
          <p v-else class="mt-3 ihc-help">{{ t('empty.no_movements') }}</p>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

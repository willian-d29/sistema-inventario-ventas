<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { cleanQuery } from '@/Utils/Helper.js';
import { computed } from 'vue';

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({
      totalVentas: 0,
      ingresoNetoProductos: 0,
      totalCosto: 0,
      utilidadBruta: 0,
      margenBruto: 0,
      totalDescuentos: 0,
      totalComprobantes: 0,
      totalItems: 0,
      ventasPorMetodo: [],
      ventasPorProducto: [],
      ventasPorCategoria: [],
      ventasPorCajero: [],
      ventasPorDocumento: [],
    }),
  },
  filters: { type: Object, default: () => ({}) },
  cashiers: { type: Array, default: () => [] },
  paymentMethods: { type: Array, default: () => [] },
  documentTypes: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
});

const { t } = useI18n();

const form = useForm({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  cashier_id: props.filters.cashier_id || '',
  payment_method: props.filters.payment_method || '',
  product_id: props.filters.product_id || '',
  document_type: props.filters.document_type || '',
});

const documentLabels = {
  receipt: t('states.receipt'),
  invoice: t('states.invoice'),
};

const paymentLabels = {
  cash: t('states.cash'),
  yape: t('states.yape'),
  plin: t('states.plin'),
  card: t('states.card'),
  transfer: t('states.transfer'),
};

const paymentTotal = computed(() => sumRows(props.summary.ventasPorMetodo));
const productProfitMax = computed(() => maxRows(props.summary.ventasPorProducto, 'gross_profit'));
const categoryTotalMax = computed(() => maxRows(props.summary.ventasPorCategoria));
const cashierTotalMax = computed(() => maxRows(props.summary.ventasPorCajero));
const documentTotal = computed(() => sumRows(props.summary.ventasPorDocumento));
const periodLabel = computed(() => `${form.date_from || t('reports.range_start')} - ${form.date_to || t('reports.range_current')}`);
const profitTone = computed(() => Number(props.summary.utilidadBruta || 0) >= 0 ? 'is-positive' : 'is-negative');

function money(value) {
  return Number(value || 0).toFixed(2);
}

function sumRows(rows = [], key = 'total') {
  return rows.reduce((total, row) => total + Number(row?.[key] || 0), 0);
}

function maxRows(rows = [], key = 'total') {
  return Math.max(...rows.map((row) => Number(row?.[key] || 0)), 1);
}

function ratio(value, total) {
  if (!Number(total)) {
    return 0;
  }

  return Math.max(4, Math.min(100, (Number(value || 0) / Number(total)) * 100));
}

function barStyle(value, total) {
  return { '--bar-width': `${ratio(value, total)}%` };
}

function tone(index) {
  return ['is-blue', 'is-emerald', 'is-amber', 'is-violet', 'is-rose'][index % 5];
}

function paymentIcon(method) {
  return {
    cash: 'fa-money-bill-wave',
    yape: 'fa-mobile-screen-button',
    plin: 'fa-qrcode',
    card: 'fa-credit-card',
    transfer: 'fa-building-columns',
  }[method] || 'fa-wallet';
}

function documentIcon(type) {
  return {
    receipt: 'fa-receipt',
    invoice: 'fa-file-invoice',
  }[type] || 'fa-file-lines';
}

function queryParams() {
  return cleanQuery({
    date_from: form.date_from,
    date_to: form.date_to,
    cashier_id: form.cashier_id,
    payment_method: form.payment_method,
    product_id: form.product_id,
    document_type: form.document_type,
  });
}

function applyFilters() {
  router.get(route('reports.index'), queryParams(), { preserveScroll: true, preserveState: true, replace: true });
}

function clearFilters() {
  form.date_from = '';
  form.date_to = '';
  form.cashier_id = '';
  form.payment_method = '';
  form.product_id = '';
  form.document_type = '';
  router.get(route('reports.index'), {}, { preserveScroll: true, preserveState: true, replace: true });
}

function exportUrl(routeName) {
  return route(routeName, queryParams());
}
</script>

<template>
  <AuthenticatedLayout>
    <Head :title="t('reports.title')" />

    <div class="space-y-6 p-4 md:p-6">
      <PageHeader :title="t('reports.title')" :description="t('reports.description')">
        <template #actions>
          <AppButton
            class="reports-export-button reports-export-pdf"
            :href="exportUrl('reports.ventas.pdf')"
            target="_blank"
            rel="noopener noreferrer"
            variant="danger"
            icon="fa-file-pdf"
            :title="t('reports.export_pdf')"
            :aria-label="t('reports.export_pdf')"
          >
            PDF
          </AppButton>
          <AppButton
            class="reports-export-button reports-export-excel"
            :href="exportUrl('reports.ventas.excel')"
            target="_blank"
            rel="noopener noreferrer"
            variant="success"
            icon="fa-file-excel"
            :title="t('reports.export_excel')"
            :aria-label="t('reports.export_excel')"
          >
            Excel
          </AppButton>
        </template>
      </PageHeader>

      <section class="reports-hero">
        <div class="reports-hero-main">
          <span class="reports-hero-kicker">{{ t('reports.performance_snapshot') }}</span>
          <h2>S/ {{ money(summary.totalVentas) }}</h2>
          <p>{{ t('reports.period') }}: {{ periodLabel }}</p>
          <div class="reports-hero-chips">
            <span><i class="fas fa-receipt" aria-hidden="true"></i>{{ summary.totalComprobantes }} {{ t('reports.documents') }}</span>
            <span><i class="fas fa-boxes-stacked" aria-hidden="true"></i>{{ Number(summary.totalItems || 0).toFixed(0) }} {{ t('reports.units') }}</span>
            <span><i class="fas fa-percent" aria-hidden="true"></i>{{ money(summary.margenBruto) }}%</span>
          </div>
        </div>
        <div class="reports-hero-card" :class="profitTone">
          <small>{{ t('reports.gross_profit') }}</small>
          <strong>S/ {{ money(summary.utilidadBruta) }}</strong>
          <span>{{ t('reports.net_income') }} S/ {{ money(summary.ingresoNetoProductos) }}</span>
          <div class="reports-profit-meter">
            <span :style="barStyle(summary.utilidadBruta, Math.max(Number(summary.ingresoNetoProductos || 0), 1))"></span>
          </div>
        </div>
      </section>

      <form class="ihc-filter-grid" @submit.prevent="applyFilters">
        <label class="ihc-label">{{ t('reports.from') }}
          <input v-model="form.date_from" type="date" class="ihc-field" />
        </label>
        <label class="ihc-label">{{ t('reports.to') }}
          <input v-model="form.date_to" type="date" class="ihc-field" />
        </label>
        <label class="ihc-label">{{ t('reports.cashier') }}
          <select v-model="form.cashier_id" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">{{ cashier.name }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('reports.method') }}
          <select v-model="form.payment_method" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ paymentLabels[method.value] || method.label }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('reports.product') }}
          <select v-model="form.product_id" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }}{{ product.barcode ? ` · ${product.barcode}` : '' }}
            </option>
          </select>
        </label>
        <label class="ihc-label">{{ t('reports.document') }}
          <select v-model="form.document_type" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="type in documentTypes" :key="type.value" :value="type.value">{{ documentLabels[type.value] || type.label }}</option>
          </select>
        </label>
        <div class="flex items-end gap-2">
          <AppButton class="flex-1" type="submit" icon="fa-filter">{{ t('actions.apply_filters') }}</AppButton>
          <AppButton type="button" variant="secondary" icon="fa-eraser" :title="t('actions.clear_filters')" :aria-label="t('actions.clear_filters')" @click="clearFilters">
            {{ t('actions.clear_filters') }}
          </AppButton>
        </div>
      </form>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :title="t('reports.income')" :value="`S/ ${money(summary.totalVentas)}`" icon="fa-chart-line" variant="info" />
        <StatCard :title="t('reports.sold_cost')" :value="`S/ ${money(summary.totalCosto)}`" icon="fa-box" />
        <StatCard :title="t('reports.gross_profit')" :value="`S/ ${money(summary.utilidadBruta)}`" icon="fa-chart-line" variant="success" />
        <StatCard :title="t('reports.gross_margin')" :value="`${money(summary.margenBruto)}%`" icon="fa-percent" variant="info" />
        <StatCard :title="t('reports.discounts')" :value="`S/ ${money(summary.totalDescuentos)}`" icon="fa-tag" variant="warning" />
        <StatCard :title="t('reports.documents')" :value="summary.totalComprobantes" icon="fa-receipt" variant="info" />
        <StatCard :title="t('reports.sold_units')" :value="Number(summary.totalItems || 0).toFixed(0)" icon="fa-boxes" variant="success" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="reports-panel">
          <div class="reports-panel-header">
            <div>
              <span>{{ t('reports.collection') }}</span>
              <h2>{{ t('reports.payments_by_method') }}</h2>
            </div>
            <strong>S/ {{ money(paymentTotal) }}</strong>
          </div>
          <div v-if="summary.ventasPorMetodo?.length" class="reports-meter-list">
            <div v-for="(payment, index) in summary.ventasPorMetodo" :key="payment.method" class="reports-meter-row">
              <div class="reports-meter-head">
                <span><i class="fas" :class="[paymentIcon(payment.method), tone(index)]" aria-hidden="true"></i>{{ paymentLabels[payment.method] || payment.label }}</span>
                <strong>S/ {{ money(payment.total) }}</strong>
              </div>
              <div class="reports-meter-meta">
                <span>{{ payment.count }} {{ t('reports.operations') }}</span>
                <span>{{ money(ratio(payment.total, paymentTotal)) }}%</span>
              </div>
              <div class="reports-meter-track">
                <span class="reports-meter-fill" :class="tone(index)" :style="barStyle(payment.total, paymentTotal)"></span>
              </div>
            </div>
          </div>
          <p v-else class="app-ui-help">{{ t('reports.no_payments') }}</p>
        </section>

        <section class="reports-panel">
          <div class="reports-panel-header">
            <div>
              <span>{{ t('reports.ranking') }}</span>
              <h2>{{ t('reports.sales_by_product') }}</h2>
            </div>
            <i class="fas fa-box-open" aria-hidden="true"></i>
          </div>
          <div v-if="summary.ventasPorProducto?.length" class="reports-meter-list">
            <div v-for="(product, index) in summary.ventasPorProducto" :key="product.name" class="reports-meter-row">
              <div class="reports-meter-head">
                <span><i class="fas fa-box" :class="tone(index)" aria-hidden="true"></i>{{ product.name }}</span>
                <strong>S/ {{ money(product.gross_profit) }}</strong>
              </div>
              <div class="reports-meter-meta">
                <span>{{ Number(product.quantity).toFixed(0) }} {{ t('reports.units') }} · S/ {{ money(product.total) }}</span>
                <span>{{ money(product.margin) }}%</span>
              </div>
              <div class="reports-meter-track">
                <span class="reports-meter-fill" :class="tone(index)" :style="barStyle(product.gross_profit, productProfitMax)"></span>
              </div>
            </div>
          </div>
          <p v-else class="app-ui-help">{{ t('reports.no_products') }}</p>
        </section>

        <section class="reports-panel">
          <div class="reports-panel-header">
            <div>
              <span>{{ t('reports.mix') }}</span>
              <h2>{{ t('reports.sales_by_category') }}</h2>
            </div>
            <i class="fas fa-layer-group" aria-hidden="true"></i>
          </div>
          <div v-if="summary.ventasPorCategoria?.length" class="reports-meter-list">
            <div v-for="(category, index) in summary.ventasPorCategoria" :key="category.name" class="reports-meter-row">
              <div class="reports-meter-head">
                <span><i class="fas fa-tags" :class="tone(index)" aria-hidden="true"></i>{{ category.name }}</span>
                <strong>S/ {{ money(category.total) }}</strong>
              </div>
              <div class="reports-meter-meta">
                <span>{{ Number(category.quantity || 0).toFixed(0) }} {{ t('reports.units') }}</span>
                <span>S/ {{ money(category.gross_profit) }} · {{ money(category.margin) }}%</span>
              </div>
              <div class="reports-meter-track">
                <span class="reports-meter-fill" :class="tone(index)" :style="barStyle(category.total, categoryTotalMax)"></span>
              </div>
            </div>
          </div>
          <p v-else class="app-ui-help">{{ t('reports.no_categories') }}</p>
        </section>

        <section class="reports-panel">
          <div class="reports-panel-header">
            <div>
              <span>{{ t('reports.team') }}</span>
              <h2>{{ t('reports.sales_by_cashier') }}</h2>
            </div>
            <i class="fas fa-user-tie" aria-hidden="true"></i>
          </div>
          <div v-if="summary.ventasPorCajero?.length" class="reports-meter-list">
            <div v-for="(cashier, index) in summary.ventasPorCajero" :key="cashier.name" class="reports-meter-row">
              <div class="reports-meter-head">
                <span><i class="fas fa-user-check" :class="tone(index)" aria-hidden="true"></i>{{ cashier.name }}</span>
                <strong>S/ {{ money(cashier.total) }}</strong>
              </div>
              <div class="reports-meter-meta">
                <span>{{ cashier.count }} {{ t('reports.documents') }}</span>
                <span>{{ money(ratio(cashier.total, cashierTotalMax)) }}%</span>
              </div>
              <div class="reports-meter-track">
                <span class="reports-meter-fill" :class="tone(index)" :style="barStyle(cashier.total, cashierTotalMax)"></span>
              </div>
            </div>
          </div>
          <p v-else class="app-ui-help">{{ t('reports.no_cashiers') }}</p>
        </section>

        <section class="reports-panel lg:col-span-2">
          <div class="reports-panel-header">
            <div>
              <span>{{ t('reports.documents') }}</span>
              <h2>{{ t('reports.sales_by_document') }}</h2>
            </div>
            <strong>{{ summary.totalComprobantes }}</strong>
          </div>
          <div v-if="summary.ventasPorDocumento?.length" class="reports-document-grid">
            <div v-for="(document, index) in summary.ventasPorDocumento" :key="document.document_type" class="reports-document-card">
              <i class="fas" :class="[documentIcon(document.document_type), tone(index)]" aria-hidden="true"></i>
              <span>{{ documentLabels[document.document_type] || document.label }}</span>
              <strong>S/ {{ money(document.total) }}</strong>
              <small>{{ document.count }} {{ t('reports.documents') }} · {{ money(ratio(document.total, documentTotal)) }}%</small>
            </div>
          </div>
          <p v-else class="app-ui-help">{{ t('reports.no_documents') }}</p>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

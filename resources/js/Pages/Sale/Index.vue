<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { cleanQuery, formatDatetime, getCurrency, numberFormat } from '@/Utils/Helper.js';

const props = defineProps({
  filters: Object,
  sales: Object,
  cashiers: Array,
  documentTypes: Array,
  paymentMethods: Array,
  saleStatuses: Array,
});

const page = usePage();
const printingId = ref(null);
const printMessage = ref('');
const { t } = useI18n();

const form = useForm({
  full_document_number: props.filters?.full_document_number || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  cashier_id: props.filters?.cashier_id || '',
  document_type: props.filters?.document_type || '',
  payment_method: props.filters?.payment_method || '',
  status: props.filters?.status || '',
  amount_min: props.filters?.amount_min || '',
  amount_max: props.filters?.amount_max || '',
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
const statusLabels = {
  completed: t('sales.completed'),
  cancelled: t('sales.cancelled'),
  pending: t('sales.pending'),
};

function queryParams() {
  return cleanQuery(form.data());
}

function applyFilters() {
  router.get(route('sales.index'), queryParams(), { preserveScroll: true, preserveState: true, replace: true });
}

function clearFilters() {
  Object.keys(form.data()).forEach((key) => { form[key] = ''; });
  router.get(route('sales.index'), {}, { preserveScroll: true, preserveState: true, replace: true });
}

async function requestPrint(sale) {
  if (printingId.value) return;
  printingId.value = sale.id;
  printMessage.value = '';
  try {
    await fetch(route('sales.print-request', sale.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        Accept: 'application/json',
      },
    });
    window.open(route('sales.thermal', sale.id), '_blank', 'noopener,noreferrer,width=420,height=720');
    printMessage.value = t('sales.print_request_success', { document: sale.full_document_number });
  } catch (error) {
    printMessage.value = t('sales.print_request_error');
  } finally {
    printingId.value = null;
  }
}

function paymentsText(sale) {
  return (sale.payments || [])
    .map((payment) => paymentLabels[payment.payment_method] || payment.payment_method)
    .join(' + ') || '-';
}
</script>

<template>
  <Head :title="t('sales.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ page.props.auth.user.role === 'admin' ? t('sales.history_title') : t('sales.my_sales') }}</template>

    <div class="min-w-0 max-w-full space-y-5 overflow-x-hidden">
      <PageHeader
        :title="page.props.auth.user.role === 'admin' ? t('sales.history_title') : t('sales.my_sales')"
        :description="t('sales.readonly_description')"
        :count="sales.total"
      >
        <template #actions>
          <AppButton :href="route('carts.index')" icon="fa-cash-register">{{ t('sales.new_sale') }}</AppButton>
        </template>
      </PageHeader>

      <form class="ihc-filter-grid" :aria-label="t('sales.filters_label')" @submit.prevent="applyFilters">
        <label class="ihc-label">{{ t('sales.correlative') }}
          <input v-model="form.full_document_number" class="ihc-field" placeholder="B001-00000001" />
        </label>
        <label class="ihc-label">{{ t('reports.from') }}
          <input v-model="form.date_from" type="date" class="ihc-field" />
        </label>
        <label class="ihc-label">{{ t('reports.to') }}
          <input v-model="form.date_to" type="date" class="ihc-field" />
        </label>
        <label v-if="page.props.auth.user.role === 'admin'" class="ihc-label">{{ t('common.cashier') }}
          <select v-model="form.cashier_id" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">{{ cashier.name }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('sales.type') }}
          <select v-model="form.document_type" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="type in documentTypes" :key="type.value" :value="type.value">{{ documentLabels[type.value] || type.label }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('sales.method') }}
          <select v-model="form.payment_method" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ paymentLabels[method.value] || method.label }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('common.status') }}
          <select v-model="form.status" class="ihc-field">
            <option value="">{{ t('sales.all') }}</option>
            <option v-for="status in saleStatuses" :key="status.value" :value="status.value">{{ statusLabels[status.value] || status.label }}</option>
          </select>
        </label>
        <label class="ihc-label">{{ t('sales.min') }}
          <input v-model="form.amount_min" type="number" min="0" step="0.01" class="ihc-field" />
          <InputError :message="form.errors.amount_min" />
        </label>
        <label class="ihc-label">{{ t('sales.max') }}
          <input v-model="form.amount_max" type="number" min="0" step="0.01" class="ihc-field" />
          <InputError :message="form.errors.amount_max" />
        </label>
        <div class="flex items-end gap-2">
          <AppButton class="flex-1" type="submit" icon="fa-filter">{{ t('actions.apply_filters') }}</AppButton>
          <AppButton type="button" variant="secondary" icon="fa-eraser" :aria-label="t('actions.clear_filters')" :title="t('actions.clear_filters')" @click="clearFilters">
            {{ t('actions.clear_filters') }}
          </AppButton>
        </div>
      </form>

      <p v-if="printMessage" class="rounded-md bg-sky-50 px-4 py-2 text-sm font-bold text-sky-800">{{ printMessage }}</p>

      <section class="ihc-panel overflow-hidden">
        <div class="hidden overflow-x-auto lg:block">
          <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase">
              <tr>
                <th class="px-4 py-3">{{ t('common.document') }}</th>
                <th>{{ t('sales.date_time') }}</th>
                <th>{{ t('common.cashier') }}</th>
                <th>{{ t('sales.type') }}</th>
                <th>{{ t('sales.payment') }}</th>
                <th class="text-right">{{ t('common.total') }}</th>
                <th>{{ t('common.status') }}</th>
                <th class="text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sale in sales.data" :key="sale.id">
                <td class="px-4 py-3">
                  <strong class="block text-[var(--color-text-primary)]">{{ sale.full_document_number }}</strong>
                  <span class="ihc-help">Caja #{{ sale.cash_register_id }}</span>
                </td>
                <td>{{ formatDatetime(sale.sold_at) }}</td>
                <td>{{ sale.cashier?.name || '-' }}</td>
                <td>{{ documentLabels[sale.document_type] || sale.document_type }}</td>
                <td>{{ paymentsText(sale) }}</td>
                <td class="text-right font-black">{{ getCurrency() }}{{ numberFormat(Number(sale.total)) }}</td>
                <td>
                  <AppBadge :variant="sale.status === 'completed' ? 'success' : sale.status === 'cancelled' ? 'danger' : 'info'" icon="fa-circle-check">
                    {{ statusLabels[sale.status] || sale.status }}
                  </AppBadge>
                </td>
                <td class="text-right">
                  <details class="relative inline-block max-w-full text-left">
                    <summary class="app-action-trigger" :aria-label="t('sales.open_actions')">
                      {{ t('common.actions') }} <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </summary>
                    <div class="app-action-menu">
                      <Link :href="route('sales.show', sale.id)" class="app-action-item is-view"><i class="fas fa-eye"></i>{{ t('sales.view_detail') }}</Link>
                      <a :href="route('sales.thermal', sale.id)" target="_blank" rel="noopener noreferrer" class="app-action-item is-print"><i class="fas fa-print"></i>{{ t('sales.open_thermal') }}</a>
                      <a :href="route('sales.pdf', sale.id)" target="_blank" rel="noopener noreferrer" class="app-action-item is-pdf"><i class="fas fa-file-pdf"></i>{{ t('sales.download_pdf') }}</a>
                      <button type="button" class="app-action-item is-success" :disabled="printingId === sale.id" @click="requestPrint(sale)">
                        <i class="fas fa-rotate-right"></i>{{ t('sales.request_reprint') }}
                      </button>
                      <Link :href="route('cash-registers.index', { cash_register_id: sale.cash_register_id })" class="app-action-item is-register"><i class="fas fa-lock-open"></i>{{ t('sales.consult_register') }}</Link>
                      <Link :href="route('sales.show', sale.id) + '#impresiones'" class="app-action-item is-history"><i class="fas fa-history"></i>{{ t('sales.print_history') }}</Link>
                    </div>
                  </details>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-[var(--color-border)] lg:hidden">
          <article v-for="sale in sales.data" :key="sale.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 class="font-black text-[var(--color-text-primary)]">{{ sale.full_document_number }}</h2>
                <p class="ihc-help">{{ formatDatetime(sale.sold_at) }} · {{ documentLabels[sale.document_type] }}</p>
              </div>
              <strong>{{ getCurrency() }}{{ numberFormat(Number(sale.total)) }}</strong>
            </div>
            <p class="mt-2 text-sm"><strong>{{ t('sales.payment') }}:</strong> {{ paymentsText(sale) }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton :href="route('sales.show', sale.id)" size="sm" icon="fa-eye">{{ t('sales.detail') }}</AppButton>
              <AppButton class="doc-button is-thermal" :href="route('sales.thermal', sale.id)" target="_blank" rel="noopener noreferrer" variant="secondary" size="sm" icon="fa-print">{{ t('cash.thermal') }}</AppButton>
              <AppButton class="doc-button is-pdf" :href="route('sales.pdf', sale.id)" target="_blank" rel="noopener noreferrer" variant="secondary" size="sm" icon="fa-file-pdf">PDF</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState
          v-if="!sales.data.length"
          :title="t('sales.no_sales_found')"
          :description="t('sales.no_sales_description')"
          icon="fa-receipt"
        />
      </section>

      <AppPagination :links="sales.links" :label="t('sales.pagination')" />
    </div>
  </AuthenticatedLayout>
</template>

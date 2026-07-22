<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Button.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { useShortcutPlatform } from '@/Composables/useShortcutPlatform.js';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { formatDatetime, getCurrency, numberFormat } from '@/Utils/Helper.js';

const props = defineProps({
  sale: Object,
});

const page = usePage();
const { t } = useI18n();
const { formatShortcut, isPrimaryShortcut } = useShortcutPlatform();
const printing = ref(false);
const printMessage = ref('');
const hasAutoOpened = ref(false);

const role = computed(() => page.props.auth.user.role);
const isAdmin = computed(() => role.value === 'admin');
const businessSettings = computed(() => page.props.businessSettings || {});

const documentLabels = {
  receipt: t('states.receipt'),
  invoice: t('states.invoice'),
};

const issueLabels = {
  internal: t('sales.internal'),
  pending: t('sales.pending'),
  accepted: t('sales.accepted'),
  rejected: t('sales.rejected'),
  cancelled: t('sales.cancelled'),
};

const statusLabels = {
  completed: t('sales.completed'),
  cancelled: t('sales.cancelled'),
  pending: t('sales.pending'),
};

const paymentLabels = {
  cash: t('states.cash'),
  yape: t('states.yape'),
  plin: t('states.plin'),
  card: t('states.card'),
  transfer: t('states.transfer'),
};

const paymentsSummary = computed(() => props.sale.payments.map((payment) => paymentLabels[payment.payment_method] || payment.payment_method).join(' + '));
const cashPayment = computed(() => props.sale.payments.find((payment) => payment.payment_method === 'cash'));
const printLogs = computed(() => props.sale.document_print_logs || []);
const shortcutHelp = computed(() => t('sales.shortcuts_help', { print: formatShortcut('P') }));

function money(value) {
  return `${getCurrency()}${numberFormat(Number(value || 0))}`;
}

async function printThermal(auto = false) {
  if (printing.value) return;
  printing.value = true;
  printMessage.value = t('sales.print_registering');
  try {
    await fetch(route('sales.print-request', props.sale.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        Accept: 'application/json',
      },
    });

    const popup = window.open(route('sales.thermal', { sale: props.sale.id, autoprint: 1 }), '_blank', 'noopener,noreferrer,width=420,height=720');
    printMessage.value = popup
      ? t('sales.print_dialog_requested')
      : t('sales.popup_blocked');

    if (popup && auto && businessSettings.value.return_to_pos_after_print) {
      window.location.href = route('carts.index');
    }
  } catch (error) {
    printMessage.value = t('sales.print_error');
  } finally {
    printing.value = false;
  }
}

function newSale() {
  window.location.href = `${route('carts.index')}?focus=search`;
}

function handleShortcut(event) {
  const tag = event.target?.tagName?.toLowerCase();
  if (['input', 'textarea', 'select'].includes(tag)) return;

  if (isPrimaryShortcut(event, 'p')) {
    event.preventDefault();
    printThermal(false);
  }

  if (event.key === 'F8') {
    event.preventDefault();
    newSale();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleShortcut);
  if (!hasAutoOpened.value && businessSettings.value.auto_open_print_dialog) {
    hasAutoOpened.value = true;
    printThermal(true);
  }
});

onUnmounted(() => window.removeEventListener('keydown', handleShortcut));
</script>

<template>
  <Head :title="sale.full_document_number" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('sales.title') }} / {{ sale.full_document_number }}</template>

    <div class="mx-auto max-w-6xl space-y-5">
      <section class="ihc-panel p-5">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-start md:justify-between">
          <div>
            <p class="text-sm font-bold text-emerald-700">{{ t('sales.registered') }}</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900">{{ sale.full_document_number }}</h1>
            <p class="ihc-help">{{ documentLabels[sale.document_type] }} · {{ issueLabels[sale.issue_status] }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button :disabled="printing" @click="printThermal(false)">
              <i class="fas" :class="printing ? 'fa-spinner fa-spin' : 'fa-print'"></i>{{ t('actions.print') }}
            </Button>
            <a :href="route('sales.pdf', sale.id)" target="_blank" rel="noopener noreferrer" class="app-button doc-button is-pdf mr-1"><i class="fas fa-file-pdf"></i>PDF</a>
            <Button :href="route('sales.index')" buttonType="link" type="gray"><i class="fas fa-list"></i>{{ t('sales.history_title') }}</Button>
            <button type="button" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white" @click="newSale">
              <i class="fas fa-plus"></i>{{ t('sales.new_sale') }}
            </button>
          </div>
        </div>

        <p v-if="printMessage" class="mt-3 rounded-md bg-sky-50 px-4 py-2 text-sm font-bold text-sky-800">{{ printMessage }}</p>
        <p class="mt-3 ihc-help">{{ shortcutHelp }}</p>
      </section>

      <section class="ihc-card-grid">
        <div class="ihc-panel p-4">
          <p class="ihc-label">{{ t('common.document') }}</p>
          <strong class="mt-1 block text-lg">{{ sale.full_document_number }}</strong>
          <span class="ihc-help">{{ documentLabels[sale.document_type] }}</span>
        </div>
        <div class="ihc-panel p-4">
          <p class="ihc-label">{{ t('common.date') }}</p>
          <strong class="mt-1 block text-lg">{{ formatDatetime(sale.sold_at) }}</strong>
        </div>
        <div class="ihc-panel p-4">
          <p class="ihc-label">{{ t('common.total') }}</p>
          <strong class="mt-1 block text-2xl text-emerald-700">{{ money(sale.total) }}</strong>
        </div>
        <div class="ihc-panel p-4">
          <p class="ihc-label">{{ t('sales.payment') }}</p>
          <strong class="mt-1 block text-lg">{{ paymentsSummary }}</strong>
        </div>
      </section>

      <div class="grid gap-5 lg:grid-cols-2">
        <section class="ihc-panel p-4">
          <h2 class="ihc-section-title">{{ t('sales.register') }}</h2>
          <dl class="mt-3 space-y-2 text-sm">
            <div><dt class="ihc-label">{{ t('sales.register') }}</dt><dd class="font-bold">#{{ sale.cash_register_id }}</dd></div>
            <div><dt class="ihc-label">{{ t('common.cashier') }}</dt><dd>{{ sale.cashier?.name || '-' }}</dd></div>
            <div v-if="sale.cash_register?.opened_at"><dt class="ihc-label">{{ t('states.open') }}</dt><dd>{{ formatDatetime(sale.cash_register.opened_at) }}</dd></div>
          </dl>
          <Link :href="route('cash-registers.index')" class="mt-3 inline-flex text-sm font-bold text-emerald-700">{{ t('sales.consult_register') }}</Link>
        </section>

        <section class="ihc-panel p-4">
          <h2 class="ihc-section-title">{{ t('common.document') }}</h2>
          <dl class="mt-3 space-y-2 text-sm">
            <div><dt class="ihc-label">{{ t('sales.operational_status') }}</dt><dd>{{ statusLabels[sale.status] || sale.status }}</dd></div>
            <div><dt class="ihc-label">{{ t('sales.issue_status') }}</dt><dd>{{ issueLabels[sale.issue_status] || sale.issue_status }}</dd></div>
            <div><dt class="ihc-label">{{ t('sales.series') }}</dt><dd>{{ sale.document_series }} · {{ sale.document_number }}</dd></div>
          </dl>
        </section>
      </div>

      <section class="ihc-panel overflow-hidden">
        <div class="border-b border-slate-200 p-4">
          <h2 class="ihc-section-title">{{ t('products.title') }}</h2>
          <p class="ihc-help">{{ t('sales.historical_cost_help') }}</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3">{{ t('products.table_product') }}</th>
                <th class="text-right">{{ t('common.quantity') }}</th>
                <th class="text-right">{{ t('sales.unit_price') }}</th>
                <th class="text-right">{{ t('reports.discounts') }}</th>
                <th v-if="isAdmin" class="text-right">{{ t('sales.historical_cost') }}</th>
                <th v-if="isAdmin" class="text-right">{{ t('sales.gross_profit') }}</th>
                <th class="text-right">{{ t('sales.amount') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in sale.items" :key="item.id">
                <td class="px-4 py-3">
                  <strong>{{ item.product_name_snapshot }}</strong>
                  <small class="block text-slate-500">{{ item.product_code_snapshot || '-' }}</small>
                </td>
                <td class="text-right">{{ numberFormat(Number(item.quantity)) }}</td>
                <td class="text-right">{{ money(item.unit_price) }}</td>
                <td class="text-right text-red-600">{{ money(item.discount) }}</td>
                <td v-if="isAdmin" class="text-right">{{ money(item.unit_cost) }}</td>
                <td v-if="isAdmin" class="text-right font-bold text-emerald-700">{{ money(item.gross_profit) }}</td>
                <td class="text-right font-bold">{{ money(item.subtotal) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <section class="ihc-panel p-4">
          <h2 class="ihc-section-title">{{ t('sales.payments') }}</h2>
          <div class="mt-3 space-y-3">
            <div v-for="payment in sale.payments" :key="payment.id" class="rounded-md border border-slate-200 p-3 text-sm">
              <div class="flex justify-between gap-3">
                <strong>{{ paymentLabels[payment.payment_method] || payment.payment_method }}</strong>
                <strong>{{ money(payment.amount) }}</strong>
              </div>
              <p v-if="payment.received_amount" class="ihc-help">{{ t('sales.received') }}: {{ money(payment.received_amount) }} · {{ t('sales.change') }}: {{ money(payment.change_amount) }}</p>
              <p v-if="payment.operation_number" class="ihc-help">{{ t('sales.operation') }}: {{ payment.operation_number }}</p>
              <p v-if="payment.bank_name" class="ihc-help">{{ t('sales.bank') }}: {{ payment.bank_name }}</p>
              <p v-if="payment.notes" class="ihc-help">{{ t('sales.note') }}: {{ payment.notes }}</p>
            </div>
          </div>
        </section>

        <section class="ihc-panel p-4">
          <h2 class="ihc-section-title">{{ t('sales.totals') }}</h2>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-bold">{{ money(sale.subtotal) }}</dd></div>
            <div class="flex justify-between"><dt>{{ t('reports.discounts') }}</dt><dd class="font-bold text-red-600">- {{ money(sale.discount_total) }}</dd></div>
            <div class="flex justify-between"><dt>IGV</dt><dd class="font-bold">{{ money(sale.igv) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-lg"><dt class="font-black">Total</dt><dd class="font-black text-emerald-700">{{ money(sale.total) }}</dd></div>
            <div v-if="cashPayment" class="flex justify-between"><dt>{{ t('sales.received') }}</dt><dd class="font-bold">{{ money(cashPayment.received_amount) }}</dd></div>
            <div v-if="cashPayment" class="flex justify-between"><dt>{{ t('sales.change') }}</dt><dd class="font-bold">{{ money(cashPayment.change_amount) }}</dd></div>
          </dl>
        </section>
      </div>

      <section id="impresiones" class="ihc-panel p-4">
        <h2 class="ihc-section-title">{{ t('sales.prints_audit') }}</h2>
        <p class="ihc-help">{{ t('sales.print_audit_help') }}</p>
        <div v-if="printLogs.length" class="mt-3 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500">
              <tr><th class="px-3 py-2">{{ t('common.date') }}</th><th>{{ t('common.user') }}</th><th>{{ t('sales.output') }}</th><th>{{ t('common.actions') }}</th><th>{{ t('sales.print_type') }}</th></tr>
            </thead>
            <tbody>
              <tr v-for="log in printLogs" :key="log.id">
                <td class="px-3 py-2">{{ formatDatetime(log.requested_at) }}</td>
                <td>{{ log.user?.name || '-' }}</td>
                <td>{{ log.output_type }}</td>
                <td>{{ log.action_type }}</td>
                <td>{{ log.is_reprint ? t('sales.reprint') : t('sales.original_download') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="mt-3 ihc-help">{{ t('sales.no_prints') }}</p>
      </section>

      <p class="rounded-md bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
        {{ t('sales.external_tax_notice') }}
      </p>
    </div>
  </AuthenticatedLayout>
</template>

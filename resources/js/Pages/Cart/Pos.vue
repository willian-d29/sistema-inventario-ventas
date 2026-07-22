<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { getCurrency, numberFormat, showToast } from '@/Utils/Helper.js';

const props = defineProps({
  products: Object,
  carts: Object,
  cartSubtotal: Number,
  discountType: String,
  discount: Number,
  totalDiscount: Number,
  tax: Number,
  totalTax: Number,
  total: Number,
  paymentMethods: Array,
  cashRegister: Object,
});

const page = usePage();
const { t } = useI18n();
const searchInput = ref(null);
const productQuery = ref(new URLSearchParams(window.location.search).get('keyword') || '');
const showPaymentModal = ref(false);
const showCloseRegisterModal = ref(false);
const showCloseConfirmation = ref(false);
const showShortcutHelp = ref(false);
const searchSubmitting = ref(false);
const defaultDocumentType = computed(() => {
  const configured = page.props.businessSettings?.default_sale_document;

  return ['receipt', 'invoice'].includes(configured) ? configured : 'receipt';
});

const form = useForm({
  document_type: defaultDocumentType.value,
  custom_discount: { discount: 0, discount_type: 'fixed' },
  payments: [{ method: 'cash', amount: 0, received_amount: 0, operation_number: null, bank_name: null, notes: null }],
});
const openForm = useForm({ opening_amount: 0, notes: null });
const closeForm = useForm({
  declared_amounts: { cash: 0, yape: 0, plin: 0, card: 0, transfer: 0 },
  closing_notes: null,
  confirmed: false,
});

const paymentIcons = {
  cash: 'fas fa-money-bill-wave',
  card: 'fas fa-credit-card',
  transfer: 'fas fa-university',
};
const paymentLogos = {
  yape: '/assets/payment/yape.svg',
  plin: '/assets/payment/plin.png',
};
const paymentLabels = computed(() => ({
  cash: t('states.cash'),
  yape: t('states.yape'),
  plin: t('states.plin'),
  card: t('states.card'),
  transfer: t('states.transfer'),
}));
const documentTypes = computed(() => [
  { value: 'receipt', label: t('states.receipt'), description: t('pos.receipt_description') },
  { value: 'invoice', label: t('states.invoice'), description: t('pos.invoice_description') },
]);

const customDiscountAmount = computed(() => {
  const value = Math.max(Number(form.custom_discount.discount) || 0, 0);
  return form.custom_discount.discount_type === 'percentage'
    ? Number(props.cartSubtotal || 0) * value / 100
    : value;
});
const saleTotal = computed(() => Math.max(numberFormat(Number(props.total || 0) - customDiscountAmount.value), 0));
const paymentsTotal = computed(() => numberFormat(form.payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0)));
const remaining = computed(() => numberFormat(saleTotal.value - paymentsTotal.value));
const canOpenPayment = computed(() => Boolean(props.cashRegister && props.carts.total && saleTotal.value > 0));

watch(saleTotal, (total) => {
  if (form.payments.length === 1) {
    form.payments[0].amount = total;
    if (form.payments[0].method === 'cash') form.payments[0].received_amount = total;
  }
}, { immediate: true });

onMounted(() => {
  nextTick(() => searchInput.value?.focus());
  window.addEventListener('keydown', handleGlobalShortcut);
});

onUnmounted(() => window.removeEventListener('keydown', handleGlobalShortcut));

function isTextField(target) {
  return ['input', 'textarea', 'select'].includes(target?.tagName?.toLowerCase()) || target?.isContentEditable;
}

function handleGlobalShortcut(event) {
  if (event.key === 'F2') {
    event.preventDefault();
    searchInput.value?.focus();
    searchInput.value?.select?.();
    return;
  }

  if (isTextField(event.target)) return;

  if (event.key === 'F4') {
    event.preventDefault();
    openPayment();
  }

  if (event.key === 'Escape' && showPaymentModal.value) {
    showPaymentModal.value = false;
  }
}

function submitProductQuery() {
  const code = productQuery.value.trim();
  if (!code) return;
  searchSubmitting.value = true;
  router.post(route('carts.scan'), { code }, {
    preserveScroll: true,
    onSuccess: (page) => {
      if (!page.url.includes('keyword=')) productQuery.value = '';
      if (page.props.flash?.message) showToast();
      nextTick(() => searchInput.value?.focus());
    },
    onFinish: () => { searchSubmitting.value = false; },
  });
}

function clearSearch() {
  productQuery.value = '';
  router.get(route('carts.index'), {}, { preserveState: true, replace: true });
}

function addToCart(product) {
  router.post(route('carts.store', product.id), {}, { preserveScroll: true, onSuccess: showToast });
}

function changeQuantity(cart, action) {
  router.put(route(`carts.${action}`, cart.id), {}, { preserveScroll: true, onSuccess: showToast });
}

function updateQuantity(cart, quantity) {
  if (Number(quantity) === Number(cart.quantity)) return;
  router.put(route('carts.update', cart.id), { quantity }, { preserveScroll: true, onSuccess: showToast });
}

function removeCart(cart) {
  router.delete(route('carts.delete', cart.id), { preserveScroll: true, onSuccess: showToast });
}

function clearCart() {
  router.delete(route('carts.delete.all'), { preserveScroll: true, onSuccess: showToast });
}

function openPayment() {
  if (!canOpenPayment.value) return;
  form.clearErrors();
  showPaymentModal.value = true;
}

function addPayment() {
  if (form.payments.length >= 2) return;
  const secondAmount = numberFormat(saleTotal.value / 2);
  form.payments[0].amount = numberFormat(saleTotal.value - secondAmount);
  if (form.payments[0].method === 'cash') form.payments[0].received_amount = form.payments[0].amount;
  form.payments.push({ method: 'yape', amount: secondAmount, received_amount: null, operation_number: null, bank_name: null, notes: null });
}

function removePayment(index) {
  form.payments.splice(index, 1);
  form.payments[0].amount = saleTotal.value;
  if (form.payments[0].method === 'cash') form.payments[0].received_amount = saleTotal.value;
}

function selectPaymentMethod(payment, method) {
  payment.method = method;
  payment.operation_number = null;
  payment.bank_name = null;
  payment.notes = null;
  payment.received_amount = method === 'cash' ? payment.amount : null;
}

function selectDocumentType(type) {
  form.document_type = type;
}

function createSale() {
  if (form.processing || !canOpenPayment.value || Math.abs(remaining.value) > 0.01) return;
  form.post(route('sales.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showPaymentModal.value = false;
      showToast();
      form.reset();
      form.document_type = defaultDocumentType.value;
    },
  });
}

function openRegister() {
  openForm.post(route('cash-registers.open'), { preserveScroll: true, onSuccess: showToast });
}

function closeRegister() {
  closeForm.clearErrors();
  closeForm.confirmed = false;
  showCloseRegisterModal.value = true;
}

function submitCloseRegister() {
  if (!closeForm.confirmed) {
    showCloseConfirmation.value = true;
    return;
  }

  closeForm.put(route('cash-registers.close', props.cashRegister.id), {
    preserveScroll: true,
    onSuccess: () => { showCloseRegisterModal.value = false; showToast(); },
  });
}

function confirmCloseRegister() {
  closeForm.confirmed = true;
  showCloseConfirmation.value = false;
  submitCloseRegister();
}

function paymentMethodLabel(method) {
  return paymentLabels.value[method.value] || method.label;
}

function paymentLogo(method) {
  return paymentLogos[method.value] || null;
}

function productStockClass(product) {
  const quantity = Number(product.quantity || 0);
  if (quantity <= 0) return 'is-danger';
  if (quantity < 10) return 'is-warning';

  return 'is-success';
}
</script>

<template>
  <Head :title="t('pos.title')" />
  <AuthenticatedLayout workspace>
    <template #breadcrumb>{{ t('pos.title') }}</template>

    <div class="h-full min-h-0">
      <div class="pos-shell">
        <header class="pos-toolbar">
          <form @submit.prevent="submitProductQuery">
            <div class="flex items-center justify-between gap-3">
              <label for="product_query" class="ihc-label">{{ t('pos.search_label') }}</label>
              <button type="button" class="text-xs font-bold text-[var(--color-success)]" :aria-expanded="showShortcutHelp" @click="showShortcutHelp = !showShortcutHelp">
                <i class="fas fa-keyboard mr-1"></i>{{ t('pos.shortcuts') }}
              </button>
            </div>
            <div class="pos-search-control">
              <i class="fas fa-barcode absolute left-3 top-3.5 text-[var(--color-text-muted)]"></i>
              <input id="product_query" ref="searchInput" v-model="productQuery" autofocus autocomplete="off"
                aria-describedby="product_query_help"
                :placeholder="t('pos.search_placeholder')" />
              <button v-if="productQuery" type="button" @click="clearSearch" class="absolute right-24 top-0.5 ihc-icon-button" :title="t('actions.clear_filters')" :aria-label="t('actions.clear_filters')"><i class="fas fa-times"></i></button>
              <button type="submit" :disabled="searchSubmitting" :title="t('pos.search_product')">
                <i class="fas shrink-0" :class="searchSubmitting ? 'fa-spinner fa-spin' : 'fa-search'"></i><span class="leading-none">{{ t('common.search') }}</span>
              </button>
            </div>
            <p id="product_query_help" class="mt-1 ihc-help">{{ t('pos.search_help') }}</p>
            <div v-if="showShortcutHelp" class="mt-2 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] px-3 py-2 text-xs font-semibold text-[var(--color-text-secondary)]">
              {{ t('pos.shortcut_help') }}
            </div>
          </form>

          <div>
            <span class="ihc-label">{{ t('pos.register') }}</span>
            <div v-if="cashRegister" class="mt-1 flex h-11 items-center justify-between rounded-md border border-[color-mix(in_srgb,var(--color-success)_28%,transparent)] bg-[var(--color-success-soft)] px-3">
              <span class="font-semibold text-[var(--color-success)]"><i class="fas fa-lock-open mr-2"></i>{{ t('pos.open') }} · {{ getCurrency() }}{{ cashRegister.opening_amount }}</span>
              <button @click="closeRegister" class="ihc-icon-button h-9 w-9 text-[var(--color-danger)]" :title="t('pos.close_register')" :aria-label="t('pos.close_register')"><i class="fas fa-lock"></i></button>
            </div>
            <form v-else class="mt-1 flex" @submit.prevent="openRegister">
              <input v-model="openForm.opening_amount" type="number" min="0" step="0.01" class="h-11 w-full rounded-l-md border-[var(--color-border)]" :placeholder="t('pos.opening_float')" />
              <button class="w-12 rounded-r-md bg-[var(--color-warning)] text-white hover:opacity-90" :title="t('pos.open_register')" :aria-label="t('pos.open_register')"><i class="fas fa-lock-open"></i></button>
            </form>
            <InputError :message="openForm.errors.opening_amount" />
          </div>
        </header>

        <div class="pos-workspace">
          <section class="pos-products-pane">
            <div class="shrink-0 flex items-center justify-between px-1 pb-2">
              <div>
                <h2 class="font-bold text-[var(--color-text-primary)]">{{ t('products.title') }}</h2>
                <p class="text-xs text-[var(--color-text-muted)]">{{ t('pos.results', { count: products.total }) }}</p>
              </div>
              <span v-if="productQuery" class="app-ui-badge app-ui-badge-info app-ui-badge-pill app-ui-badge-sm">“{{ productQuery }}”</span>
            </div>

            <div v-if="!products.data.length" class="flex-1 flex flex-col items-center justify-center text-center text-[var(--color-text-muted)]">
              <i class="fas fa-search text-2xl mb-2"></i>
              <span class="font-bold">{{ t('pos.not_found') }}</span>
              <p class="mt-1 max-w-sm text-sm">{{ t('pos.not_found_help') }}</p>
            </div>
            <div v-else class="pos-scroll flex-1 min-h-0 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 content-start gap-2 overflow-y-auto pr-1">
              <button v-for="product in products.data" :key="product.id" @click="addToCart(product)" :disabled="product.quantity < 1"
                class="pos-product-card">
                <div class="pos-product-image">
                  <img :src="product.photo" :alt="product.name" />
                  <span class="pos-quantity-pill" :class="productStockClass(product)">
                    Stock {{ numberFormat(product.quantity) }}
                  </span>
                </div>
                <div class="flex flex-1 flex-col justify-between gap-2 border-t border-[var(--color-border)] px-2 py-2">
                  <div class="break-words text-xs font-semibold leading-4 text-[var(--color-text-primary)]">{{ product.name }}</div>
                  <div class="flex min-w-0 flex-wrap items-end justify-between gap-x-2 gap-y-1">
                    <strong class="shrink-0 text-sm text-[var(--color-success)]">{{ getCurrency() }}{{ numberFormat(product.selling_price) }}</strong>
                    <span class="min-w-0 max-w-full truncate text-[10px] text-[var(--color-text-muted)]">{{ product.barcode || product.product_code || product.product_number }}</span>
                  </div>
                </div>
              </button>
            </div>
          </section>

          <section class="pos-cart-pane">
            <div class="shrink-0 flex items-center justify-between border-b border-[var(--color-border)] px-4 py-3">
              <h2 class="font-bold text-[var(--color-text-primary)]">{{ t('pos.current_sale') }} <span class="app-ui-badge app-ui-badge-neutral app-ui-badge-pill app-ui-badge-sm ml-1">{{ carts.total }}</span></h2>
              <button :disabled="!carts.total" @click="clearCart" class="ihc-icon-button text-[var(--color-danger)]" :title="t('pos.clear_sale')" :aria-label="t('pos.clear_sale')"><i class="fas fa-trash-alt"></i></button>
            </div>

            <div class="pos-scroll flex-1 min-h-0 overflow-y-auto">
              <div v-if="!carts.total" class="h-full flex flex-col items-center justify-center text-[var(--color-text-muted)]">
                <i class="fas fa-shopping-basket text-3xl mb-2"></i><span class="font-medium">{{ t('pos.empty_sale') }}</span>
              </div>
              <div v-for="cart in carts.data" :key="cart.id" class="pos-cart-item">
                <div class="min-w-0"><div class="truncate font-semibold">{{ cart.product.name }}</div><small class="text-[var(--color-text-muted)]">{{ getCurrency() }}{{ numberFormat(cart.product.selling_price) }} {{ t('pos.unit') }} · {{ t('pos.stock') }} {{ numberFormat(cart.product.quantity) }}</small></div>
                <div class="flex items-center">
                  <button @click="changeQuantity(cart, 'decrement')" class="h-9 w-9 rounded-l-md bg-[var(--color-surface-alt)] hover:bg-[var(--color-primary-soft)]" :title="t('pos.decrease')" :aria-label="t('pos.decrease')"><i class="fas fa-minus text-xs"></i></button>
                  <input :value="cart.quantity" @change="updateQuantity(cart, $event.target.value)" type="number" min="1" :max="cart.product.quantity" class="h-9 w-14 border-[var(--color-border)] p-1 text-center" :aria-label="t('pos.quantity')" />
                  <button :disabled="Number(cart.quantity) >= Number(cart.product.quantity)" @click="changeQuantity(cart, 'increment')" class="h-9 w-9 rounded-r-md bg-[var(--color-surface-alt)] hover:bg-[var(--color-primary-soft)] disabled:opacity-40" :title="t('pos.increase')" :aria-label="t('pos.increase')"><i class="fas fa-plus text-xs"></i></button>
                </div>
                <div class="text-right"><strong>{{ getCurrency() }}{{ numberFormat(cart.quantity * cart.product.selling_price) }}</strong><button @click="removeCart(cart)" class="ml-auto mt-1 block text-[var(--color-danger)]" :title="t('pos.remove')" :aria-label="t('pos.remove')"><i class="fas fa-times"></i></button></div>
              </div>
            </div>

            <div class="pos-total-panel">
              <div class="grid grid-cols-2 text-sm leading-7 text-[var(--color-text-secondary)]">
                <span>Subtotal</span><strong class="text-right text-[var(--color-text-primary)]">{{ getCurrency() }}{{ numberFormat(cartSubtotal) }}</strong>
                <span>{{ t('pos.tax') }}</span><span class="text-right">{{ getCurrency() }}{{ numberFormat(totalTax) }}</span>
                <span>{{ t('reports.discounts') }}</span><span class="text-right text-[var(--color-danger)]">- {{ getCurrency() }}{{ numberFormat(totalDiscount) }}</span>
              </div>
              <div class="mt-2 flex items-center justify-between border-t border-[var(--color-border)] pt-3">
                <span class="font-bold text-lg">Total</span><strong class="text-2xl text-[var(--color-success)]">{{ getCurrency() }}{{ saleTotal }}</strong>
              </div>
              <button @click="openPayment" :disabled="!canOpenPayment || form.processing"
                class="pos-pay-button">
                <i :class="cashRegister ? 'fas fa-credit-card' : 'fas fa-lock'" class="mr-2"></i>
                {{ cashRegister ? t('pos.pay', { amount: `${getCurrency()}${saleTotal}` }) : t('pos.open_register_to_charge') }}
              </button>
              <p class="mt-2 ihc-help">{{ t('pos.sticky_total_help') }}</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <Modal
      :title="t('pos.charge_title')"
      :submitButtonText="t('pos.confirm_charge', { amount: `${getCurrency()}${saleTotal}` })"
      :show="showPaymentModal"
      :formProcessing="form.processing"
      :submitDisabled="Math.abs(remaining) > 0.01"
      @close="showPaymentModal = false"
      @submitAction="createSale"
      maxWidth="2xl"
    >
      <section class="app-premium-panel p-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-[var(--color-text-primary)]">1. {{ t('pos.summary') }}</h3>
            <p class="text-xs text-[var(--color-text-muted)]">{{ t('pos.verify_amounts') }}</p>
          </div>
          <strong class="text-2xl text-[var(--color-success)]">{{ getCurrency() }}{{ saleTotal }}</strong>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-y-1 text-sm">
          <span class="text-[var(--color-text-secondary)]">Subtotal</span><span class="text-right font-semibold">{{ getCurrency() }}{{ numberFormat(cartSubtotal) }}</span>
          <span class="text-[var(--color-text-secondary)]">Descuento</span><span class="text-right font-semibold text-[var(--color-danger)]">- {{ getCurrency() }}{{ numberFormat(totalDiscount + customDiscountAmount) }}</span>
          <span class="text-[var(--color-text-secondary)]">IGV</span><span class="text-right font-semibold">{{ getCurrency() }}{{ numberFormat(totalTax) }}</span>
        </div>
        <div class="mt-3">
          <label class="ihc-label">{{ t('pos.extra_discount') }}</label>
          <div class="flex mt-1">
            <select v-model="form.custom_discount.discount_type" class="h-10 rounded-l-md border-[var(--color-border)] text-sm"><option value="fixed">S/</option><option value="percentage">%</option></select>
            <input v-model.number="form.custom_discount.discount" type="number" min="0" class="h-10 w-full rounded-r-md border-[var(--color-border)]" />
          </div>
        </div>
      </section>

      <section class="mt-4">
        <h3 class="text-sm font-bold text-[var(--color-text-primary)]">2. {{ t('pos.document') }}</h3>
        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
          <button
            v-for="document in documentTypes"
            :key="document.value"
            type="button"
            @click="selectDocumentType(document.value)"
            class="rounded-md border p-3 text-left transition"
            :class="form.document_type === document.value ? 'border-[var(--color-success)] bg-[var(--color-success-soft)] ring-2 ring-[color-mix(in_srgb,var(--color-success)_18%,transparent)]' : 'border-[var(--color-border)] hover:bg-[var(--color-surface-alt)]'"
          >
            <span class="block font-bold text-[var(--color-text-primary)]">{{ document.label }}</span>
            <small class="text-[var(--color-text-muted)]">{{ document.description }}</small>
          </button>
        </div>
        <p class="mt-2 rounded-md bg-[var(--color-warning-soft)] px-3 py-2 text-xs font-semibold text-[var(--color-warning)]">
          {{ t('pos.sunat_notice') }}
        </p>
        <InputError :message="form.errors.document_type" />
      </section>

      <section class="mt-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-bold text-[var(--color-text-primary)]">3. {{ t('sales.payment') }}</h3>
          <button type="button" @click="addPayment" :disabled="form.payments.length >= 2" class="text-sm font-semibold text-[var(--color-success)] disabled:opacity-40"><i class="fas fa-plus mr-1"></i>{{ t('pos.mixed_payment') }}</button>
        </div>

      <div v-for="(payment, index) in form.payments" :key="index" class="mb-3 rounded-md border border-[var(--color-border)] p-3">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ t('pos.payment_number', { number: index + 1 }) }}</span>
          <button v-if="form.payments.length > 1" type="button" @click="removePayment(index)" class="text-sm text-[var(--color-danger)]" :title="t('pos.remove_payment')" :aria-label="t('pos.remove_payment')"><i class="fas fa-times mr-1"></i>{{ t('pos.remove') }}</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
          <button v-for="method in paymentMethods" :key="method.value" type="button" @click="selectPaymentMethod(payment, method.value)"
            class="pos-payment-method"
            :class="[
              `is-${method.value}`,
              payment.method === method.value ? 'is-selected' : ''
            ]">
            <img
              v-if="paymentLogo(method)"
              :src="paymentLogo(method)"
              :alt="paymentMethodLabel(method)"
              class="pos-payment-logo"
            />
            <i v-else :class="paymentIcons[method.value]" class="pos-payment-icon"></i>
            <span>{{ paymentMethodLabel(method) }}</span>
          </button>
        </div>

        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
          <label class="ihc-label">{{ t('cash.amount') }}
            <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="ihc-field" />
          </label>
          <label v-if="payment.method === 'cash'" class="ihc-label">{{ t('pos.cash_received') }}
            <input v-model.number="payment.received_amount" type="number" min="0" step="0.01" class="ihc-field" />
          </label>
          <label v-else class="ihc-label">{{ t('pos.operation_number') }}
            <input v-model="payment.operation_number" class="ihc-field" :placeholder="t('pos.optional')" />
          </label>
          <label v-if="payment.method === 'transfer'" class="ihc-label">{{ t('sales.bank') }}
            <input v-model="payment.bank_name" class="ihc-field" :placeholder="t('pos.optional')" />
          </label>
          <label v-if="payment.method !== 'cash'" class="ihc-label">{{ t('cash.notes') }}
            <input v-model="payment.notes" class="ihc-field" :placeholder="t('pos.optional')" />
          </label>
        </div>
        <div v-if="payment.method === 'cash' && payment.received_amount > payment.amount" class="mt-2 rounded-md bg-[var(--color-success-soft)] px-3 py-2 text-right font-bold text-[var(--color-success)]">
          {{ t('sales.change') }}: {{ getCurrency() }}{{ numberFormat(payment.received_amount - payment.amount) }}
        </div>
        <InputError :message="form.errors[`payments.${index}.amount`] || form.errors[`payments.${index}.operation_number`]" />
      </div>
      </section>

      <div class="mt-4 flex justify-between rounded-md px-4 py-3 font-semibold" :class="Math.abs(remaining) > 0.01 ? 'bg-[var(--color-warning-soft)] text-[var(--color-warning)]' : 'bg-[var(--color-success-soft)] text-[var(--color-success)]'">
        <span>{{ Math.abs(remaining) > 0.01 ? t('pos.remaining') : t('pos.complete') }}</span><strong>{{ getCurrency() }}{{ remaining }}</strong>
      </div>
      <InputError :message="form.errors.payments" />
    </Modal>

    <Modal :title="t('cash.close_register')" :submitButtonText="t('cash.close_register')" :show="showCloseRegisterModal" :formProcessing="closeForm.processing"
      @close="showCloseRegisterModal = false" @submitAction="submitCloseRegister" maxWidth="sm">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label v-for="method in paymentMethods" :key="method.value" class="ihc-label">{{ paymentMethodLabel(method) }} {{ t('cash.declared').toLowerCase() }}
          <input v-model.number="closeForm.declared_amounts[method.value]" type="number" min="0" step="0.01" class="ihc-field" />
        </label>
      </div>
      <InputError :message="closeForm.errors.declared_amounts" />
      <label for="closing_notes" class="ihc-label mt-4">{{ t('cash.notes') }}</label>
      <textarea id="closing_notes" v-model="closeForm.closing_notes" rows="2" class="ihc-field" :placeholder="t('pos.closing_notes_placeholder')"></textarea>
      <InputError :message="closeForm.errors.closing_notes" />
    </Modal>

    <ConfirmDialog
      :show="showCloseConfirmation"
      :title="t('cash.confirm_close')"
      :action="t('cash.close_action')"
      :consequence="t('cash.close_consequence')"
      :loading="closeForm.processing"
      :confirm-text="t('pos.confirm_close_text')"
      @cancel="showCloseConfirmation = false"
      @confirm="confirmCloseRegister"
    />
  </AuthenticatedLayout>
</template>

<style scoped>
.pos-scroll { scrollbar-width: thin; scrollbar-color: #94a3b8 transparent; }
.pos-scroll::-webkit-scrollbar { width: 6px; }
.pos-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 6px; }
</style>

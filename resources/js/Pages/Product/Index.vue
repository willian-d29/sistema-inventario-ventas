<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import FilterPanel from '@/Components/UI/FilterPanel.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import SmartImage from '@/Components/UI/SmartImage.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import ProductForm from '@/Pages/Product/Partials/ProductForm.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { cleanQuery, numberFormat, showToast, truncateString } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  products: { type: Object, required: true },
  categoryOptions: { type: Array, default: () => [] },
  canManageProducts: { type: Boolean, default: false },
});

const selectedProduct = ref(null);
const showFormModal = ref(false);
const showDeleteDialog = ref(false);
const formMode = ref('create');
const filterProcessing = ref(false);
const deleteForm = useForm({});
const productFormRef = ref(null);
const { t } = useI18n();

const stockRangeByStatus = {
  available: [10, 999999999],
  low: [1, 9],
  out: [0, 0],
};

function filterValue(key, fallback = '') {
  return props.filters?.[key]?.value ?? fallback;
}

function resolveStockStatus(value) {
  const range = Array.isArray(value) ? value.map((item) => Number(item)) : [];
  if (range[0] === 10) return 'available';
  if (range[0] === 1 && range[1] === 9) return 'low';
  if (range[0] === 0 && range[1] === 0) return 'out';

  return '';
}

const filterForm = useForm({
  keyword: filterValue('keyword'),
  barcode: filterValue('barcode'),
  category_id: filterValue('category_id'),
  status: filterValue('status'),
  stock_status: resolveStockStatus(filterValue('quantities', [])),
});

const productForm = useForm({
  category_id: null,
  supplier_id: null,
  name: '',
  description: '',
  product_code: '',
  barcode: '',
  root: '',
  buying_date: '',
  buying_price: '',
  selling_price: '',
  unit_type_id: null,
  quantity: 1,
  photo: null,
  status: 'active',
});

const statusOptions = computed(() => [
  { value: 'active', label: t('states.active') },
  { value: 'inactive', label: t('states.inactive') },
]);

const stockOptions = computed(() => [
  { value: 'available', label: t('products.available') },
  { value: 'low', label: t('products.low_stock') },
  { value: 'out', label: t('products.out_of_stock') },
]);

const formTitle = computed(() => formMode.value === 'create' ? t('products.create_title') : t('products.edit_title'));

function fillProductForm(product = null) {
  Object.assign(productForm, {
    category_id: product?.category_id ?? null,
    supplier_id: product?.supplier_id ?? null,
    name: product?.name || '',
    description: product?.description || '',
    product_code: product?.product_code || '',
    barcode: product?.barcode || '',
    root: product?.root || '',
    buying_date: product?.buying_date ? String(product.buying_date).split(' ')[0] : '',
    buying_price: product?.buying_price ?? '',
    selling_price: product?.selling_price ?? '',
    unit_type_id: product?.unit_type_id ?? null,
    quantity: product?.quantity ?? 1,
    photo: null,
    status: product?.status || 'active',
  });
}

function productIdentifier(product) {
  return product.barcode || product.product_code || product.product_number || '-';
}

function openCreateModal() {
  formMode.value = 'create';
  selectedProduct.value = null;
  productForm.reset();
  fillProductForm();
  productForm.clearErrors();
  showFormModal.value = true;
  nextTick(() => productFormRef.value?.focusBarcode?.());
}

function openEditModal(product) {
  formMode.value = 'edit';
  selectedProduct.value = product;
  fillProductForm(product);
  productForm.clearErrors();
  showFormModal.value = true;
  nextTick(() => productFormRef.value?.focusBarcode?.());
}

function submitProductForm() {
  productForm.product_code = productForm.barcode || productForm.product_code || '';

  const options = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      showFormModal.value = false;
      showToast();
      productForm.reset();
    },
  };

  if (formMode.value === 'create') {
    productForm.transform((data) => data).post(route('products.store'), options);
    return;
  }

  productForm.transform((data) => ({ ...data, _method: 'put' }))
    .post(route('products.update', selectedProduct.value.id), options);
}

function stockState(product) {
  const quantity = Number(product.quantity || 0);
  if (quantity <= 0) return { label: t('common.exhausted'), variant: 'danger', icon: 'fa-circle-xmark' };
  if (quantity < 10) return { label: t('common.low_stock'), variant: 'warning', icon: 'fa-triangle-exclamation' };
  return { label: t('common.available'), variant: 'success', icon: 'fa-check-circle' };
}

function statusState(product) {
  return product.status === 'active'
    ? { label: t('states.active'), variant: 'success', icon: 'fa-check-circle' }
    : { label: t('states.inactive'), variant: 'danger', icon: 'fa-ban' };
}

function deleteProductModal(product) {
  selectedProduct.value = product;
  showDeleteDialog.value = true;
}

function applyFilters() {
  const payload = filterForm.data();
  const stockStatus = payload.stock_status;

  delete payload.stock_status;

  if (stockStatus && stockRangeByStatus[stockStatus]) {
    payload.quantities = stockRangeByStatus[stockStatus];
  }

  router.get(route('products.index'), cleanQuery(payload), {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    onStart: () => {
      filterProcessing.value = true;
    },
    onFinish: () => {
      filterProcessing.value = false;
    },
  });
}

function clearFilters() {
  filterForm.keyword = '';
  filterForm.barcode = '';
  filterForm.category_id = '';
  filterForm.status = '';
  filterForm.stock_status = '';
  applyFilters();
}

function deleteProduct() {
  deleteForm.delete(route('products.destroy', selectedProduct.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedProduct.value = null;
      showToast();
    },
  });
}

const hasProducts = computed(() => props.products?.data?.length > 0);
const visibleProducts = computed(() => props.products?.data || []);
const summary = computed(() => {
  const items = visibleProducts.value;

  return {
    total: props.products?.total || items.length,
    available: items.filter((product) => Number(product.quantity || 0) >= 10).length,
    lowStock: items.filter((product) => Number(product.quantity || 0) > 0 && Number(product.quantity || 0) < 10).length,
    outOfStock: items.filter((product) => Number(product.quantity || 0) <= 0).length,
    withoutCost: props.canManageProducts ? items.filter((product) => Number(product.buying_price || 0) <= 0).length : 0,
  };
});

const activeFilterCount = computed(() => [
  filterForm.keyword,
  filterForm.barcode,
  filterForm.category_id,
  filterForm.status,
  filterForm.stock_status,
].filter(Boolean).length);
</script>

<template>
  <Head :title="t('products.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('products.title') }}</template>

    <div class="space-y-5">
      <PageHeader
        :title="t('products.title')"
        :description="t('products.description')"
        :count="products.total"
      >
        <template v-if="canManageProducts" #actions>
          <AppButton icon="fa-plus" data-tour="create-button" @click="openCreateModal">{{ t('actions.new_product') }}</AppButton>
        </template>
      </PageHeader>

      <div class="grid gap-3 sm:grid-cols-2" data-tour="summary-cards" :class="canManageProducts ? 'xl:grid-cols-5' : 'xl:grid-cols-4'">
        <StatCard :title="t('products.total')" :value="summary.total" icon="fa-boxes" variant="info" />
        <StatCard :title="t('products.available')" :value="summary.available" icon="fa-check-circle" variant="success" />
        <StatCard :title="t('products.low_stock')" :value="summary.lowStock" icon="fa-triangle-exclamation" variant="warning" />
        <StatCard :title="t('products.out_of_stock')" :value="summary.outOfStock" icon="fa-circle-xmark" variant="danger" />
        <StatCard v-if="canManageProducts" :title="t('products.without_cost')" :value="summary.withoutCost" icon="fa-tag" variant="analytics" />
      </div>

      <FilterPanel>
        <form class="contents" @submit.prevent="applyFilters">
          <div class="sm:col-span-2 xl:col-span-2">
            <AppInput
              v-model="filterForm.keyword"
              :label="t('products.filter_search')"
              :placeholder="t('products.search_placeholder')"
              autocomplete="off"
            />
          </div>
          <AppInput
            v-model="filterForm.barcode"
            :label="t('common.barcode')"
            :placeholder="t('products.barcode_placeholder')"
            autocomplete="off"
            inputmode="numeric"
          />
          <AppSelect
            v-model="filterForm.category_id"
            :label="t('common.category')"
            :placeholder="t('products.all_categories')"
            :options="categoryOptions"
          />
          <AppSelect
            v-model="filterForm.stock_status"
            :label="t('products.stock_filter')"
            :placeholder="t('products.all_stock')"
            :options="stockOptions"
          />
          <AppSelect
            v-model="filterForm.status"
            :label="t('common.status')"
            :placeholder="t('common.all')"
            :options="statusOptions"
          />
          <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-2">
            <AppButton class="is-nowrap" type="submit" icon="fa-filter" :loading="filterProcessing">{{ t('actions.apply_filters') }}</AppButton>
            <AppButton class="is-nowrap" type="button" variant="secondary" icon="fa-eraser" @click="clearFilters">{{ t('actions.clear_filters') }}</AppButton>
          </div>
          <div class="flex items-end sm:col-span-2 xl:col-span-4">
            <span class="app-ui-help">
              {{ t('products.results_summary', { count: products.total, active: activeFilterCount }) }}
            </span>
          </div>
        </form>
      </FilterPanel>

      <section class="product-inventory-shell overflow-hidden" data-tour="records-list">
        <div class="hidden overflow-x-auto xl:block">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase">
              <tr>
                <th scope="col" class="px-4 py-3">{{ t('products.table_product') }}</th>
                <th scope="col" class="px-3 py-3">{{ t('products.barcode_column') }}</th>
                <th scope="col" class="px-3 py-3">{{ t('common.category') }}</th>
                <th scope="col" class="px-3 py-3">Stock</th>
                <th v-if="canManageProducts" scope="col" class="px-3 py-3 text-right">{{ t('common.cost') }}</th>
                <th scope="col" class="px-3 py-3 text-right">{{ t('common.price') }}</th>
                <th scope="col" class="px-3 py-3">{{ t('common.status') }}</th>
                <th v-if="canManageProducts" scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in visibleProducts" :key="product.id">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <SmartImage :src="product.photo" :alt="product.name" class="product-thumb" contain />
                    <div class="min-w-0">
                      <strong class="block truncate text-[var(--color-text-primary)]">{{ product.name }}</strong>
                      <span class="app-ui-help">{{ t('products.supplier') }}: {{ product.supplier?.name || '-' }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-3">
                  <span class="block font-semibold">{{ productIdentifier(product) }}</span>
                </td>
                <td class="px-3 py-3">{{ product.category?.name || '-' }}</td>
                <td class="px-3 py-3">
                  <span class="block font-black">{{ numberFormat(product.quantity) }} {{ product.unit_type?.symbol }}</span>
                  <AppBadge :variant="stockState(product).variant" :icon="stockState(product).icon">
                    {{ stockState(product).label }}
                  </AppBadge>
                </td>
                <td v-if="canManageProducts" class="px-3 py-3 text-right">S/ {{ numberFormat(product.buying_price || 0) }}</td>
                <td class="px-3 py-3 text-right font-black">S/ {{ numberFormat(product.selling_price || 0) }}</td>
                <td class="px-3 py-3">
                  <AppBadge :variant="statusState(product).variant" :icon="statusState(product).icon">
                    {{ statusState(product).label }}
                  </AppBadge>
                </td>
                <td v-if="canManageProducts" class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2" data-tour="row-actions">
                    <AppButton
                      class="h-9 w-9 px-0"
                      variant="success"
                      size="sm"
                      icon="fa-pencil-alt"
                      :aria-label="`${t('actions.edit')} ${product.name}`"
                      :title="t('actions.edit')"
                      @click="openEditModal(product)"
                    >
                      <span class="sr-only">{{ t('actions.edit') }}</span>
                    </AppButton>
                    <AppButton
                      class="h-9 w-9 px-0"
                      variant="danger"
                      size="sm"
                      icon="fa-trash-alt"
                      :aria-label="`${t('actions.delete')} ${product.name}`"
                      :title="t('actions.delete')"
                      @click="deleteProductModal(product)"
                    >
                      <span class="sr-only">{{ t('actions.delete') }}</span>
                    </AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-[var(--color-border)] xl:hidden">
          <article v-for="product in visibleProducts" :key="product.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 gap-3">
                <SmartImage :src="product.photo" :alt="product.name" class="product-thumb" contain />
                <div class="min-w-0">
                  <h2 class="truncate font-black text-[var(--color-text-primary)]">{{ product.name }}</h2>
                  <p class="app-ui-help">{{ productIdentifier(product) }}</p>
                </div>
              </div>
              <strong class="shrink-0">S/ {{ numberFormat(product.selling_price || 0) }}</strong>
            </div>
            <div class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
              <p>
                <span class="app-ui-help">{{ t('common.category') }}</span>
                <span class="block font-semibold text-[var(--color-text-secondary)]">{{ truncateString(product.category?.name || t('products.no_category'), 32) }}</span>
              </p>
              <p v-if="canManageProducts">
                <span class="app-ui-help">{{ t('common.cost') }}</span>
                <span class="block font-semibold text-[var(--color-text-secondary)]">S/ {{ numberFormat(product.buying_price || 0) }}</span>
              </p>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <AppBadge :variant="stockState(product).variant" :icon="stockState(product).icon">
                {{ stockState(product).label }} · {{ numberFormat(product.quantity) }} {{ product.unit_type?.symbol }}
              </AppBadge>
              <AppBadge :variant="statusState(product).variant" :icon="statusState(product).icon">
                {{ statusState(product).label }}
              </AppBadge>
            </div>
            <div v-if="canManageProducts" class="mt-3 flex flex-wrap gap-2" data-tour="row-actions">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" @click="openEditModal(product)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" @click="deleteProductModal(product)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState
          v-if="!hasProducts"
          :title="t('empty.no_products')"
          :description="t('products.create_first')"
          icon="fa-box"
          :action-label="canManageProducts ? t('actions.new_product') : null"
          @action="openCreateModal"
        />
      </section>

      <AppPagination :links="products.links" :label="t('pagination.label')" />
    </div>

    <AppModal
      v-if="canManageProducts"
      :show="showFormModal"
      :title="formTitle"
      size="xl"
      @close="showFormModal = false"
    >
      <ProductForm
        :key="`${formMode}-${selectedProduct?.id || 'new'}-${showFormModal ? 'open' : 'closed'}`"
        ref="productFormRef"
        :form="productForm"
        :mode="formMode"
        :product-photo="selectedProduct?.photo"
        :show-actions="false"
        @submit="submitProductForm"
      />
      <template #footer>
        <AppButton variant="secondary" :disabled="productForm.processing" @click="showFormModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" data-tour="product-form-save" :loading="productForm.processing" :loading-text="t('common.loading')" @click="submitProductForm">
          {{ formMode === 'edit' ? t('actions.save_changes') : t('actions.save') }}
        </AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      v-if="canManageProducts"
      :show="showDeleteDialog"
      :title="t('products.delete_title')"
      :action="t('products.delete_action', { name: selectedProduct?.name || t('products.title') })"
      :consequence="t('products.delete_consequence')"
      :confirm-text="t('products.confirm_delete')"
      :loading="deleteForm.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteProduct"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import FilterPanel from '@/Components/UI/FilterPanel.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { cleanQuery, showToast } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  suppliers: { type: Object, required: true },
});

const { t } = useI18n();
const selectedSupplier = ref(null);
const showFormModal = ref(false);
const showDeleteDialog = ref(false);
const formMode = ref('create');
const nameInput = ref(null);

const filterForm = useForm({
  name: props.filters?.name?.value || '',
  email: props.filters?.email?.value || '',
  phone: props.filters?.phone?.value || '',
  shop_name: props.filters?.shop_name?.value || '',
});

const form = useForm({
  name: '',
  email: '',
  phone: '',
  shop_name: '',
  address: '',
  photo: null,
  _method: '',
});

const title = computed(() => formMode.value === 'create' ? t('admin.suppliers.create') : t('admin.suppliers.edit'));
const items = computed(() => props.suppliers?.data || []);

function applyFilters() {
  router.get(route('suppliers.index'), cleanQuery(filterForm.data()), { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.name = '';
  filterForm.email = '';
  filterForm.phone = '';
  filterForm.shop_name = '';
  router.get(route('suppliers.index'), {}, { preserveState: true, replace: true });
}

function fillForm(supplier = null) {
  Object.assign(form, {
    name: supplier?.name || '',
    email: supplier?.email || '',
    phone: supplier?.phone || '',
    shop_name: supplier?.shop_name || '',
    address: supplier?.address || '',
    photo: null,
    _method: '',
  });
}

function openCreateModal() {
  formMode.value = 'create';
  selectedSupplier.value = null;
  form.reset();
  fillForm();
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function openEditModal(supplier) {
  formMode.value = 'edit';
  selectedSupplier.value = supplier;
  fillForm(supplier);
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function submitForm() {
  const options = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      showFormModal.value = false;
      showToast();
      form.reset();
    },
  };

  if (formMode.value === 'create') {
    form._method = '';
    form.post(route('suppliers.store'), options);
    return;
  }

  form._method = 'put';
  form.post(route('suppliers.update', selectedSupplier.value.id), options);
}

function openDeleteDialog(supplier) {
  selectedSupplier.value = supplier;
  showDeleteDialog.value = true;
}

function deleteSupplier() {
  form.delete(route('suppliers.destroy', selectedSupplier.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedSupplier.value = null;
      showToast();
    },
  });
}
</script>

<template>
  <Head :title="t('admin.suppliers.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('admin.suppliers.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader :title="t('admin.suppliers.title')" :description="t('admin.suppliers.description')" :count="suppliers.total">
        <template #actions>
          <AppButton icon="fa-plus" @click="openCreateModal">{{ t('admin.suppliers.create') }}</AppButton>
        </template>
      </PageHeader>

      <FilterPanel>
        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <AppInput v-model="filterForm.name" :label="t('admin.filters.search')" :placeholder="t('admin.suppliers.search_placeholder')" />
          <AppInput v-model="filterForm.email" :label="t('admin.fields.email')" placeholder="proveedor@empresa.com" />
          <AppInput v-model="filterForm.phone" :label="t('admin.fields.phone')" placeholder="999999999" />
          <AppInput v-model="filterForm.shop_name" :label="t('admin.fields.trade_name')" :placeholder="t('admin.fields.trade_name')" />
          <div class="flex items-end gap-2">
            <AppButton type="submit" icon="fa-filter" :loading="filterForm.processing">{{ t('actions.apply_filters') }}</AppButton>
            <AppButton type="button" variant="secondary" icon="fa-eraser" @click="clearFilters">{{ t('actions.clear_filters') }}</AppButton>
          </div>
        </form>
      </FilterPanel>

      <section class="ihc-panel overflow-hidden">
        <div v-if="items.length" class="hidden overflow-x-auto xl:block">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase">
              <tr>
                <th scope="col" class="px-4 py-3">{{ t('admin.suppliers.title') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.document') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.sections.contact') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('common.status') }}</th>
                <th scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="supplier in items" :key="supplier.id">
                <td class="px-4 py-3">
                  <strong class="block text-[var(--color-text-primary)]">{{ supplier.name }}</strong>
                  <span class="app-ui-help">{{ supplier.shop_name || t('common.no_description') }}</span>
                </td>
                <td class="px-4 py-3">{{ t('admin.suppliers.no_document') }}</td>
                <td class="px-4 py-3">
                  <span class="block">{{ supplier.phone || '-' }}</span>
                  <span class="app-ui-help">{{ supplier.email || '-' }}</span>
                </td>
                <td class="px-4 py-3"><AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge></td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <AppButton class="w-9 px-0" variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${supplier.name}`" @click="openEditModal(supplier)"><span class="sr-only">{{ t('actions.edit') }}</span></AppButton>
                    <AppButton class="w-9 px-0" variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${supplier.name}`" @click="openDeleteDialog(supplier)"><span class="sr-only">{{ t('actions.delete') }}</span></AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.length" class="divide-y divide-[var(--color-border)] xl:hidden">
          <article v-for="supplier in items" :key="supplier.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h2 class="break-words font-black text-[var(--color-text-primary)]">{{ supplier.name }}</h2>
                <p class="app-ui-help">{{ supplier.shop_name || t('admin.suppliers.no_document') }}</p>
              </div>
              <AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge>
            </div>
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">{{ supplier.phone || '-' }} · {{ supplier.email || '-' }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${supplier.name}`" @click="openEditModal(supplier)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${supplier.name}`" @click="openDeleteDialog(supplier)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState v-if="!items.length" icon="fa-truck" :title="t('admin.suppliers.empty')" :description="t('admin.suppliers.description')" />
      </section>

      <AppPagination :links="suppliers.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showFormModal" :title="title" size="lg" :initial-focus="nameInput" @close="showFormModal = false">
      <div class="space-y-5">
        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.general') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput ref="nameInput" v-model="form.name" :label="t('admin.fields.name_or_business_name')" :placeholder="t('admin.fields.name_or_business_name')" :error="form.errors.name" required />
            <AppInput v-model="form.shop_name" :label="t('admin.fields.trade_name')" :placeholder="t('admin.fields.trade_name')" :error="form.errors.shop_name" />
          </div>
        </section>
        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.contact') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput v-model="form.phone" :label="t('admin.fields.phone')" placeholder="999999999" :error="form.errors.phone" required />
            <AppInput v-model="form.email" :label="t('admin.fields.email')" type="email" placeholder="proveedor@empresa.com" :error="form.errors.email" required />
          </div>
        </section>
        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.location') }}</h3>
          <AppTextarea v-model="form.address" :label="t('admin.fields.address')" :placeholder="t('admin.fields.address')" :error="form.errors.address" />
        </section>
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showFormModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="submitForm">{{ t('admin.suppliers.save') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('admin.suppliers.delete_title')"
      :action="t('admin.suppliers.delete_action', { name: selectedSupplier?.name || t('admin.suppliers.title') })"
      :consequence="t('admin.suppliers.delete_consequence')"
      :confirm-text="t('actions.delete')"
      :cancel-text="t('actions.cancel')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteSupplier"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import FilterPanel from '@/Components/UI/FilterPanel.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { cleanQuery, showToast } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  unitTypes: { type: Object, required: true },
});

const { t } = useI18n();
const selectedUnitType = ref(null);
const showFormModal = ref(false);
const showDeleteDialog = ref(false);
const formMode = ref('create');
const nameInput = ref(null);

const filterForm = useForm({
  name: props.filters?.name?.value || '',
  symbol: props.filters?.symbol?.value || '',
});

const form = useForm({
  name: '',
  symbol: '',
});

const title = computed(() => formMode.value === 'create' ? t('admin.units.create') : t('admin.units.edit'));
const items = computed(() => props.unitTypes?.data || []);

function applyFilters() {
  router.get(route('unit-types.index'), cleanQuery(filterForm.data()), { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.name = '';
  filterForm.symbol = '';
  router.get(route('unit-types.index'), {}, { preserveState: true, replace: true });
}

function openCreateModal() {
  formMode.value = 'create';
  selectedUnitType.value = null;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function openEditModal(unitType) {
  formMode.value = 'edit';
  selectedUnitType.value = unitType;
  form.name = unitType.name;
  form.symbol = unitType.symbol;
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function submitForm() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showFormModal.value = false;
      showToast();
      form.reset();
    },
  };

  if (formMode.value === 'create') {
    form.post(route('unit-types.store'), options);
    return;
  }

  form.put(route('unit-types.update', selectedUnitType.value.id), options);
}

function openDeleteDialog(unitType) {
  selectedUnitType.value = unitType;
  showDeleteDialog.value = true;
}

function deleteUnitType() {
  form.delete(route('unit-types.destroy', selectedUnitType.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedUnitType.value = null;
      showToast();
    },
  });
}
</script>

<template>
  <Head :title="t('admin.units.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('admin.units.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader :title="t('admin.units.title')" :description="t('admin.units.description')" :count="unitTypes.total">
        <template #actions>
          <AppButton icon="fa-plus" @click="openCreateModal">{{ t('admin.units.create') }}</AppButton>
        </template>
      </PageHeader>

      <FilterPanel>
        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <AppInput v-model="filterForm.name" :label="t('common.name')" :placeholder="t('admin.units.search_placeholder')" />
          <AppInput v-model="filterForm.symbol" :label="t('admin.fields.symbol')" :placeholder="t('admin.units.symbol_placeholder')" />
          <div class="flex items-end gap-2">
            <AppButton type="submit" icon="fa-filter" :loading="filterForm.processing">{{ t('actions.apply_filters') }}</AppButton>
            <AppButton type="button" variant="secondary" icon="fa-eraser" @click="clearFilters">{{ t('actions.clear_filters') }}</AppButton>
          </div>
        </form>
      </FilterPanel>

      <section class="ihc-panel overflow-hidden">
        <div v-if="items.length" class="hidden overflow-x-auto lg:block">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase">
              <tr>
                <th scope="col" class="px-4 py-3">{{ t('common.name') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.symbol') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('common.status') }}</th>
                <th scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="unitType in items" :key="unitType.id">
                <td class="px-4 py-3 font-black text-[var(--color-text-primary)]">{{ unitType.name }}</td>
                <td class="px-4 py-3"><AppBadge variant="info">{{ unitType.symbol }}</AppBadge></td>
                <td class="px-4 py-3"><AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge></td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <AppButton class="w-9 px-0" variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${unitType.name}`" @click="openEditModal(unitType)"><span class="sr-only">{{ t('actions.edit') }}</span></AppButton>
                    <AppButton class="w-9 px-0" variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${unitType.name}`" @click="openDeleteDialog(unitType)"><span class="sr-only">{{ t('actions.delete') }}</span></AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.length" class="divide-y divide-[var(--color-border)] lg:hidden">
          <article v-for="unitType in items" :key="unitType.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 class="font-black text-[var(--color-text-primary)]">{{ unitType.name }}</h2>
                <p class="app-ui-help">{{ t('admin.fields.symbol') }}: {{ unitType.symbol }}</p>
              </div>
              <AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${unitType.name}`" @click="openEditModal(unitType)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${unitType.name}`" @click="openDeleteDialog(unitType)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState v-if="!items.length" icon="fa-balance-scale" :title="t('admin.units.empty')" :description="t('admin.units.description')" />
      </section>

      <AppPagination :links="unitTypes.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showFormModal" :title="title" size="md" :initial-focus="nameInput" @close="showFormModal = false">
      <div class="grid gap-4 sm:grid-cols-2">
        <AppInput ref="nameInput" v-model="form.name" :label="t('common.name')" :placeholder="t('admin.units.search_placeholder')" :error="form.errors.name" required @keyup.enter="submitForm" />
        <AppInput v-model="form.symbol" :label="t('admin.fields.symbol')" :placeholder="t('admin.units.symbol_placeholder')" :error="form.errors.symbol" required @keyup.enter="submitForm" />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showFormModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="submitForm">{{ t('admin.units.save') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('admin.units.delete_title')"
      :action="t('admin.units.delete_action', { name: selectedUnitType?.name || t('admin.units.title') })"
      :consequence="t('admin.units.delete_consequence')"
      :confirm-text="t('actions.delete')"
      :cancel-text="t('actions.cancel')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteUnitType"
    />
  </AuthenticatedLayout>
</template>

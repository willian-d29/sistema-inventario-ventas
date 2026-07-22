<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppAlert from '@/Components/UI/AppAlert.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AsyncVueSelect from '@/Components/AsyncVueSelect.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import FilterPanel from '@/Components/UI/FilterPanel.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { cleanQuery, getCurrency, numberFormat, showToast } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  salaries: { type: Object, required: true },
});

const { t } = useI18n();
const selectedSalary = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteDialog = ref(false);
const employeeSelect = ref(null);

const filterForm = useForm({
  salary_date: props.filters?.salary_date?.value || '',
});

const form = useForm({
  employee_id: '',
  amount: '',
  salary_date: '',
});

const items = computed(() => props.salaries?.data || []);

function money(value) {
  return `${getCurrency()}${numberFormat(Number(value || 0))}`;
}

function salaryEmployeeName(salary) {
  return salary.employee?.name || t('common.unavailable');
}

function applyFilters() {
  router.get(route('salaries.index'), cleanQuery(filterForm.data()), { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.salary_date = '';
  router.get(route('salaries.index'), {}, { preserveState: true, replace: true });
}

function openCreateModal() {
  selectedSalary.value = null;
  form.reset();
  form.clearErrors();
  showCreateModal.value = true;
  nextTick(() => employeeSelect.value?.$el?.querySelector('input')?.focus?.());
}

function openEditModal(salary) {
  selectedSalary.value = salary;
  form.employee_id = salary.employee_id;
  form.amount = salary.amount;
  form.salary_date = salary.salary_date;
  form.clearErrors();
  showEditModal.value = true;
}

function createSalary() {
  form.post(route('salaries.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      showToast();
      form.reset();
    },
  });
}

function updateSalary() {
  form.put(route('salaries.update', selectedSalary.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      showToast();
      form.reset();
    },
  });
}

function openDeleteDialog(salary) {
  selectedSalary.value = salary;
  showDeleteDialog.value = true;
}

function deleteSalary() {
  form.delete(route('salaries.destroy', selectedSalary.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedSalary.value = null;
      showToast();
    },
  });
}
</script>

<template>
  <Head :title="t('admin.salaries.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('admin.salaries.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader :title="t('admin.salaries.title')" :description="t('admin.salaries.description')" :count="salaries.total">
        <template #actions>
          <AppButton icon="fa-plus" @click="openCreateModal">{{ t('admin.salaries.create') }}</AppButton>
        </template>
      </PageHeader>

      <AppAlert variant="info" :title="t('admin.salaries.title')" :message="t('admin.salaries.active_note')" />

      <FilterPanel>
        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <AppInput v-model="filterForm.salary_date" :label="t('admin.fields.salary_date')" type="month" :placeholder="t('admin.salaries.month_placeholder')" />
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
                <th scope="col" class="px-4 py-3">{{ t('admin.salaries.employee') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.work_position') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('common.amount') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.salary_date') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('common.status') }}</th>
                <th scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="salary in items" :key="salary.id">
                <td class="px-4 py-3 font-black text-[var(--color-text-primary)]">{{ salaryEmployeeName(salary) }}</td>
                <td class="px-4 py-3">{{ salary.employee?.designation || '-' }}</td>
                <td class="px-4 py-3 font-black">{{ money(salary.amount) }}</td>
                <td class="px-4 py-3">{{ salary.salary_date }}</td>
                <td class="px-4 py-3"><AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge></td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <AppButton class="w-9 px-0" variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${salaryEmployeeName(salary)}`" @click="openEditModal(salary)"><span class="sr-only">{{ t('actions.edit') }}</span></AppButton>
                    <AppButton class="w-9 px-0" variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${salaryEmployeeName(salary)}`" @click="openDeleteDialog(salary)"><span class="sr-only">{{ t('actions.delete') }}</span></AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.length" class="divide-y divide-[var(--color-border)] lg:hidden">
          <article v-for="salary in items" :key="salary.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h2 class="break-words font-black text-[var(--color-text-primary)]">{{ salaryEmployeeName(salary) }}</h2>
                <p class="app-ui-help">{{ salary.employee?.designation || '-' }} · {{ salary.salary_date }}</p>
              </div>
              <AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge>
            </div>
            <p class="mt-2 font-black">{{ money(salary.amount) }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${salaryEmployeeName(salary)}`" @click="openEditModal(salary)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${salaryEmployeeName(salary)}`" @click="openDeleteDialog(salary)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState v-if="!items.length" icon="fa-money-check-alt" :title="t('admin.salaries.empty')" :description="t('admin.salaries.description')" />
      </section>

      <AppPagination :links="salaries.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showCreateModal" :title="t('admin.salaries.create')" size="md" @close="showCreateModal = false">
      <div class="space-y-4">
        <label class="app-ui-field">
          <span class="app-ui-label">{{ t('admin.salaries.employee') }} <span aria-hidden="true">*</span></span>
          <AsyncVueSelect ref="employeeSelect" v-model="form.employee_id" resource="employees.index" resource-label="name" :placeholder="t('admin.salaries.search_placeholder')" />
          <span v-if="form.errors.employee_id" class="app-ui-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ form.errors.employee_id }}</span>
        </label>
        <AppInput v-model="form.salary_date" :label="t('admin.fields.salary_date')" type="date" :error="form.errors.salary_date" required />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showCreateModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="createSalary">{{ t('admin.salaries.save') }}</AppButton>
      </template>
    </AppModal>

    <AppModal :show="showEditModal" :title="t('admin.salaries.edit')" size="md" @close="showEditModal = false">
      <div class="space-y-4">
        <AppInput v-model="form.employee_id" :label="t('admin.salaries.employee')" :error="form.errors.employee_id" required />
        <AppInput v-model="form.amount" :label="t('common.amount')" type="number" min="0" step="0.01" :error="form.errors.amount" required />
        <AppInput v-model="form.salary_date" :label="t('admin.fields.salary_date')" type="date" :error="form.errors.salary_date" required />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showEditModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="updateSalary">{{ t('admin.salaries.save') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('admin.salaries.delete_title')"
      :action="t('admin.salaries.delete_action', { name: salaryEmployeeName(selectedSalary || {}) })"
      :consequence="t('admin.salaries.delete_consequence')"
      :confirm-text="t('actions.delete')"
      :cancel-text="t('actions.cancel')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteSalary"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppAlert from '@/Components/UI/AppAlert.vue';
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
import { cleanQuery, getCurrency, numberFormat, showToast } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  employees: { type: Object, required: true },
});

const { t } = useI18n();
const selectedEmployee = ref(null);
const showFormModal = ref(false);
const showDeleteDialog = ref(false);
const formMode = ref('create');
const nameInput = ref(null);

const filterForm = useForm({
  name: props.filters?.name?.value || '',
  email: props.filters?.email?.value || '',
  phone: props.filters?.phone?.value || '',
  nid: props.filters?.nid?.value || '',
});

const form = useForm({
  name: '',
  email: '',
  phone: '',
  designation: '',
  address: '',
  salary: '',
  nid: '',
  joining_date: '',
  photo: null,
  password: '',
  _method: '',
});

const title = computed(() => formMode.value === 'create' ? t('admin.employees.create') : t('admin.employees.edit'));
const items = computed(() => props.employees?.data || []);

function money(value) {
  return `${getCurrency()}${numberFormat(Number(value || 0))}`;
}

function applyFilters() {
  router.get(route('employees.index'), cleanQuery(filterForm.data()), { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.name = '';
  filterForm.email = '';
  filterForm.phone = '';
  filterForm.nid = '';
  router.get(route('employees.index'), {}, { preserveState: true, replace: true });
}

function fillForm(employee = null) {
  Object.assign(form, {
    name: employee?.name || '',
    email: employee?.email || '',
    phone: employee?.phone || '',
    designation: employee?.designation || '',
    address: employee?.address || '',
    salary: employee?.salary || '',
    nid: employee?.nid || '',
    joining_date: employee?.joining_date || '',
    photo: null,
    password: '',
    _method: '',
  });
}

function openCreateModal() {
  formMode.value = 'create';
  selectedEmployee.value = null;
  form.reset();
  fillForm();
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function openEditModal(employee) {
  formMode.value = 'edit';
  selectedEmployee.value = employee;
  fillForm(employee);
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
    form.post(route('employees.store'), options);
    return;
  }

  form._method = 'put';
  form.post(route('employees.update', selectedEmployee.value.id), options);
}

function openDeleteDialog(employee) {
  selectedEmployee.value = employee;
  showDeleteDialog.value = true;
}

function deleteEmployee() {
  form.delete(route('employees.destroy', selectedEmployee.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedEmployee.value = null;
      showToast();
    },
  });
}
</script>

<template>
  <Head :title="t('admin.employees.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('admin.employees.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader :title="t('admin.employees.title')" :description="t('admin.employees.description')" :count="employees.total">
        <template #actions>
          <AppButton icon="fa-plus" @click="openCreateModal">{{ t('admin.employees.create') }}</AppButton>
        </template>
      </PageHeader>

      <AppAlert variant="info" :title="t('admin.sections.role')" :message="t('admin.employees.role_help')" />

      <FilterPanel>
        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <AppInput v-model="filterForm.name" :label="t('admin.filters.search')" :placeholder="t('admin.employees.search_placeholder')" />
          <AppInput v-model="filterForm.email" :label="t('admin.fields.email')" placeholder="cajero@empresa.com" />
          <AppInput v-model="filterForm.phone" :label="t('admin.fields.phone')" placeholder="999999999" />
          <AppInput v-model="filterForm.nid" :label="t('admin.fields.document_number')" placeholder="DNI" />
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
                <th scope="col" class="px-4 py-3">{{ t('admin.employees.title') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.role') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.sections.contact') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('admin.fields.salary') }}</th>
                <th scope="col" class="px-4 py-3">{{ t('common.status') }}</th>
                <th scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="employee in items" :key="employee.id">
                <td class="px-4 py-3">
                  <strong class="block text-[var(--color-text-primary)]">{{ employee.name }}</strong>
                  <span class="app-ui-help">{{ employee.nid || t('admin.employees.no_document') }} · {{ employee.joining_date || '-' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="block font-semibold">{{ employee.designation || t('roles.cajero') }}</span>
                  <span class="app-ui-help">{{ t('roles.cajero') }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="block">{{ employee.phone || '-' }}</span>
                  <span class="app-ui-help">{{ employee.email || '-' }}</span>
                </td>
                <td class="px-4 py-3 font-black">{{ money(employee.salary) }}</td>
                <td class="px-4 py-3"><AppBadge variant="success" icon="fa-user-check">{{ t('admin.status.enabled') }}</AppBadge></td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <AppButton class="w-9 px-0" variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${employee.name}`" @click="openEditModal(employee)"><span class="sr-only">{{ t('actions.edit') }}</span></AppButton>
                    <AppButton class="w-9 px-0" variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${employee.name}`" @click="openDeleteDialog(employee)"><span class="sr-only">{{ t('actions.delete') }}</span></AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.length" class="divide-y divide-[var(--color-border)] xl:hidden">
          <article v-for="employee in items" :key="employee.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h2 class="break-words font-black text-[var(--color-text-primary)]">{{ employee.name }}</h2>
                <p class="app-ui-help">{{ employee.designation || t('roles.cajero') }} · {{ employee.nid || t('admin.employees.no_document') }}</p>
              </div>
              <AppBadge variant="success" icon="fa-user-check">{{ t('admin.status.enabled') }}</AppBadge>
            </div>
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">{{ employee.phone || '-' }} · {{ employee.email || '-' }}</p>
            <p class="mt-1 text-sm font-black">{{ money(employee.salary) }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${employee.name}`" @click="openEditModal(employee)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${employee.name}`" @click="openDeleteDialog(employee)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState v-if="!items.length" icon="fa-id-badge" :title="t('admin.employees.empty')" :description="t('admin.employees.description')" />
      </section>

      <AppPagination :links="employees.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showFormModal" :title="title" size="xl" :initial-focus="nameInput" @close="showFormModal = false">
      <div class="space-y-5">
        <AppAlert variant="info" :title="t('admin.sections.access')" :message="t('admin.employees.access_help')" />

        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.personal') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput ref="nameInput" v-model="form.name" :label="t('admin.fields.full_name')" :placeholder="t('admin.fields.full_name')" :error="form.errors.name" required />
            <AppInput v-model="form.nid" :label="t('admin.fields.document_number')" placeholder="DNI" :error="form.errors.nid" />
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.access') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput v-model="form.email" :label="t('admin.fields.email')" type="email" placeholder="cajero@empresa.com" :error="form.errors.email" required />
            <AppInput v-model="form.password" :label="t('admin.fields.password')" type="password" :placeholder="formMode === 'create' ? t('admin.fields.password') : t('admin.employees.optional_password')" :help-text="formMode === 'edit' ? t('admin.employees.optional_password') : null" :error="form.errors.password" :required="formMode === 'create'" />
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.role') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput :model-value="t('roles.cajero')" :label="t('admin.fields.role')" readonly :help-text="t('admin.employees.role_help')" />
            <AppInput v-model="form.designation" :label="t('admin.fields.work_position')" :placeholder="t('admin.fields.work_position')" :error="form.errors.designation" required />
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.employment') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput v-model="form.salary" :label="t('admin.fields.salary')" type="number" min="0" step="0.01" :error="form.errors.salary" required />
            <AppInput v-model="form.joining_date" :label="t('admin.fields.joining_date')" type="date" :error="form.errors.joining_date" required />
          </div>
        </section>

        <section class="space-y-3">
          <h3 class="ihc-section-title">{{ t('admin.sections.contact') }}</h3>
          <div class="grid gap-4 md:grid-cols-2">
            <AppInput v-model="form.phone" :label="t('admin.fields.phone')" placeholder="999999999" :error="form.errors.phone" required />
            <AppTextarea v-model="form.address" :label="t('admin.fields.address')" :placeholder="t('admin.fields.address')" :error="form.errors.address" required />
          </div>
        </section>
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showFormModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="submitForm">{{ t('admin.employees.save') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('admin.employees.delete_title')"
      :action="t('admin.employees.delete_action', { name: selectedEmployee?.name || t('admin.employees.title') })"
      :consequence="t('admin.employees.delete_consequence')"
      :confirm-text="t('actions.delete')"
      :cancel-text="t('actions.cancel')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteEmployee"
    />
  </AuthenticatedLayout>
</template>

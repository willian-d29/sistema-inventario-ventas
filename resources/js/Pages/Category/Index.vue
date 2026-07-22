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
  categories: { type: Object, required: true },
});

const { t } = useI18n();
const selectedCategory = ref(null);
const showFormModal = ref(false);
const showDeleteDialog = ref(false);
const formMode = ref('create');
const nameInput = ref(null);

const filterForm = useForm({
  name: props.filters?.name?.value || '',
});

const form = useForm({
  name: '',
});

const title = computed(() => formMode.value === 'create' ? t('admin.categories.create') : t('admin.categories.edit'));
const items = computed(() => props.categories?.data || []);

function applyFilters() {
  router.get(route('categories.index'), cleanQuery(filterForm.data()), { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.name = '';
  router.get(route('categories.index'), {}, { preserveState: true, replace: true });
}

function openCreateModal() {
  formMode.value = 'create';
  selectedCategory.value = null;
  form.reset();
  form.clearErrors();
  showFormModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function openEditModal(category) {
  formMode.value = 'edit';
  selectedCategory.value = category;
  form.name = category.name;
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
    form.post(route('categories.store'), options);
    return;
  }

  form.put(route('categories.update', selectedCategory.value.id), options);
}

function openDeleteDialog(category) {
  selectedCategory.value = category;
  showDeleteDialog.value = true;
}

function deleteCategory() {
  form.delete(route('categories.destroy', selectedCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedCategory.value = null;
      showToast();
    },
  });
}
</script>

<template>
  <Head :title="t('admin.categories.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('admin.categories.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader
        :title="t('admin.categories.title')"
        :description="t('admin.categories.description')"
        :count="categories.total"
      >
        <template #actions>
          <AppButton icon="fa-plus" @click="openCreateModal">{{ t('admin.categories.create') }}</AppButton>
        </template>
      </PageHeader>

      <FilterPanel>
        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <AppInput v-model="filterForm.name" :label="t('common.name')" :placeholder="t('admin.categories.search_placeholder')" />
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
                <th scope="col" class="px-4 py-3">{{ t('common.status') }}</th>
                <th scope="col" class="px-4 py-3 text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="category in items" :key="category.id">
                <td class="px-4 py-3 font-black text-[var(--color-text-primary)]">{{ category.name }}</td>
                <td class="px-4 py-3">
                  <AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <AppButton class="w-9 px-0" variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${category.name}`" @click="openEditModal(category)">
                      <span class="sr-only">{{ t('actions.edit') }}</span>
                    </AppButton>
                    <AppButton class="w-9 px-0" variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${category.name}`" @click="openDeleteDialog(category)">
                      <span class="sr-only">{{ t('actions.delete') }}</span>
                    </AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="items.length" class="divide-y divide-[var(--color-border)] lg:hidden">
          <article v-for="category in items" :key="category.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 class="font-black text-[var(--color-text-primary)]">{{ category.name }}</h2>
              </div>
              <AppBadge variant="success" icon="fa-check-circle">{{ t('admin.status.active') }}</AppBadge>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" :title="t('actions.edit')" :aria-label="`${t('actions.edit')} ${category.name}`" @click="openEditModal(category)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" :title="t('actions.delete')" :aria-label="`${t('actions.delete')} ${category.name}`" @click="openDeleteDialog(category)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState v-if="!items.length" icon="fa-tags" :title="t('admin.categories.empty')" :description="t('admin.categories.description')" />
      </section>

      <AppPagination :links="categories.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showFormModal" :title="title" size="md" :initial-focus="nameInput" @close="showFormModal = false">
      <div class="space-y-4">
        <AppInput ref="nameInput" v-model="form.name" :label="t('common.name')" :placeholder="t('admin.categories.search_placeholder')" :error="form.errors.name" required @keyup.enter="submitForm" />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="showFormModal = false">{{ t('actions.cancel') }}</AppButton>
        <AppButton icon="fa-save" :loading="form.processing" :loading-text="t('common.loading')" @click="submitForm">{{ t('admin.categories.save') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('admin.categories.delete_title')"
      :action="t('admin.categories.delete_action', { name: selectedCategory?.name || t('admin.categories.title') })"
      :consequence="t('admin.categories.delete_consequence')"
      :confirm-text="t('actions.delete')"
      :cancel-text="t('actions.cancel')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteCategory"
    />
  </AuthenticatedLayout>
</template>

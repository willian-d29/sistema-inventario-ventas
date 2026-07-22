<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppAlert from '@/Components/UI/AppAlert.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCheckbox from '@/Components/UI/AppCheckbox.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { showToast } from '@/Utils/Helper.js';

const props = defineProps({
  filters: { type: Object },
  expenses: { type: Object },
});

const selectedExpense = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteDialog = ref(false);
const nameInput = ref(null);
const { t } = useI18n();

const form = useForm({
  name: '',
  description: '',
  amount: '',
  expense_date: '',
  paid_from_cash_register: false,
});

function resetForm() {
  form.reset();
  form.clearErrors();
}

function createExpenseModal() {
  resetForm();
  showCreateModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function editExpenseModal(expense) {
  selectedExpense.value = expense;
  form.name = expense.name;
  form.description = expense.description;
  form.amount = expense.amount;
  form.expense_date = expense.expense_date;
  form.paid_from_cash_register = Boolean(expense.paid_from_cash_register);
  showEditModal.value = true;
  nextTick(() => nameInput.value?.focus?.());
}

function deleteExpenseModal(expense) {
  selectedExpense.value = expense;
  showDeleteDialog.value = true;
}

function createExpense() {
  form.post(route('expenses.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
      showToast();
    },
    onError: () => nameInput.value?.focus?.(),
  });
}

function updateExpense() {
  form.put(route('expenses.update', selectedExpense.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
      showToast();
    },
    onError: () => nameInput.value?.focus?.(),
  });
}

function deleteExpense() {
  form.delete(route('expenses.destroy', selectedExpense.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      selectedExpense.value = null;
      showToast();
    },
  });
}

function closeModal() {
  showCreateModal.value = false;
  showEditModal.value = false;
  resetForm();
}

function money(value) {
  return `S/ ${Number(value || 0).toFixed(2)}`;
}

const visibleExpenses = computed(() => props.expenses?.data || []);
const summary = computed(() => {
  const items = visibleExpenses.value;
  const total = items.reduce((sum, expense) => sum + Number(expense.amount || 0), 0);
  const cash = items
    .filter((expense) => expense.paid_from_cash_register)
    .reduce((sum, expense) => sum + Number(expense.amount || 0), 0);

  return {
    total: money(total),
    cash: money(cash),
    admin: money(total - cash),
  };
});
</script>

<template>
  <Head :title="t('expenses.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('expenses.title') }}</template>

    <div class="space-y-5 px-4">
      <PageHeader
        :title="t('expenses.title')"
        :description="t('expenses.description')"
        :count="expenses.total"
      >
        <template #actions>
          <AppButton icon="fa-plus" @click="createExpenseModal">{{ t('expenses.register_expense') }}</AppButton>
        </template>
      </PageHeader>

      <div class="grid gap-3 md:grid-cols-3">
        <StatCard :title="t('expenses.summary_total')" :value="summary.total" icon="fa-receipt" variant="info" />
        <StatCard :title="t('expenses.summary_admin')" :value="summary.admin" icon="fa-building" />
        <StatCard :title="t('expenses.summary_cash')" :value="summary.cash" icon="fa-cash-register" variant="warning" />
      </div>

      <section class="ihc-panel overflow-hidden">
        <div class="hidden overflow-x-auto lg:block">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase">
              <tr>
                <th scope="col" class="px-4 py-3">{{ t('common.date') }}</th>
                <th scope="col">{{ t('expenses.concept') }}</th>
                <th scope="col">{{ t('expenses.category') }}</th>
                <th scope="col" class="text-right">{{ t('common.amount') }}</th>
                <th scope="col">{{ t('expenses.responsible') }}</th>
                <th scope="col">{{ t('expenses.origin') }}</th>
                <th scope="col">{{ t('expenses.cash_register') }}</th>
                <th scope="col" class="text-right">{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expense in visibleExpenses" :key="expense.id">
                <td class="px-4 py-3 font-semibold">{{ expense.expense_date }}</td>
                <td>
                  <strong class="block text-[var(--color-text-primary)]">{{ expense.name }}</strong>
                  <span class="app-ui-help">{{ expense.description || t('common.no_description') }}</span>
                </td>
                <td>{{ t('common.administrative') }}</td>
                <td class="text-right font-black">{{ money(expense.amount) }}</td>
                <td>{{ expense.responsible?.name || expense.user?.name || '-' }}</td>
                <td>
                  <AppBadge
                    :variant="expense.paid_from_cash_register ? 'warning' : 'neutral'"
                    :icon="expense.paid_from_cash_register ? 'fa-cash-register' : 'fa-building'"
                  >
                    {{ expense.paid_from_cash_register ? t('expenses.paid_from_cash') : t('expenses.administrative_expense') }}
                  </AppBadge>
                </td>
                <td>{{ expense.cash_register_id ? `Caja #${expense.cash_register_id}` : '-' }}</td>
                <td class="text-right">
                  <div class="flex justify-end gap-2">
                    <AppButton variant="success" size="sm" icon="fa-pencil-alt" @click="editExpenseModal(expense)">{{ t('actions.edit') }}</AppButton>
                    <AppButton variant="danger" size="sm" icon="fa-trash-alt" @click="deleteExpenseModal(expense)">{{ t('actions.delete') }}</AppButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-[var(--color-border)] lg:hidden">
          <article v-for="expense in visibleExpenses" :key="expense.id" class="p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 class="font-black text-[var(--color-text-primary)]">{{ expense.name }}</h2>
                <p class="app-ui-help">{{ expense.expense_date }} · {{ t('common.administrative') }}</p>
              </div>
              <strong>{{ money(expense.amount) }}</strong>
            </div>
            <div class="mt-3">
              <AppBadge
                :variant="expense.paid_from_cash_register ? 'warning' : 'neutral'"
                :icon="expense.paid_from_cash_register ? 'fa-cash-register' : 'fa-building'"
              >
                {{ expense.paid_from_cash_register ? t('expenses.paid_from_cash') : t('expenses.administrative_expense') }}
              </AppBadge>
            </div>
            <p class="mt-2 text-sm text-[var(--color-text-secondary)]">{{ expense.description || t('common.no_description') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <AppButton variant="success" size="sm" icon="fa-pencil-alt" @click="editExpenseModal(expense)">{{ t('actions.edit') }}</AppButton>
              <AppButton variant="danger" size="sm" icon="fa-trash-alt" @click="deleteExpenseModal(expense)">{{ t('actions.delete') }}</AppButton>
            </div>
          </article>
        </div>

        <AppEmptyState
          v-if="!visibleExpenses.length"
          :title="t('empty.no_expenses')"
          :description="t('expenses.create_first')"
          icon="fa-receipt"
        />
      </section>

      <AppPagination :links="expenses.links" :label="t('pagination.label')" />
    </div>

    <AppModal :show="showCreateModal" :title="t('expenses.register_expense')" size="lg" @close="closeModal">
      <div class="grid gap-4 md:grid-cols-2">
        <AppInput ref="nameInput" v-model="form.name" :label="t('expenses.concept')" required :placeholder="t('expenses.create_example')" :error="form.errors.name" />
        <AppInput v-model="form.amount" :label="t('cash.amount')" type="number" min="0.01" step="0.01" required :error="form.errors.amount" />
        <AppInput v-model="form.expense_date" :label="t('common.date')" type="date" required :error="form.errors.expense_date" />
        <AppCheckbox
          v-model="form.paid_from_cash_register"
          :label="t('expenses.paid_question')"
          :help-text="t('expenses.paid_help')"
          :error="form.errors.paid_from_cash_register"
        />
        <AppAlert class="md:col-span-2" variant="info" :message="t('expenses.admin_help')" />
        <AppAlert
          v-if="form.paid_from_cash_register"
          class="md:col-span-2"
          variant="warning"
          :title="t('expenses.cash_impact')"
          :message="t('expenses.cash_impact_message')"
        />
        <AppTextarea v-model="form.description" class="md:col-span-2" :label="t('common.description')" :error="form.errors.description" />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="closeModal">{{ t('actions.cancel') }}</AppButton>
        <AppButton :loading="form.processing" @click="createExpense">{{ t('actions.save') }}</AppButton>
      </template>
    </AppModal>

    <AppModal :show="showEditModal" :title="t('expenses.edit_expense')" size="lg" @close="closeModal">
      <div class="grid gap-4 md:grid-cols-2">
        <AppInput ref="nameInput" v-model="form.name" :label="t('expenses.concept')" required :error="form.errors.name" />
        <AppInput v-model="form.amount" :label="t('cash.amount')" type="number" min="0.01" step="0.01" required :error="form.errors.amount" />
        <AppInput v-model="form.expense_date" :label="t('common.date')" type="date" required :error="form.errors.expense_date" />
        <AppCheckbox
          v-model="form.paid_from_cash_register"
          :label="t('expenses.paid_from_cash')"
          :help-text="t('expenses.locked_cash_help')"
          disabled
        />
        <AppTextarea v-model="form.description" class="md:col-span-2" :label="t('common.description')" :error="form.errors.description" />
      </div>
      <template #footer>
        <AppButton variant="secondary" :disabled="form.processing" @click="closeModal">{{ t('actions.cancel') }}</AppButton>
        <AppButton :loading="form.processing" @click="updateExpense">{{ t('actions.save_changes') }}</AppButton>
      </template>
    </AppModal>

    <ConfirmDialog
      :show="showDeleteDialog"
      :title="t('expenses.delete_title')"
      :action="t('expenses.delete_action', { name: selectedExpense?.name || t('expenses.title') })"
      :consequence="t('expenses.delete_consequence')"
      :confirm-text="t('products.confirm_delete')"
      :loading="form.processing"
      @cancel="showDeleteDialog = false"
      @confirm="deleteExpense"
    />
  </AuthenticatedLayout>
</template>

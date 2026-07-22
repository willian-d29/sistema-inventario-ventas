<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { cleanQuery } from '@/Utils/Helper.js';

const props = defineProps({
  rows: Array,
  filters: Object,
  cashiers: Array,
  paymentMethods: Array,
  statuses: Array,
});

const filterForm = useForm({
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  cashier_id: props.filters?.cashier_id || '',
  method: props.filters?.method || '',
  status: props.filters?.status || '',
  sale: props.filters?.sale || '',
  document: props.filters?.document || '',
});

function applyFilters() {
  router.get(route('cash-registers.reconciliation'), cleanQuery(filterForm.data()), { preserveScroll: true, preserveState: true, replace: true });
}

function clearFilters() {
  Object.keys(filterForm.data()).forEach((key) => { filterForm[key] = ''; });
  router.get(route('cash-registers.reconciliation'), {}, { preserveScroll: true, preserveState: true, replace: true });
}

function money(value) {
  if (value === null || value === undefined) return '-';
  return `S/ ${Number(value || 0).toFixed(2)}`;
}

function statusLabel(status) {
  return props.statuses?.find((item) => item.value === status)?.label || status;
}

function methodLabel(method) {
  return props.paymentMethods?.find((item) => item.value === method)?.label || method || '-';
}
</script>

<template>
  <Head title="Conciliación de caja" />
  <AuthenticatedLayout>
    <template #breadcrumb>Conciliación de caja</template>

    <div class="space-y-6 px-4">
      <section class="ihc-panel p-5">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
          <div>
            <h1 class="ihc-section-title">Conciliación ventas/caja</h1>
            <p class="app-ui-help">Revisión administrativa entre pagos y movimientos de caja. Las correcciones se realizan mediante ajustes auditables.</p>
          </div>
          <span class="app-ui-badge app-ui-badge-neutral app-ui-badge-pill">{{ rows?.length || 0 }} registro(s)</span>
        </div>

        <form class="ihc-filter-grid" @submit.prevent="applyFilters">
          <label class="ihc-label">Desde
            <input v-model="filterForm.date_from" type="date" class="ihc-field" />
          </label>
          <label class="ihc-label">Hasta
            <input v-model="filterForm.date_to" type="date" class="ihc-field" />
          </label>
          <label class="ihc-label">Cajero
            <select v-model="filterForm.cashier_id" class="ihc-field">
              <option value="">Todos</option>
              <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">{{ cashier.name }}</option>
            </select>
          </label>
          <label class="ihc-label">Método
            <select v-model="filterForm.method" class="ihc-field">
              <option value="">Todos</option>
              <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
            </select>
          </label>
          <label class="ihc-label">Estado
            <select v-model="filterForm.status" class="ihc-field">
              <option value="">Todos</option>
              <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
          </label>
          <label class="ihc-label">Venta
            <input v-model="filterForm.sale" class="ihc-field" placeholder="ID o documento" />
          </label>
          <label class="ihc-label">Documento
            <input v-model="filterForm.document" class="ihc-field" placeholder="NV01-000001" />
          </label>
          <div class="flex items-end gap-2">
            <AppButton class="flex-1" type="submit" icon="fa-filter">Filtrar</AppButton>
            <AppButton type="button" variant="secondary" icon="fa-eraser" @click="clearFilters">Limpiar</AppButton>
          </div>
        </form>
      </section>

      <section class="ihc-panel p-5">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase">
              <tr>
                <th scope="col" class="py-2">Venta</th>
                <th scope="col">Documento</th>
                <th scope="col">Cajero</th>
                <th scope="col">Método</th>
                <th scope="col" class="text-right">Payment</th>
                <th scope="col" class="text-right">Movimiento</th>
                <th scope="col" class="text-right">Diferencia</th>
                <th scope="col">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="`${row.sale_id}-${row.method}-${row.status}-${row.movement_amount}`">
                <td class="py-2 font-semibold">{{ row.sale_id || '-' }}</td>
                <td>{{ row.document || '-' }}</td>
                <td>{{ row.cashier || '-' }}</td>
                <td>{{ methodLabel(row.method) }}</td>
                <td class="text-right">{{ money(row.payment_amount) }}</td>
                <td class="text-right">{{ money(row.movement_amount) }}</td>
                <td class="text-right">{{ money(row.difference) }}</td>
                <td>
                  <span class="font-semibold text-[var(--color-text-primary)]">{{ statusLabel(row.status) }}</span>
                </td>
              </tr>
              <tr v-if="!rows?.length">
                <td colspan="8" class="py-6 text-center text-[var(--color-text-muted)]">Sin registros para los filtros seleccionados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

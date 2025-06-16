<template>
  <AuthenticatedLayout>
    <Head title="Reportes de Ventas" />

    <div class="p-6 space-y-6">
      <h1 class="text-3xl font-bold text-gray-800">Panel de Reportes</h1>

      <!-- Resumen de métricas -->
      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div class="p-5 bg-white rounded shadow border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-sm text-gray-500">Ventas Totales</h3>
              <p class="text-2xl font-bold text-gray-700">S/. {{ summary.totalVentas }}</p>
            </div>
            <i class="fas fa-coins text-blue-500 text-3xl"></i>
          </div>
        </div>
        <div class="p-5 bg-white rounded shadow border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-sm text-gray-500">Ganancias</h3>
              <p class="text-2xl font-bold text-gray-700">S/. {{ summary.totalGanancias }}</p>
            </div>
            <i class="fas fa-chart-line text-green-500 text-3xl"></i>
          </div>
        </div>
        <div class="p-5 bg-white rounded shadow border-l-4 border-indigo-500">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-sm text-gray-500">Órdenes</h3>
              <p class="text-2xl font-bold text-gray-700">{{ summary.totalOrdenes }}</p>
            </div>
            <i class="fas fa-file-invoice-dollar text-indigo-500 text-3xl"></i>
          </div>
        </div>
      </div>

      <!-- Exportar -->
      <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Exportar Reporte</h2>
        <div class="flex gap-4">
          <a
            :href="route('reports.ventas.pdf')"
            target="_blank"
            class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded inline-flex items-center"
          >
            <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
          </a>
          <a
            :href="route('reports.ventas.excel')"
            target="_blank"
            class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded inline-flex items-center"
          >
            <i class="fas fa-file-excel mr-2"></i> Descargar Excel
          </a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  summary: {
    type: Object,
    default: () => ({
      totalVentas: 0,
      totalGanancias: 0,
      totalOrdenes: 0,
    }),
  },
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Panel de Reportes" />

    <div class="p-6 space-y-8">
      <h1 class="text-3xl font-bold text-gray-800"> Reportes de Ventas</h1>

      <!-- Tarjetas de resumen -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="bg-white shadow-md border-l-4 border-blue-500 rounded-lg p-5 flex justify-between items-center">
          <div>
            <p class="text-gray-500 text-sm">Ventas Totales</p>
            <p class="text-2xl font-semibold text-gray-800">S/. {{ summary.totalVentas.toFixed(2) }}</p>
          </div>
          <i class="fas fa-coins text-blue-500 text-3xl"></i>
        </div>

        <div class="bg-white shadow-md border-l-4 border-green-500 rounded-lg p-5 flex justify-between items-center">
          <div>
            <p class="text-gray-500 text-sm">Ganancia Total</p>
            <p class="text-2xl font-semibold text-gray-800">S/. {{ summary.totalGanancias.toFixed(2) }}</p>
          </div>
          <i class="fas fa-chart-line text-green-500 text-3xl"></i>
        </div>

        <div class="bg-white shadow-md border-l-4 border-indigo-500 rounded-lg p-5 flex justify-between items-center">
          <div>
            <p class="text-gray-500 text-sm">Órdenes Registradas</p>
            <p class="text-2xl font-semibold text-gray-800">{{ summary.totalOrdenes }}</p>
          </div>
          <i class="fas fa-file-invoice-dollar text-indigo-500 text-3xl"></i>
        </div>
      </div>

      <!-- Exportación -->
<div class="mt-10">
  <h2 class="text-2xl font-bold text-gray-700 mb-4 flex items-center gap-2">
    <i class="fas fa-cloud-download-alt text-indigo-500 text-xl"></i> Exportar Reportes
  </h2>

  <div class="flex flex-wrap gap-4">
    <!-- Botón PDF -->
    <a
      :href="route('reports.ventas.pdf')"
      target="_blank"
      class="flex items-center gap-2 bg-red-600 text-white px-5 py-2.5 rounded-lg shadow-md transition-all duration-300 hover:bg-red-700 hover:scale-105 hover:shadow-lg"
    >
      <i class="fas fa-file-pdf text-lg"></i>
      <span class="font-semibold">Descargar PDF</span>
    </a>

    <!-- Botón Excel -->
    <a
      :href="route('reports.ventas.excel')"
      target="_blank"
      class="flex items-center gap-2 bg-green-600 text-white px-5 py-2.5 rounded-lg shadow-md transition-all duration-300 hover:bg-green-700 hover:scale-105 hover:shadow-lg"
    >
      <i class="fas fa-file-excel text-lg"></i>
      <span class="font-semibold">Descargar Excel</span>
    </a>
  </div>
</div>




    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  summary: {
    type: Object,
    required: true,
    default: () => ({
      totalVentas: 0,
      totalGanancias: 0,
      totalOrdenes: 0
    })
  }
})
</script>

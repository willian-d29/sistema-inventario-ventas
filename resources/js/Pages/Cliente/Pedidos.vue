<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 px-4 py-10">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-indigo-800 mb-8 animate-fade-in text-center">Mis Pedidos</h1>

      <div v-if="orders.data.length">
        <div
          v-for="order in orders.data"
          :key="order.id"
          class="bg-white rounded-2xl shadow-md mb-6 p-6 transition-transform transform hover:scale-[1.01]"
        >
          <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-semibold text-gray-700">
              <i class="las la-box text-indigo-600 mr-1"></i> Pedido #{{ order.id }}
            </h2>
            <span
              class="px-3 py-1 text-sm rounded-full font-medium"
              :class="order.status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
            >
              {{ order.status }}
            </span>
          </div>

          <p class="text-sm text-gray-500 mb-4">
            <i class="las la-calendar-alt mr-1"></i> Fecha: {{ formatDate(order.created_at) }}
          </p>

          <ul class="divide-y divide-gray-200 mb-4">
            <li
              v-for="item in order.items"
              :key="item.id"
              class="flex justify-between py-2 text-sm"
            >
              <span>
                {{ item.product?.name ?? 'Producto eliminado' }} (x{{ parseFloat(item.quantity) }})
              </span>
              <span>S/. {{ parseFloat(item.total).toFixed(2) }}</span>
            </li>
          </ul>

          <div class="flex flex-wrap justify-between items-center gap-2 mt-4">
            <p class="font-bold text-indigo-700">Total: S/. {{ parseFloat(order.total).toFixed(2) }}</p>
            <div class="flex flex-wrap gap-2">
              <a
                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700"
                :href="`/order/${order.id}/pdf`"
                target="_blank"
              >
                <i class="las la-file-download mr-1"></i> PDF
              </a>
              <button
                @click="openModal(order.id)"
                class="px-4 py-2 text-sm font-semibold text-white bg-red-500 rounded hover:bg-red-600"
              >
                <i class="las la-trash-alt mr-1"></i> Eliminar
              </button>
            </div>
          </div>
        </div>

        <!-- Paginación -->
        <div class="flex justify-center items-center mt-8 gap-2">
          <button
            @click="goToPage(orders.prev_page_url)"
            :disabled="!orders.prev_page_url"
            class="px-4 py-2 bg-indigo-500 text-white rounded disabled:opacity-50"
          >
            Anterior
          </button>
          <button
            @click="goToPage(orders.next_page_url)"
            :disabled="!orders.next_page_url"
            class="px-4 py-2 bg-indigo-500 text-white rounded disabled:opacity-50"
          >
            Siguiente
          </button>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 font-semibold mt-10">
        <i class="las la-box-open text-3xl text-gray-400 mb-2"></i>
        <p>Aún no tienes pedidos registrados.</p>
      </div>
    </div>

    <!-- Modal de Confirmación -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-xl shadow-xl p-6 w-[90%] max-w-md animate-fade-in">
        <h2 class="text-lg font-bold mb-2 text-gray-800">¿Eliminar Pedido?</h2>
        <p class="text-sm text-gray-600 mb-4">
          Esta acción es irreversible. ¿Deseas eliminar el pedido #{{ selectedOrderId }}?
        </p>
        <div class="flex justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
          >
            Cancelar
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700"
          >
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { defineProps } from 'vue'

const props = defineProps({
  orders: Object,
})

const showModal = ref(false)
const selectedOrderId = ref(null)

const openModal = (id) => {
  selectedOrderId.value = id
  showModal.value = true
}

const confirmDelete = () => {
  router.delete(`/cliente/pedidos/${selectedOrderId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      selectedOrderId.value = null
    },
    onError: () => {
      alert('No se pudo eliminar el pedido')
    }
  })
}

const goToPage = (url) => {
  if (url) router.visit(url)
}

const formatDate = (fecha) => {
  return new Date(fecha).toLocaleDateString('es-PE', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<style>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in {
  animation: fade-in 0.5s ease-in-out;
}
</style>

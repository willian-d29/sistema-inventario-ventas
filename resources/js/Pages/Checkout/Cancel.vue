<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-red-100 to-gray-100 px-4">
    <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-md w-full animate-fade-in">
      <XCircleIcon class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h1 class="text-3xl font-bold text-red-700 mb-2">Pago cancelado</h1>
      <p class="text-gray-700 mb-6">Parece que cancelaste el pago o hubo un error. Puedes volver e intentarlo nuevamente.</p>
      <Link href="/" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition">
        Volver a la tienda
      </Link>
    </div>
  </div>
</template>

<script setup>
import { XCircleIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

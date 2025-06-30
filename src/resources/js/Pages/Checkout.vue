<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-blue-100 py-10 px-4 font-sans">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow-xl animate-fade-in relative">
      <h1 class="text-3xl font-bold mb-6 text-blue-700 text-center">Resumen de tu pedido</h1>

      <div v-if="cart.length">
        <ul class="space-y-4 mb-6">
          <li v-for="item in cart" :key="item.id" class="flex justify-between items-center border-b pb-3">
            <div>
              <h3 class="font-semibold text-gray-800">{{ item.name }}</h3>
              <p class="text-sm text-gray-500">Cantidad: {{ item.quantity }}</p>
            </div>
            <p class="text-blue-700 font-bold">S/. {{ (item.quantity * parseFloat(item.selling_price)).toFixed(2) }}</p>
          </li>
        </ul>

        <p class="text-right text-xl font-extrabold text-green-600">
          Total a pagar: S/. {{ cartTotal }}
        </p>

        <button
          @click="processPayment"
          class="mt-6 w-full bg-gradient-to-r from-green-500 to-blue-600 text-white py-3 rounded-lg font-bold shadow hover:from-blue-600 hover:to-green-500 transition-all duration-300 flex items-center justify-center gap-2"
        >
          <CreditCardIcon class="w-6 h-6" /> Confirmar y pagar
        </button>
      </div>

      <div v-else class="text-center text-gray-500 font-semibold mt-8">
        Tu carrito está vacío.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { CreditCardIcon } from '@heroicons/vue/24/outline'
import { loadStripe } from '@stripe/stripe-js'
import axios from 'axios'

const cart = ref([])
onMounted(() => {
  const saved = localStorage.getItem('cart')
  if (saved) cart.value = JSON.parse(saved)
})

const cartTotal = computed(() =>
  cart.value.reduce(
    (sum, item) => sum + item.quantity * parseFloat(item.selling_price),
    0
  ).toFixed(2)
)

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY)

const processPayment = async () => {
  const stripe = await stripePromise

  try {
    const response = await axios.post('/checkout/stripe', {
      items: cart.value
    })

    await stripe.redirectToCheckout({ sessionId: response.data.id })
  } catch (error) {
    console.error("Error al procesar el pago:", error)
    alert("Hubo un problema al iniciar el pago. Intenta nuevamente.")
  }
}
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

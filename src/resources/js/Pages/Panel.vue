<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
  <AuthenticatedLayout>
    <template #breadcrumb>
      <h1 class="text-white text-lg font-semibold uppercase">Panel del Vendedor</h1>
    </template>

    <template #headerState>
      <div class="text-white text-xl font-bold">Bienvenido, gestiona tu trabajo desde aquí</div>
    </template>

    <div class="p-6 space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <Link v-if="user.role === 'vendedor'" href="/sistema/pos" class="bg-emerald-600 hover:bg-emerald-700 text-white p-5 rounded-lg shadow">
          🛒 Punto de Venta
        </Link>
        <Link v-if="user.role === 'vendedor'" href="/sistema/orders" class="bg-indigo-600 hover:bg-indigo-700 text-white p-5 rounded-lg shadow">
          📦 Órdenes
        </Link>
        <Link v-if="user.role === 'vendedor'" href="/sistema/transactions" class="bg-blue-600 hover:bg-blue-700 text-white p-5 rounded-lg shadow">
          💰 Transacciones
        </Link>
        <Link v-if="user.role === 'vendedor'" href="/sistema/products" class="bg-teal-600 hover:bg-teal-700 text-white p-5 rounded-lg shadow">
          📦 Productos
        </Link>
        <Link v-if="user.role === 'vendedor'" href="/sistema/expenses" class="bg-pink-600 hover:bg-pink-700 text-white p-5 rounded-lg shadow">
          📉 Gastos
        </Link>
        <Link v-if="user.role === 'vendedor'" href="/sistema/profile" class="bg-gray-700 hover:bg-gray-800 text-white p-5 rounded-lg shadow">
          ⚙️ Perfil
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

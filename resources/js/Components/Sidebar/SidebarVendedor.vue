<template>
  <nav class="md:left-0 md:block md:fixed md:top-0 md:bottom-0 md:overflow-y-auto md:flex-row md:flex-nowrap md:overflow-hidden shadow-xl bg-white flex flex-wrap items-center justify-between relative md:w-64 z-10 py-4 px-6">
    <div class="md:flex-col md:items-stretch md:min-h-full md:flex-nowrap px-0 flex flex-wrap items-center justify-between w-full mx-auto">
      <!-- Brand -->
      <Link
        class="md:block text-left md:pb-2 text-blueGray-600 mr-0 inline-block whitespace-nowrap text-sm uppercase font-bold p-4 px-0"
        href="/"
      >
        <ApplicationLogo color="black" class="hidden sm:block" />
      </Link>

      <!-- Collapse Navigation -->
      <div class="md:flex md:flex-col md:items-stretch md:opacity-100 md:relative md:mt-4 md:shadow-none shadow absolute top-0 left-0 right-0 z-40 overflow-y-auto overflow-x-hidden h-auto items-center flex-1 rounded">
        <!-- Divider -->
        <hr class="my-4 md:min-w-full" />

        <!-- Navigation -->
        <ul class="md:flex-col md:min-w-full flex flex-col list-none">
          <SidebarItem
            name="Punto de Venta"
            routeName="carts.index"
            icon="fas fa-cash-register"
          />
          <SidebarItem
            name="Órdenes"
            routeName="orders.index"
            icon="fas fa-box"
          />
          <SidebarItem
            name="Transacciones"
            routeName="transactions.index"
            icon="fas fa-exchange-alt"
          />
          <SidebarItem
            name="Productos"
            routeName="products.index"
            icon="fas fa-boxes"
          />
          <SidebarItem
            name="Gastos"
            routeName="expenses.index"
            icon="fas fa-money-bill-wave"
          />
          <SidebarItem
            name="Perfil"
            routeName="profile.edit"
            icon="fas fa-user-cog"
          />
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
import SidebarItem from "@/Components/Sidebar/SidebarItem.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";

export default {
  components: {
    SidebarItem,
    ApplicationLogo,
    Link,
  },
};
</script>

<script setup>
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import AdminNavbar from "@/Components/Navbars/AdminNavbar.vue";
import FooterAdmin from "@/Components/Footers/FooterAdmin.vue";
import { Notification, Notivue, pastelTheme } from "notivue";
import { router } from "@inertiajs/vue3";

// Función segura para logout con POST
function logout() {
    router.post('/logout');
}
</script>

<template>
    <Notivue v-slot="item">
        <Notification :item="item" :theme="pastelTheme" />
    </Notivue>

    <div>
        <!-- Menú lateral -->
        <Sidebar />

        <!-- Contenido principal -->
        <div class="relative md:ml-64 bg-blueGray-100">
            <AdminNavbar>
                <template #breadcrumb>
                    <slot name="breadcrumb" />
                </template>
            </AdminNavbar>

            <!-- 👇 Botón logout fuera del slot, siempre visible -->
            <div class="flex justify-end px-6 py-4 bg-white shadow">
                <button
                    @click="logout"
                    class="text-white bg-red-600 hover:bg-red-700 px-4 py-2 rounded"
                >
                    Cerrar sesión
                </button>
            </div>

            <!-- Header -->
            <div class="relative bg-emerald-600 md:pt-32 pb-32 pt-12">
                <div class="px-4 md:px-10 mx-auto w-full">
                    <div>
                        <slot name="headerState" />
                    </div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="px-4 md:px-10 mx-auto w-full -m-24">
                <slot />
                <FooterAdmin />
            </div>
        </div>
    </div>
</template>

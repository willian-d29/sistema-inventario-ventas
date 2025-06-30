<script setup>
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import SidebarVendedor from "@/Components/Sidebar/SidebarVendedor.vue";
import AdminNavbar from "@/Components/Navbars/AdminNavbar.vue";
import FooterAdmin from "@/Components/Footers/FooterAdmin.vue";
import { Notification, Notivue, pastelTheme } from "notivue";
import { router, usePage } from "@inertiajs/vue3";

// Accedemos al usuario autenticado
const user = usePage().props.auth.user;

function logout() {
    router.post('/logout');
}
</script>

<template>
    <Notivue v-slot="item">
        <Notification :item="item" :theme="pastelTheme" />
    </Notivue>

    <div>
        <!-- Menú lateral dinámico -->
        <component :is="user.role === 'vendedor' ? SidebarVendedor : Sidebar" />

        <!-- Contenido principal -->
        <div class="relative md:ml-64 bg-blueGray-100">
            <AdminNavbar>
                <template #breadcrumb>
                    <slot name="breadcrumb" />
                </template>
            </AdminNavbar>

            

            <!-- Header -->
            <div class="relative bg-emerald-600 md:pt-32 pb-32 pt-12">
                <div class="px-4 md:px-10 mx-auto w-full">
                    <slot name="headerState" />
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

<script setup>
import { ref, computed } from "vue";
import GuestNavbar from "@/Components/Navbars/GuestNavbar.vue";
import FooterComponent from "@/Components/Footers/Footer.vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import ContactForm from "@/Components/ContactForm.vue";
import banner from "@/assets/img/banner.avif";

const props = defineProps({
    pageTitle: String,
    products: Array
});

const page = usePage();
const authUser = page.props.auth.user;

const selectedCategory = ref("");
const minPrice = ref("");
const maxPrice = ref("");
const selectedProduct = ref(null);
const cart = ref([]);

// Categorías únicas
const categories = computed(() => {
    const set = new Set();
    props.products.forEach(p => p.category?.name && set.add(p.category.name));
    return Array.from(set);
});

// Productos filtrados
const filteredProducts = computed(() => {
    return props.products.filter(p => {
        const price = parseFloat(p.selling_price);
        const matchCategory = !selectedCategory.value || p.category?.name === selectedCategory.value;
        const matchMin = minPrice.value === "" || (!isNaN(minPrice.value) && price >= Math.max(0, parseFloat(minPrice.value)));
        const matchMax = maxPrice.value === "" || (!isNaN(maxPrice.value) && price <= parseFloat(maxPrice.value));
        return matchCategory && matchMin && matchMax;
    });
});

// Modal
const openProductModal = (product) => selectedProduct.value = product;
const closeModal = () => selectedProduct.value = null;

// Agregar al carrito
const addToCart = (product) => {
    const existing = cart.value.find(p => p.id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({ ...product, quantity: 1 });
    }
};

// Total
const cartTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity * parseFloat(item.selling_price), 0).toFixed(2);
});
</script>

<template>
    <Head :title="pageTitle" />

    <div>
        <GuestNavbar />

        <main>
            <!-- Banner -->
            <div class="relative pt-16 pb-32 flex content-center items-center justify-center min-h-screen-75">
                <div class="absolute top-0 w-full h-full bg-center bg-cover" :style="{ backgroundImage: `url(${banner})` }">
                    <span class="w-full h-full absolute opacity-75 bg-black"></span>
                </div>
                <div class="container relative mx-auto text-center text-white">
                    <h1 class="text-4xl font-bold">Tu historia comienza con nosotros</h1>
                    <p class="mt-4 text-lg">Descubre nuestros productos y servicios que potencian tu negocio.</p>
                </div>
            </div>

            <!-- Productos -->
            <section class="bg-white py-16">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-semibold text-center mb-10">Nuestros Productos</h2>

                    <!-- Filtros -->
                    <div class="flex flex-wrap justify-center gap-4 mb-10">
                        <select v-model="selectedCategory" class="p-2 border rounded text-gray-700">
                            <option value="">Todas las categorías</option>
                            <option v-for="(cat, idx) in categories" :key="idx" :value="cat">{{ cat }}</option>
                        </select>
                        <input v-model.number="minPrice" type="number" min="0" placeholder="Precio mínimo" class="p-2 border rounded w-36" />
                        <input v-model.number="maxPrice" type="number" min="0" placeholder="Precio máximo" class="p-2 border rounded w-36" />
                    </div>

                    <!-- Cards -->
                    <div class="grid gap-6 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="cursor-pointer bg-white shadow rounded-lg overflow-hidden border hover:shadow-md transition"
                            @click="openProductModal(product)"
                        >
                            <img :src="product.photo || '/images/no-image.png'" :alt="product.name" class="w-full h-36 object-cover" />
                            <div class="p-3">
                                <h3 class="text-sm font-bold text-gray-800 truncate">{{ product.name }}</h3>
                                <p class="text-emerald-600 font-semibold mt-1 text-sm">S/. {{ product.selling_price }}</p>
                                <p class="text-xs text-gray-400">{{ product.category?.name || 'N/A' }}</p>
                                <button
                                    v-if="authUser && authUser.role === 'cliente'"
                                    class="w-full mt-2 text-xs text-white bg-emerald-600 hover:bg-emerald-700 rounded px-2 py-1"
                                    @click.stop="addToCart(product)"
                                >
                                    Agregar al carrito
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modal -->
            <div v-if="selectedProduct" class="fixed z-50 inset-0 bg-black bg-opacity-50 flex justify-center items-center">
                <div class="bg-white rounded-lg max-w-md w-full p-6 relative">
                    <button class="absolute top-2 right-3 text-gray-600" @click="closeModal">✖</button>
                    <img :src="selectedProduct.photo || '/images/no-image.png'" class="w-full h-40 object-cover rounded mb-4" />
                    <h3 class="text-xl font-bold mb-2">{{ selectedProduct.name }}</h3>
                    <p class="text-gray-600 text-sm mb-2">{{ selectedProduct.description }}</p>
                    <p class="text-sm text-gray-500">Categoría: {{ selectedProduct.category?.name }}</p>
                    <p class="text-emerald-700 font-semibold mt-2">S/. {{ selectedProduct.selling_price }}</p>
                    <p class="text-sm mt-1">Stock: {{ selectedProduct.quantity }}</p>
                </div>
            </div>

            <!-- Carrito -->
            <section class="bg-gray-100 py-10">
                <div class="container mx-auto">
                    <h2 class="text-2xl font-semibold mb-4">Carrito de Compras</h2>
                    <div v-if="cart.length === 0" class="text-gray-500">Tu carrito está vacío.</div>
                    <div v-else>
                        <ul class="space-y-2">
                            <li v-for="item in cart" :key="item.id" class="flex justify-between items-center bg-white p-2 rounded shadow">
                                <span>{{ item.name }} x{{ item.quantity }}</span>
                                <span>S/. {{ (item.quantity * parseFloat(item.selling_price)).toFixed(2) }}</span>
                            </li>
                        </ul>
                        <p class="mt-4 font-bold text-right">Total: S/. {{ cartTotal }}</p>
                    </div>
                </div>
            </section>

            <!-- Contacto -->
            <section class="pb-20 bg-blueGray-800">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-4xl font-semibold text-white mb-4">Contáctanos</h2>
                    <p class="text-blueGray-400 mb-8">¿Tienes preguntas o deseas cotizar? Escríbenos.</p>
                    <div class="flex justify-center">
                        <div class="w-full lg:w-6/12">
                            <div class="bg-blueGray-200 p-6 rounded-lg shadow">
                                <ContactForm />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <FooterComponent />
    </div>
</template>

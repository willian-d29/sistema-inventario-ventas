<template>
  <Head :title="pageTitle" />

  <div class="bg-gray-100 min-h-screen font-sans">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-blue-600 to-indigo-600 shadow">
      <div class="container mx-auto max-w-screen-xl px-4 py-3 flex items-center justify-between">
        <Link href="/">
          <img src="/logo.png" alt="Logo" class="h-8"/>
        </Link>
        <ul class="hidden md:flex items-center space-x-6 text-white">
          <li><Link href="#" class="hover:text-gray-200">Más vendidos</Link></li>
          <li><Link href="#" class="hover:text-gray-200">Con descuentos</Link></li>
          <li><Link href="#" class="hover:text-gray-200">Lo nuevo</Link></li>
          <li class="relative">
            <button class="flex items-center hover:text-gray-200">
              Categoría <ChevronDownIcon class="w-4 h-4 ml-1"/>
            </button>
            <!-- dropdown aquí -->
          </li>
        </ul>
        <div class="flex-1 px-4">
          <div class="relative max-w-md mx-auto">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar productos..."
              class="w-full pl-4 pr-10 py-2 rounded-full text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300"
            />
            <MagnifyingGlassIcon class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"/>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <button @click="showCart = true" class="relative text-white hover:text-gray-200">
            <ShoppingCartIcon class="w-6 h-6"/>
            <span v-if="cart.length" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1">
              {{ cart.length }}
            </span>
          </button>
          <div v-if="authUser" class="flex items-center space-x-2 text-white">
            <img
              v-if="authUser.photo"
              :src="`/storage/users/${authUser.photo}`"
              alt="Avatar"
              class="w-8 h-8 rounded-full border-2 border-white"
            />
            <span>{{ authUser.name }}</span>
            <button @click="logout" class="text-sm hover:underline">Cerrar sesión</button>
          </div>
          <div v-else class="space-x-2">
            <Link href="/login" class="text-white hover:text-gray-200 text-sm">Iniciar sesión</Link>
            <Link href="/register" class="text-white hover:text-gray-200 text-sm">Registrarse</Link>
          </div>
        </div>
      </div>
    </nav>

    <div class="container mx-auto max-w-screen-xl px-4 py-8 flex flex-col lg:flex-row gap-8">
      <!-- Sidebar -->
     

      <!-- Contenido principal -->
      <section class="flex-1 space-y-6">
        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl shadow flex flex-wrap items-center gap-4">
          <select
            v-model="selectedCategory"
            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
          >
            <option value="">Todas las categorías</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <input
            v-model.number="minPrice"
            type="number"
            placeholder="Mín precio"
            class="w-24 border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
            min="0"
          />
          <input
            v-model.number="maxPrice"
            type="number"
            placeholder="Máx precio"
            class="w-24 border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            min="0"
          />
        </div>

        <!-- Cabecera de orden y vista -->
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-2 text-gray-700">
            <ArrowsUpDownIcon class="w-6 h-6"/>
            <span class="text-lg font-semibold">Más relevantes</span>
            <ChevronDownIcon class="w-5 h-5"/>
          </div>
          <div class="flex items-center space-x-4">
            <button @click="viewMode = 'grid'" :class="viewMode==='grid' ? 'text-blue-600' : 'text-gray-400'">
              <Squares2X2Icon class="w-6 h-6"/>
            </button>
            <button @click="viewMode = 'list'" :class="viewMode==='list' ? 'text-blue-600' : 'text-gray-400'">
              <Bars3Icon class="w-6 h-6"/>
            </button>
          </div>
        </div>

        <!-- Productos -->
        <div
          :class="viewMode==='grid'
            ? 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-4'
            : 'flex flex-col space-y-4'"
        >
          <article
            v-for="product in filteredProducts"
            :key="product.id"
            :class="viewMode==='grid'
              ? 'bg-white rounded-xl shadow overflow-hidden text-center text-sm'
              : 'bg-white rounded-xl shadow p-4 flex items-center text-sm'"
          >
            <div v-if="viewMode==='grid'" class="aspect-[4/5] overflow-hidden">
              <img
                :src="product.photo || '/images/no-image.png'"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
            </div>
            <div v-else class="h-16 w-16 overflow-hidden flex-shrink-0 rounded-lg">
              <img
                :src="product.photo || '/images/no-image.png'"
                :alt="product.name"
                class="w-full h-full object-cover"
              />
            </div>
            <div class="p-2 flex-1 flex flex-col justify-between">
              <div>
                <h4 class="font-medium text-gray-800 truncate">{{ product.name }}</h4>
                <p class="text-emerald-600 font-semibold mt-1">S/. {{ product.selling_price }}</p>
                <p class="text-gray-500">{{ product.category?.name || 'N/A' }}</p>
              </div>
              <button
                class="mt-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded px-2 py-1"
                @click.stop="addToCart(product)"
              >
                Agregar
              </button>
            </div>
          </article>
        </div>
      </section>
    </div>

    <FooterComponent />

    <!-- Carrito Drawer -->
    <div v-if="showCart" class="fixed inset-0 z-50 flex">
      <div class="absolute inset-0 bg-black/50" @click="showCart = false"></div>
      <div class="relative ml-auto w-full sm:w-80 bg-white shadow-xl p-6 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Tu Carrito</h2>
          <button @click="showCart = false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>
        <ul class="space-y-4">
          <li
            v-for="item in cart"
            :key="item.id"
            class="flex justify-between items-center"
          >
            <div class="flex items-center space-x-3">
              <img
                :src="item.photo || '/images/no-image.png'"
                alt=""
                class="h-12 w-12 object-cover rounded"
              />
              <div>
                <p class="font-medium text-gray-800">{{ item.name }}</p>
                <p class="text-sm text-gray-500">S/. {{ (item.quantity * parseFloat(item.selling_price)).toFixed(2) }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button @click="decrement(item)" class="text-gray-500 hover:text-gray-700">–</button>
              <span class="w-6 text-center">{{ item.quantity }}</span>
              <button @click="increment(item)" class="text-gray-500 hover:text-gray-700">+</button>
            </div>
            <button @click="remove(item)" class="text-red-500 hover:text-red-700 ml-2">✕</button>
          </li>
        </ul>
        <div class="mt-6 border-t pt-4">
          <p class="flex justify-between font-semibold text-gray-800">
            <span>Total:</span>
            <span>S/. {{ cartTotal }}</span>
          </p>
          <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">
            Pagar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import FooterComponent from '@/Components/Footers/Footer.vue';
import {
  ShoppingCartIcon,
  UserIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  Squares2X2Icon,
  Bars3Icon,
  ArrowsUpDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  pageTitle: String,
  products: Array
});

const page = usePage();
const authUser = page.props.auth.user;

// Buscador
const searchQuery = ref('');

// Filtros
const selectedCategory = ref('');
const minPrice = ref('');
const maxPrice = ref('');

const categories = computed(() => {
  const set = new Set();
  props.products.forEach(p => p.category?.name && set.add(p.category.name));
  return Array.from(set);
});

const filteredProducts = computed(() => {
  return props.products
    .filter(p => {
      const price = parseFloat(p.selling_price);
      const byCat = !selectedCategory.value || p.category?.name === selectedCategory.value;
      const byMin = minPrice.value === '' || price >= +minPrice.value;
      const byMax = maxPrice.value === '' || price <= +maxPrice.value;
      return byCat && byMin && byMax;
    })
    .filter(p => {
      if (!searchQuery.value) return true;
      return p.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

// Vista (grid / list)
const viewMode = ref('grid');

// Carrito
const cart = ref([]);
const showCart = ref(false);

onMounted(() => {
  const saved = localStorage.getItem('cart');
  if (saved) cart.value = JSON.parse(saved);
});
watch(cart, v => localStorage.setItem('cart', JSON.stringify(v)), { deep: true });

const addToCart = product => {
  const ex = cart.value.find(i => i.id === product.id);
  if (ex) ex.quantity++;
  else cart.value.push({ ...product, quantity: 1 });
};

const remove = product => {
  cart.value = cart.value.filter(i => i.id !== product.id);
};

const increment = product => {
  product.quantity++;
};

const decrement = product => {
  if (product.quantity > 1) product.quantity--;
  else remove(product);
};

const cartTotal = computed(() =>
  cart.value.reduce(
    (sum, item) => sum + item.quantity * parseFloat(item.selling_price),
    0
  ).toFixed(2)
);

// Logout
const logout = () => {
  router.post(route('logout'));
};
</script>

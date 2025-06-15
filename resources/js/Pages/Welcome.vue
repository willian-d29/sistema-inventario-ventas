<template>
  <Head :title="pageTitle" />

  <!-- FUENTE PERSONALIZADA GOOGLE FONTS -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap" rel="stylesheet" />

  <div class="min-h-screen flex flex-col font-[Montserrat] bg-gradient-to-br from-sky-50 via-white to-emerald-50">

    <!-- Navbar -->
    <nav class="sticky top-0 z-20 bg-gradient-to-r from-blue-800 to-indigo-600 shadow-lg text-white">
      <div class="container mx-auto max-w-screen-xl px-4 py-3 flex items-center justify-between">
        <!-- Logo y nombre -->
        <Link href="/" class="flex items-center gap-3">
          <img src="/logo.png" alt="Logo Laratoy" class="h-10 w-10 rounded-full bg-white shadow-md object-cover"/>
          <span class="text-2xl md:text-3xl font-extrabold tracking-wider logo-gradient drop-shadow">Laratoy</span>
        </Link>
        <!-- Navegación -->
        <ul class="hidden md:flex items-center space-x-8 font-medium tracking-wide">
          <li>
            <Link href="#" class="hover:text-emerald-200 flex items-center gap-2 transition-colors duration-150">
              <BoltIcon class="w-5 h-5"/> Más vendidos
            </Link>
          </li>
          <li>
            <Link href="#" class="hover:text-emerald-200 flex items-center gap-2 transition-colors duration-150">
              <TagIcon class="w-5 h-5"/> Con descuentos
            </Link>
          </li>
          <li>
            <Link href="#" class="hover:text-emerald-200 flex items-center gap-2 transition-colors duration-150">
              <SparklesIcon class="w-5 h-5"/> Lo nuevo
            </Link>
          </li>
          <li class="relative group">
            <button @click="showDropdown = !showDropdown" class="flex items-center hover:text-emerald-200 transition-colors duration-150 focus:outline-none">
              <Squares2X2Icon class="w-5 h-5 mr-1"/> Categorías <ChevronDownIcon class="w-4 h-4 ml-1"/>
            </button>
            <transition name="fade">
              <div
                v-show="showDropdown"
                class="absolute left-0 mt-3 w-48 bg-white text-blue-900 rounded shadow-2xl z-30 py-2 animate-fade-down"
                @mouseleave="showDropdown = false"
              >
                <button
                  v-for="cat in categories"
                  :key="cat"
                  @click="selectCategory(cat)"
                  class="w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors text-sm"
                >
                  {{ cat }}
                </button>
                <button @click="selectCategory('')" class="w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors text-sm">
                  Todas las categorías
                </button>
              </div>
            </transition>
          </li>
        </ul>
        <!-- Searchbar y acciones -->
        <div class="flex-1 px-4 hidden md:block">
          <div class="relative max-w-lg mx-auto">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar productos..."
              class="w-full pl-5 pr-12 py-2 rounded-full text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400 transition shadow"
            />
            <MagnifyingGlassIcon class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"/>
          </div>
        </div>
        <!-- Acciones usuario/carrito -->
        <div class="flex items-center space-x-6">
          <button @click="showCart = true" class="relative hover:text-emerald-200 transition-colors duration-150 flex items-center">
            <ShoppingCartIcon class="w-7 h-7"/>
            <span v-if="cart.length" class="absolute -top-1 -right-2 bg-emerald-500 text-white text-xs rounded-full px-1 animate-pulse">
              {{ cart.length }}
            </span>
          </button>
          <div v-if="authUser" class="flex items-center gap-2 text-white">
            <img
              v-if="authUser.photo"
              :src="`/storage/users/${authUser.photo}`"
              alt="Avatar"
              class="w-9 h-9 rounded-full border-2 border-emerald-500 object-cover shadow"
            />
            <span>{{ authUser.name }}</span>
            <button @click="logout" class="ml-2 text-xs px-3 py-1 rounded bg-red-500 hover:bg-red-600 shadow transition">Cerrar sesión</button>
          </div>
          <div v-else class="space-x-2 flex items-center">
            <UserIcon class="w-5 h-5 text-white"/>
            <Link href="/login" class="hover:text-emerald-200 text-sm font-semibold transition">Iniciar sesión</Link>
            <span>|</span>
            <Link href="/register" class="hover:text-emerald-200 text-sm font-semibold transition">Registrarse</Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Buscador mobile -->
    <div class="block md:hidden bg-white/80 px-4 py-2 shadow">
      <div class="relative max-w-md mx-auto">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar productos..."
          class="w-full pl-4 pr-10 py-2 rounded-full text-gray-800 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
        />
        <MagnifyingGlassIcon class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"/>
      </div>
    </div>

    <!-- Filtros rápidos -->
    <main class="flex-1">
      <section class="container mx-auto max-w-screen-xl px-4 py-6">
        <div class="bg-white/90 p-4 rounded-xl shadow flex flex-wrap items-center gap-4 animate-fade-in">
          <select
            v-model="selectedCategory"
            class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300 font-semibold"
          >
            <option value="">Todas las categorías</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <input
            v-model.number="minPrice"
            type="number"
            placeholder="Mín precio"
            class="w-28 border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-semibold"
            min="0"
          />
          <input
            v-model.number="maxPrice"
            type="number"
            placeholder="Máx precio"
            class="w-28 border border-gray-300 rounded-md py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 font-semibold"
            min="0"
          />
        </div>
      </section>

      <!-- Vista/Orden -->
      <section class="container mx-auto max-w-screen-xl px-4 flex justify-between items-center pb-4">
        <div class="flex items-center space-x-2 text-gray-700 font-semibold">
          <ArrowsUpDownIcon class="w-6 h-6"/>
          <span class="text-lg">Más relevantes</span>
          <ChevronDownIcon class="w-5 h-5"/>
        </div>
        <div class="flex items-center space-x-3">
          <button @click="viewMode = 'grid'" :class="viewMode==='grid' ? 'text-blue-600 scale-110' : 'text-gray-400'" class="transition-transform duration-200">
            <Squares2X2Icon class="w-6 h-6"/>
          </button>
          <button @click="viewMode = 'list'" :class="viewMode==='list' ? 'text-blue-600 scale-110' : 'text-gray-400'" class="transition-transform duration-200">
            <Bars3Icon class="w-6 h-6"/>
          </button>
        </div>
      </section>

      <!-- Loader de productos -->
      <div v-if="loading" class="flex justify-center items-center py-20 animate-pulse">
        <svg class="w-12 h-12 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
      </div>

      <!-- Grid de productos -->
      <section v-else class="container mx-auto max-w-screen-xl px-4 pb-10">
        <div
          :class="viewMode==='grid'
            ? 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6'
            : 'flex flex-col space-y-6'"
        >
          <article
            v-for="product in filteredProducts"
            :key="product.id"
            @click="openProduct(product)"
            :class="viewMode==='grid'
              ? 'bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden text-center text-xs transition-transform duration-200 hover:-translate-y-1 group max-w-[200px] mx-auto cursor-pointer ring-emerald-100 hover:ring-4'
              : 'bg-white rounded-2xl shadow-lg hover:shadow-2xl p-4 flex items-center text-xs transition-transform duration-200 hover:-translate-y-1 group cursor-pointer max-w-2xl mx-auto'"
            style="min-width: 180px;"
          >
            <div v-if="viewMode==='grid'" class="w-full aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
              <img
                :src="product.photo || '/images/no-image.png'"
                :alt="product.name"
                class="w-24 h-24 object-contain group-hover:scale-110 transition-transform duration-200"
              />
            </div>
            <div v-else class="h-16 w-16 overflow-hidden flex-shrink-0 rounded-lg">
              <img
                :src="product.photo || '/images/no-image.png'"
                :alt="product.name"
                class="w-full h-full object-contain"
              />
            </div>
            <div class="p-2 flex-1 flex flex-col justify-between">
              <div>
                <h4 class="font-bold text-gray-900 truncate text-sm group-hover:text-blue-700 transition-colors">{{ product.name }}</h4>
                <p class="text-emerald-600 font-bold mt-1 text-base">S/. {{ product.selling_price }}</p>
                <p class="text-gray-400 text-xs">{{ product.category?.name || 'N/A' }}</p>
              </div>
              <button
                class="mt-2 text-xs font-bold text-white bg-gradient-to-r from-blue-600 to-emerald-600 hover:from-emerald-600 hover:to-blue-600 rounded-lg px-2 py-2 shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-400 active:scale-95 flex items-center justify-center gap-2"
                @click.stop="addToCart(product)"
              >
                <ShoppingCartIcon class="w-4 h-4"/>
                Agregar
              </button>
            </div>
          </article>
        </div>
      </section>
    </main>

    <!-- MODAL DE DETALLES DE PRODUCTO -->
    <transition name="fade">
      <div
        v-if="showProductModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      >
        <div class="bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl relative animate-fade-in">
          <button @click="closeProductModal" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl font-bold transition">×</button>
          <img :src="selectedProduct.photo || '/images/no-image.png'" class="w-40 h-40 object-contain mx-auto mb-4 rounded-xl shadow"/>
          <h2 class="text-2xl font-extrabold mb-1 text-blue-700">{{ selectedProduct.name }}</h2>
          <p class="text-emerald-700 font-semibold text-xl mb-2">S/. {{ selectedProduct.selling_price }}</p>
          <p class="text-gray-500 mb-2">{{ selectedProduct.category?.name }}</p>
          <p class="text-gray-700 mb-6">{{ selectedProduct.description || 'Sin descripción.' }}</p>
          <button
            class="mt-2 w-full bg-gradient-to-r from-blue-600 to-emerald-600 hover:from-emerald-600 hover:to-blue-600 text-white py-2 rounded-lg font-semibold shadow transition-all duration-200 text-lg flex justify-center gap-2"
            @click="addToCart(selectedProduct)"
          >
            <ShoppingCartIcon class="w-5 h-5"/> Agregar al carrito
          </button>
        </div>
      </div>
    </transition>

    <!-- Drawer del carrito -->
    <transition name="fade">
      <div v-if="showCart" class="fixed inset-0 z-50 flex">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCart = false"></div>
        <div class="relative ml-auto w-full sm:w-96 bg-white shadow-2xl p-6 overflow-y-auto animate-slide-left rounded-l-2xl">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-extrabold text-blue-700">Tu Carrito</h2>
            <button @click="showCart = false" class="text-gray-400 hover:text-red-500 text-2xl font-bold">×</button>
          </div>
          <ul class="space-y-5">
            <li
              v-for="item in cart"
              :key="item.id"
              class="flex justify-between items-center"
            >
              <div class="flex items-center space-x-3">
                <img
                  :src="item.photo || '/images/no-image.png'"
                  alt=""
                  class="h-14 w-14 object-contain rounded-lg shadow"
                />
                <div>
                  <p class="font-semibold text-gray-900">{{ item.name }}</p>
                  <p class="text-xs text-gray-500">S/. {{ (item.quantity * parseFloat(item.selling_price)).toFixed(2) }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button @click="decrement(item)" class="text-gray-500 hover:text-blue-700 text-lg px-1">–</button>
                <span class="w-6 text-center font-semibold">{{ item.quantity }}</span>
                <button @click="increment(item)" class="text-gray-500 hover:text-blue-700 text-lg px-1">+</button>
              </div>
              <button @click="remove(item)" class="text-red-500 hover:text-red-700 ml-2 text-xl">✕</button>
            </li>
          </ul>
          <div class="mt-8 border-t pt-5">
            <p class="flex justify-between font-bold text-blue-800 text-lg">
              <span>Total:</span>
              <span>S/. {{ cartTotal }}</span>
            </p>
            <button class="mt-5 w-full bg-gradient-to-r from-blue-600 to-emerald-600 hover:from-emerald-600 hover:to-blue-600 text-white py-3 rounded-xl font-semibold shadow transition-all duration-200 text-lg">
              <CreditCardIcon class="w-6 h-6 mr-1 inline"/> Pagar ahora
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Footer PRO -->
    <footer class="w-full bg-gradient-to-r from-blue-900 to-blue-700 py-8 shadow-lg mt-12">
      <div class="container mx-auto max-w-screen-xl px-4 flex flex-col md:flex-row items-center justify-between text-white">
        <div class="flex items-center gap-3 mb-4 md:mb-0">
          <img src="/logo.png" class="h-10 w-10 rounded-full bg-white shadow-md object-cover"/>
          <span class="font-extrabold text-2xl tracking-wider logo-gradient">Laratoy</span>
        </div>
        <div class="flex flex-col md:flex-row gap-2 md:gap-4 items-center text-sm mb-4 md:mb-0">
          <Link href="/" class="hover:underline">Inicio</Link>
          <Link href="#" class="hover:underline">Tienda</Link>
          <Link href="/login" class="hover:underline">Iniciar sesión</Link>
          <Link href="/register" class="hover:underline">Registrarse</Link>
        </div>
        <div class="flex gap-4 items-center">
          <a href="#" class="hover:text-emerald-300 transition"><svg class="w-6 h-6" fill="currentColor"><use xlink:href="#icon-facebook"></use></svg></a>
          <a href="#" class="hover:text-emerald-300 transition"><svg class="w-6 h-6" fill="currentColor"><use xlink:href="#icon-instagram"></use></svg></a>
          <a href="#" class="hover:text-emerald-300 transition"><svg class="w-6 h-6" fill="currentColor"><use xlink:href="#icon-twitter"></use></svg></a>
        </div>
      </div>
      <div class="text-center text-xs text-blue-200 mt-4">© {{ new Date().getFullYear() }} Laratoy. Todos los derechos reservados.</div>
      <!-- Puedes agregar SVG icons para las redes aquí abajo o usar lucide/heroicons/inline SVG -->
      <svg style="display:none">
        <symbol id="icon-facebook" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.731 0-1.325.594-1.325 1.326v21.348c0 .73.594 1.326 1.325 1.326h11.497v-9.294h-3.128v-3.622h3.128v-2.672c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.098 2.797.142v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.312h3.587l-.467 3.622h-3.12v9.294h6.116c.73 0 1.324-.596 1.324-1.326v-21.35c0-.732-.594-1.326-1.324-1.326"/></symbol>
        <symbol id="icon-instagram" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.34 3.608 1.314.974.975 1.251 2.242 1.313 3.608.059 1.266.069 1.646.069 4.85s-.011 3.584-.069 4.85c-.062 1.366-.34 2.633-1.313 3.608-.975.974-2.242 1.251-3.608 1.313-1.266.059-1.646.069-4.85.069s-3.584-.011-4.85-.069c-1.366-.062-2.633-.34-3.608-1.313-.974-.975-1.251-2.242-1.313-3.608-.059-1.266-.069-1.646-.069-4.85s.011-3.584.069-4.85c.062-1.366.34-2.633 1.313-3.608.975-.974 2.242-1.251 3.608-1.313 1.266-.059 1.646-.069 4.85-.069zm0-2.163c-3.259 0-3.667.012-4.941.07-1.518.068-2.871.366-3.95 1.445s-1.377 2.432-1.445 3.95c-.058 1.274-.07 1.682-.07 4.941s.012 3.667.07 4.941c.068 1.518.366 2.871 1.445 3.95s2.432 1.377 3.95 1.445c1.274.058 1.682.07 4.941.07s3.667-.012 4.941-.07c1.518-.068 2.871-.366 3.95-1.445s1.377-2.432 1.445-3.95c.058-1.274.07-1.682.07-4.941s-.012-3.667-.07-4.941c-.068-1.518-.366-2.871-1.445-3.95s-2.432-1.377-3.95-1.445c-1.274-.058-1.682-.07-4.941-.07z"/></symbol>
        <symbol id="icon-twitter" viewBox="0 0 24 24"><path d="M24 4.557a9.93 9.93 0 01-2.828.775 4.932 4.932 0 002.165-2.724c-.951.564-2.005.974-3.127 1.195a4.916 4.916 0 00-8.384 4.482c-4.084-.205-7.702-2.161-10.126-5.134-.423.724-.666 1.561-.666 2.475 0 1.708.87 3.215 2.188 4.099a4.904 4.904 0 01-2.229-.616c-.054 2.281 1.581 4.415 3.949 4.89-.693.189-1.453.232-2.224.084.627 1.956 2.444 3.377 4.6 3.417a9.867 9.867 0 01-6.102 2.105c-.396 0-.787-.023-1.174-.069a13.951 13.951 0 007.548 2.212c9.054 0 14.002-7.497 14.002-13.986 0-.213-.006-.425-.017-.636a9.936 9.936 0 002.457-2.548l-.047-.02z"/></symbol>
      </svg>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage, router, Link } from '@inertiajs/vue3';
import {
  ShoppingCartIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  Squares2X2Icon,
  Bars3Icon,
  ArrowsUpDownIcon,
  TagIcon,
  UserIcon,
  BoltIcon,
  SparklesIcon,
  CreditCardIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  pageTitle: String,
  products: Array
});

const page = usePage();
const authUser = page.props.auth.user ?? null;

// Loader de productos (simula carga inicial)
const loading = ref(true);
onMounted(() => {
  setTimeout(() => loading.value = false, 900); // simula una carga de 0.9s
});

// Buscador y filtros
const searchQuery = ref('');
const selectedCategory = ref('');
const minPrice = ref('');
const maxPrice = ref('');
const categories = computed(() => {
  const set = new Set();
  props.products.forEach(p => p.category?.name && set.add(p.category.name));
  return Array.from(set);
});
const showDropdown = ref(false);
const selectCategory = cat => {
  selectedCategory.value = cat;
  showDropdown.value = false;
};
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

// Vista (grid/list)
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
const remove = product => { cart.value = cart.value.filter(i => i.id !== product.id); };
const increment = product => { product.quantity++; };
const decrement = product => { if (product.quantity > 1) product.quantity--; else remove(product); };
const cartTotal = computed(() =>
  cart.value.reduce(
    (sum, item) => sum + item.quantity * parseFloat(item.selling_price),
    0
  ).toFixed(2)
);

// Modal de producto
const showProductModal = ref(false);
const selectedProduct = ref({});
const openProduct = (product) => {
  selectedProduct.value = product;
  showProductModal.value = true;
};
const closeProductModal = () => showProductModal.value = false;

// Logout
const logout = () => { router.post(route('logout')); };
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap');
.logo-gradient {
  background: linear-gradient(90deg, #60a5fa 0%, #06b6d4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.animate-fade-down { animation: fadeDown .3s cubic-bezier(.4,0,.2,1);}
.animate-fade-in { animation: fadeIn .6s cubic-bezier(.4,0,.2,1);}
.animate-slide-left { animation: slideLeft .4s cubic-bezier(.4,0,.2,1);}
@keyframes fadeDown { from { opacity:0; transform:translateY(-20px);} to{opacity:1;transform:translateY(0);} }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes slideLeft { from { transform: translateX(100%);} to { transform: translateX(0);} }
</style>

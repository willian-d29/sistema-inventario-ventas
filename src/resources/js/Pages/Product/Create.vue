<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import InputError from "@/Components/InputError.vue";
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from "@/Components/Button.vue";
import SubmitButton from "@/Components/SubmitButton.vue";
import AsyncVueSelect from "@/Components/AsyncVueSelect.vue";
import { showToast } from "@/Utils/Helper.js";
import default_image from "@/assets/img/default-image.jpg";

defineProps({
  filters: { type: Object },
});

const nameInput   = ref(null);
const isHovered   = ref(false);
const fileInput   = ref(null);
const previewImage = ref(null);

const form = useForm({
  category_id:    null,
  supplier_id:    null,
  name:           null,
  description:    null,
  product_code:   null,
  root:           null,
  buying_date:    null,
  buying_price:   null,
  selling_price:  null,
  unit_type_id:   null,
  quantity:       null,
  photo:          null,
  status:         'active',
});

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    previewImage.value = URL.createObjectURL(file);
    form.photo = file;
  }
};

const createProduct = () => {
  form.post(route('products.store'), {
    preserveScroll: true,
    onSuccess: () => showToast(),
    onError: () => nameInput.value.focus(),
  });
};
</script>

<template>
  <Head title="Productos" />

  <AuthenticatedLayout>
    <template #breadcrumb>
      Productos &gt; Crear
    </template>

    <div class="flex flex-wrap">
      <div class="w-full px-4">
        <div class="relative flex flex-col min-w-0 break-words w-full mb-6 shadow-lg rounded bg-white">
          <div class="rounded-t mb-3 px-4 py-3 border-0">
            <div class="flex justify-between items-center">
              <h4 class="text-2xl">Crear producto</h4>
              <Button
                :href="route('products.index')"
                buttonType="link"
              >
                Volver
              </Button>
            </div>
          </div>
          <div class="px-8 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
              <!-- Categoría -->
              <div class="flex flex-col">
                <label for="category_id" class="text-stone-600 text-sm font-medium">
                  Seleccionar categoría
                </label>
                <AsyncVueSelect
                  v-model="form.category_id"
                  resource="categories.index"
                  placeholder="Seleccionar categoría"
                  class="mt-2"
                />
                <InputError :message="form.errors.category_id"/>
              </div>
              <!-- Proveedor -->
              <div class="flex flex-col">
                <label for="supplier_id" class="text-stone-600 text-sm font-medium">
                  Seleccionar proveedor
                </label>
                <AsyncVueSelect
                  v-model="form.supplier_id"
                  resource="suppliers.index"
                  placeholder="Seleccionar proveedor"
                  class="mt-2"
                />
                <InputError :message="form.errors.supplier_id"/>
              </div>
              <!-- Nombre -->
              <div class="flex flex-col">
                <label for="name" class="text-stone-600 text-sm font-medium">
                  Nombre
                </label>
                <input
                  id="name"
                  ref="nameInput"
                  v-model="form.name"
                  @keyup.enter="createProduct"
                  type="text"
                  placeholder="Ingrese nombre"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.name"/>
              </div>
              <!-- Código de producto -->
              <div class="flex flex-col">
                <label for="product_code" class="text-stone-600 text-sm font-medium">
                  Código de producto
                </label>
                <input
                  id="product_code"
                  v-model="form.product_code"
                  @keyup.enter="createProduct"
                  type="text"
                  placeholder="Ingrese código de producto"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.product_code"/>
              </div>
              <!-- Raíz -->
              <div class="flex flex-col">
                <label for="root" class="text-stone-600 text-sm font-medium">
                  Raíz
                </label>
                <input
                  id="root"
                  v-model="form.root"
                  @keyup.enter="createProduct"
                  type="text"
                  placeholder="Ingrese raíz"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.root"/>
              </div>
              <!-- Fecha de compra -->
              <div class="flex flex-col">
                <label for="buying_date" class="text-stone-600 text-sm font-medium">
                  Fecha de compra
                </label>
                <input
                  id="buying_date"
                  v-model="form.buying_date"
                  @keyup.enter="createProduct"
                  type="date"
                  placeholder="Ingrese fecha de compra"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.buying_date"/>
              </div>
              <!-- Precio de compra -->
              <div class="flex flex-col">
                <label for="buying_price" class="text-stone-600 text-sm font-medium">
                  Precio de compra
                </label>
                <input
                  id="buying_price"
                  v-model="form.buying_price"
                  @keyup.enter="createProduct"
                  type="number"
                  placeholder="Ingrese precio de compra"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.buying_price"/>
              </div>
              <!-- Precio de venta -->
              <div class="flex flex-col">
                <label for="selling_price" class="text-stone-600 text-sm font-medium">
                  Precio de venta
                </label>
                <input
                  id="selling_price"
                  v-model="form.selling_price"
                  @keyup.enter="createProduct"
                  type="number"
                  placeholder="Ingrese precio de venta"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.selling_price"/>
              </div>
              <!-- Cantidad y tipo de unidad -->
              <div class="flex flex-col">
                <label for="quantity" class="text-stone-600 text-sm font-medium">
                  Cantidad
                </label>
                <div class="flex mt-1">
                  <AsyncVueSelect
                    v-model="form.unit_type_id"
                    resource="unit-types.index"
                    placeholder="Seleccionar tipo de unidad"
                    class="w-1/2 rounded-l-md bg-gray-300 border-none focus:outline-none"
                  />
                  <input
                    id="quantity"
                    v-model="form.quantity"
                    @keyup.enter="createProduct"
                    type="number"
                    placeholder="Ingrese cantidad"
                    class="w-full rounded-r-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                  />
                </div>
                <InputError :message="form.errors.unit_type_id"/>
                <InputError :message="form.errors.quantity"/>
              </div>
              <!-- Estado -->
              <div class="flex flex-col">
                <label for="status" class="text-stone-600 text-sm font-medium">
                  Estado
                </label>
                <select
                  id="status"
                  v-model="form.status"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                >
                  <option value="active">Activo</option>
                  <option value="inactive">Inactivo</option>
                </select>
                <InputError :message="form.errors.status"/>
              </div>
              <!-- Foto -->
              <div class="flex flex-col">
                <div
                  class="relative cursor-pointer"
                  @mouseenter="isHovered = true"
                  @mouseleave="isHovered = false"
                >
                  <img
                    @click="fileInput.click()"
                    :alt="'Vista previa'"
                    :src="previewImage || default_image"
                    class="shadow-xl h-auto align-middle border-none absolute max-w-150-px"
                    style="max-width: 400px; height: 150px;"
                    title="Subir foto"
                  />
                  <div
                    v-if="isHovered"
                    class="absolute flex items-center justify-center rounded-full"
                  >
                    <i class="fas fa-camera text-black text-2xl"></i>
                  </div>
                  <input
                    type="file"
                    class="hidden"
                    accept="image/*"
                    ref="fileInput"
                    @change="handleFileChange"
                  />
                </div>
                <InputError :message="form.errors.photo"/>
              </div>
              <!-- Descripción -->
              <div class="flex flex-col">
                <label for="description" class="text-stone-600 text-sm font-medium">
                  Descripción
                </label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  placeholder="Ingrese descripción"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                ></textarea>
                <InputError :message="form.errors.description"/>
              </div>
            </div>
            <!-- Botón enviar -->
            <div class="my-6 flex justify-end">
              <SubmitButton
                :processing="form.processing"
                @click="createProduct"
                class="bg-emerald-600 text-white font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-lg focus:outline-none mr-1 transition-all duration-150"
              >
                Guardar
              </SubmitButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

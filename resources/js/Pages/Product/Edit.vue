<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import InputError from "@/Components/InputError.vue";
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Button from "@/Components/Button.vue";
import SubmitButton from "@/Components/SubmitButton.vue";
import AsyncVueSelect from "@/Components/AsyncVueSelect.vue";
import { showToast } from "@/Utils/Helper.js";
import default_image from "@/assets/img/default-image.jpg";

const isHovered    = ref(false);
const previewImage = ref(null);

const props = defineProps({
  product: { type: Object },
});

// Inicializar formulario con datos existentes
onMounted(() => {
  form.category_id   = props.product.category_id;
  form.supplier_id   = props.product.supplier_id;
  form.name          = props.product.name;
  form.description   = props.product.description;
  form.product_code  = props.product.product_code;
  form.barcode       = props.product.barcode;
  form.root          = props.product.root;
  form.buying_date   = props.product.buying_date.split(" ")[0] ?? "";
  form.buying_price  = props.product.buying_price;
  form.selling_price = props.product.selling_price;
  form.unit_type_id  = props.product.unit_type_id;
  form.quantity      = props.product.quantity;
  form.status        = props.product.status;
  previewImage.value = props.product.photo || default_image;
});

const nameInput = ref(null);

const form = useForm({
  category_id:    null,
  supplier_id:    null,
  name:           null,
  description:    null,
  product_code:   null,
  barcode:        null,
  root:           null,
  buying_date:    null,
  buying_price:   null,
  selling_price:  null,
  unit_type_id:   null,
  quantity:       null,
  photo:          null,
  status:         null,
});

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    previewImage.value = URL.createObjectURL(file);
    form.photo = file;
  }
};

const updateProduct = () => {
  form.transform(data => ({ ...data, _method: 'put' }))
      .post(route('products.update', props.product.id), {
        preserveScroll: true,
        onSuccess: () => showToast(),
        onError:   () => nameInput.value.focus(),
      });
};
</script>

<template>
  <Head title="Editar producto" />

  <AuthenticatedLayout>
    <template #breadcrumb>
      Productos &gt; Editar
    </template>

    <div class="flex flex-wrap">
      <div class="w-full px-4">
        <div class="relative flex flex-col min-w-0 break-words w-full mb-6 shadow-lg rounded bg-white">
          <!-- Header del formulario -->
          <div class="rounded-t mb-3 px-4 py-3 border-0">
            <div class="flex justify-between items-center">
              <h4 class="text-2xl">Editar producto</h4>
              <Button
                :href="route('products.index')"
                buttonType="link"
              >
                Volver
              </Button>
            </div>
          </div>

          <!-- Cuerpo del formulario -->
          <div class="px-8 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
              <!-- Categoría -->
              <div class="flex flex-col">
                <label for="category_id" class="text-stone-600 text-sm font-medium">Seleccionar categoría</label>
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
                <label for="supplier_id" class="text-stone-600 text-sm font-medium">Seleccionar proveedor</label>
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
                <label for="name" class="text-stone-600 text-sm font-medium">Nombre</label>
                <input
                  id="name"
                  ref="nameInput"
                  v-model="form.name"
                  @keyup.enter="updateProduct"
                  type="text"
                  placeholder="Ingrese nombre"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.name"/>
              </div>

              <!-- Código de producto -->
              <div class="flex flex-col">
                <label for="product_code" class="text-stone-600 text-sm font-medium">Código de producto</label>
                <input
                  id="product_code"
                  v-model="form.product_code"
                  @keyup.enter="updateProduct"
                  type="text"
                  placeholder="Ingrese código de producto"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.product_code"/>
              </div>

              <!-- Código de barras -->
              <div class="flex flex-col">
                <label for="barcode" class="text-stone-600 text-sm font-medium">Código de barras</label>
                <input id="barcode" v-model="form.barcode" @keyup.enter="updateProduct" type="text"
                  placeholder="Escanea o ingresa el código" class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none" />
                <InputError :message="form.errors.barcode"/>
              </div>
              <!-- Producto base -->
              <div class="flex flex-col">
                <label for="root" class="text-stone-600 text-sm font-medium">Producto base</label>
                <input
                  id="root"
                  v-model="form.root"
                  @keyup.enter="updateProduct"
                  type="text"
                  placeholder="Sin producto base"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                  aria-describedby="root-help"
                />
                <p id="root-help" class="app-ui-help">Úsalo solo si el producto deriva de otro producto principal.</p>
                <InputError :message="form.errors.root"/>
              </div>

              <!-- Fecha de compra -->
              <div class="flex flex-col">
                <label for="buying_date" class="text-stone-600 text-sm font-medium">Fecha de compra</label>
                <input
                  id="buying_date"
                  v-model="form.buying_date"
                  @keyup.enter="updateProduct"
                  type="date"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.buying_date"/>
              </div>

              <!-- Precio de compra -->
              <div class="flex flex-col">
                <label for="buying_price" class="text-stone-600 text-sm font-medium">Precio de compra</label>
                <input
                  id="buying_price"
                  v-model="form.buying_price"
                  @keyup.enter="updateProduct"
                  type="number"
                  placeholder="Ingrese precio de compra"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.buying_price"/>
              </div>

              <!-- Precio de venta -->
              <div class="flex flex-col">
                <label for="selling_price" class="text-stone-600 text-sm font-medium">Precio de venta</label>
                <input
                  id="selling_price"
                  v-model="form.selling_price"
                  @keyup.enter="updateProduct"
                  type="number"
                  placeholder="Ingrese precio de venta"
                  class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
                />
                <InputError :message="form.errors.selling_price"/>
              </div>

              <!-- Cantidad y tipo de unidad -->
              <div class="flex flex-col">
                <label for="quantity" class="text-stone-600 text-sm font-medium">Cantidad</label>
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
                    @keyup.enter="updateProduct"
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
                <label for="status" class="text-stone-600 text-sm font-medium">Estado</label>
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
                <label class="block text-stone-600 text-sm font-medium mb-1">Foto</label>
                <label
                  class="relative cursor-pointer mt-2 w-full h-40 border border-gray-200 rounded-md bg-gray-50 hover:bg-gray-100 flex items-center justify-center overflow-hidden"
                >
                  <img
                    v-if="previewImage"
                    :src="previewImage"
                    alt="Vista previa"
                    class="max-h-full"
                  />
                  <img
                    v-else
                    :src="default_image"
                    alt="Imagen por defecto"
                    class="max-h-full opacity-50"
                  />
                  <input
                    type="file"
                    accept="image/*"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    @change="handleFileChange"
                  />
                </label>
                <InputError :message="form.errors.photo"/>
              </div>

              <!-- Descripción -->
              <div class="flex flex-col">
                <label for="description" class="text-stone-600 text-sm font-medium">Descripción</label>
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

            <!-- Botón Guardar cambios -->
            <div class="my-6 flex justify-end">
              <SubmitButton
                :processing="form.processing"
                @click="updateProduct"
                class="bg-emerald-600 text-white font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-lg focus:outline-none transition-all duration-150"
              >
                Guardar cambios
              </SubmitButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

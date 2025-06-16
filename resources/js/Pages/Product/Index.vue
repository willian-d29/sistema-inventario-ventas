<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import CardTable from "@/Components/Cards/CardTable.vue";
import TableData from "@/Components/TableData.vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { numberFormat, showToast, truncateString } from "@/Utils/Helper.js";
import default_image from "@/assets/img/default-image.jpg";


defineProps({
  filters:  { type: Object },
  products: { type: Object },
});

const selectedProduct = ref(null);
const showDeleteModal  = ref(false);

// Encabezados de tabla en español
const tableHeads = ref([
  '#',
  'Nombre',
  'Número de producto',
  'Código de producto',
  'Categoría',
  'Proveedor',
  'Cantidad',
  'Estado',
  'Acción',
]);

const form = useForm({});

function deleteProductModal(product) {
  selectedProduct.value = product;
  showDeleteModal.value = true;
}

function deleteProduct() {
  form.delete(route('products.destroy', selectedProduct.value.id), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
  });
}

function closeModal() {
  showDeleteModal.value = false;
  form.reset();
}
</script>

<template>
  <Head title="Productos" />

  <AuthenticatedLayout>
    <template #breadcrumb>
      Productos
    </template>

    <div class="flex flex-wrap">
      <div class="w-full px-4">
        <CardTable
          indexRoute="products.index"
          :paginatedData="products"
          :filters="filters"
          :tableHeads="tableHeads"
        >
          <template #cardHeader>
            <div class="flex justify-between items-center">
              <h4 class="text-2xl">
                Aplicar filtros ({{ products.total }})
              </h4>
              <Button
                :href="route('products.create')"
                buttonType="link"
              >
                Crear producto
              </Button>
            </div>
          </template>

          <tr
            v-for="(product, index) in products.data"
            :key="product.id"
          >
          
            <TableData>
              {{ (products.current_page - 1) * products.per_page + index + 1 }}
            </TableData>
            <TableData class="flex items-center" :title="product.name">
              

              <span class="font-bold">
                {{ truncateString(product.name, 15) }}
              </span>
            </TableData>
            <TableData>{{ product.product_number }}</TableData>
            <TableData>{{ product.product_code }}</TableData>
            <TableData :title="product.category.name">
              {{ truncateString(product.category.name) }}
            </TableData>
            <TableData :title="product.supplier?.name">
              {{ truncateString(product.supplier?.name ?? '-') }}
            </TableData>
            <TableData>
              {{ numberFormat(product.quantity) }}
              {{ product.unit_type?.symbol }}
              <span
                v-if="product.quantity > 0 && product.quantity < 10"
                class="text-xs font-semibold inline-block py-1 px-2 rounded text-amber-600 bg-amber-200"
              >
                Poco stock
              </span>
              <span
                v-if="product.quantity < 1"
                class="text-xs font-semibold inline-block py-1 px-2 rounded text-red-600 bg-red-200"
              >
                Sin stock
              </span>
            </TableData>
            <TableData>
              <span
                v-if="product.status === 'active'"
                class="text-xs font-semibold inline-block py-1 px-2 rounded text-emerald-600 bg-emerald-200"
              >
                Activo
              </span>
              <span
                v-else
                class="text-xs font-semibold inline-block py-1 px-2 rounded text-red-600 bg-red-200"
              >
                Inactivo
              </span>
            </TableData>
            <TableData>
              <Button
                :href="route('products.edit', product.id)"
                buttonType="link"
                preserveScroll
                class="mr-2"
              >
                <i class="fa fa-edit"></i>
              </Button>
              <Button
                @click="deleteProductModal(product)"
                type="red"
              >
                <i class="fa fa-trash-alt"></i>
              </Button>
            </TableData>
          </tr>
        </CardTable>
      </div>
    </div>

    <!-- Modal: Eliminar producto -->
    <Modal
      title="Eliminar producto"
      cancelButtonText="Cancelar"
      submitButtonText="¡Sí, eliminar!"
      :show="showDeleteModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="deleteProduct"
      maxWidth="sm"
    >
      ¿Está seguro de que desea eliminar este producto?
    </Modal>
  </AuthenticatedLayout>
</template>

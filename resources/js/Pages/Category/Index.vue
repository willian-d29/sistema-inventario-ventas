<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import CardTable from "@/Components/Cards/CardTable.vue";
import TableData from "@/Components/TableData.vue";
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { showToast } from "@/Utils/Helper.js";

defineProps({
  filters:    { type: Object },
  categories: { type: Object },
});

const selectedCategory = ref(null);
const showCreateModal  = ref(false);
const showEditModal    = ref(false);
const showDeleteModal  = ref(false);
const nameInput        = ref(null);

// Cabeceras de tabla en español
const tableHeads = ref([
  '#',
  'Nombre',
  'Acción',
]);

const form = useForm({ name: null });

function createCategoryModal() {
  showCreateModal.value = true;
  nextTick(() => nameInput.value.focus());
}

function editCategoryModal(category) {
  selectedCategory.value = category;
  form.name = category.name;
  showEditModal.value = true;
  nextTick(() => nameInput.value.focus());
}

function deleteCategoryModal(category) {
  selectedCategory.value = category;
  showDeleteModal.value = true;
}

function createCategory() {
  form.post(route('categories.store'), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
    onError:   () => nameInput.value.focus(),
  });
}

function updateCategory() {
  form.put(route('categories.update', selectedCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
    onError:   () => nameInput.value.focus(),
  });
}

function deleteCategory() {
  form.delete(route('categories.destroy', selectedCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
  });
}

function closeModal() {
  showCreateModal.value = false;
  showEditModal.value   = false;
  showDeleteModal.value = false;
  form.reset();
}
</script>

<template>
  <Head title="Categorías" />

  <AuthenticatedLayout>
    <template #breadcrumb>
      Categorías
    </template>

    <div class="flex flex-wrap">
      <div class="w-full px-4">
        <CardTable
          indexRoute="categories.index"
          :paginatedData="categories"
          :filters="filters"
          :tableHeads="tableHeads"
        >
          <template #cardHeader>
            <div class="flex justify-between items-center">
              <h4 class="text-2xl">
                Aplicar filtros ({{ categories.total }})
              </h4>
              <Button @click="createCategoryModal">
                Crear categoría
              </Button>
            </div>
          </template>

          <tr
            v-for="(category, index) in categories.data"
            :key="category.id"
          >
            <TableData>
              {{ (categories.current_page - 1) * categories.per_page + index + 1 }}
            </TableData>
            <TableData>{{ category.name }}</TableData>
            <TableData>
              <Button @click="editCategoryModal(category)" class="mr-2">
                <i class="fa fa-edit"></i>
              </Button>
              <Button
                @click="deleteCategoryModal(category)"
                type="red"
              >
                <i class="fa fa-trash-alt"></i>
              </Button>
            </TableData>
          </tr>
        </CardTable>
      </div>
    </div>

    <!-- Modal: Crear categoría -->
    <Modal
      title="Crear categoría"
      cancelButtonText="Cancelar"
      submitButtonText="Guardar"
      :show="showCreateModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="createCategory"
    >
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
          Nombre
        </label>
        <input
          id="name"
          ref="nameInput"
          v-model="form.name"
          @keyup.enter="createCategory"
          type="text"
          placeholder="Ingrese nombre"
          class="mt-1 block w-full rounded border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:ring"
        />
        <InputError :message="form.errors.name"/>
      </div>
    </Modal>

    <!-- Modal: Editar categoría -->
    <Modal
      title="Editar categoría"
      cancelButtonText="Cancelar"
      submitButtonText="Guardar cambios"
      :show="showEditModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="updateCategory"
    >
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
          Nombre
        </label>
        <input
          id="name"
          ref="nameInput"
          v-model="form.name"
          @keyup.enter="updateCategory"
          type="text"
          placeholder="Ingrese nombre"
          class="mt-1 block w-full rounded border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:ring"
        />
        <InputError :message="form.errors.name"/>
      </div>
    </Modal>

    <!-- Modal: Eliminar categoría -->
    <Modal
      title="Eliminar categoría"
      cancelButtonText="Cancelar"
      submitButtonText="¡Sí, eliminar!"
      :show="showDeleteModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="deleteCategory"
      maxWidth="sm"
    >
      ¿Está seguro de que desea eliminar esta categoría?
    </Modal>
  </AuthenticatedLayout>
</template>

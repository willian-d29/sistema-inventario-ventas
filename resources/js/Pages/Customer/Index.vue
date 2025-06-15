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
import DashboardInputGroup from "@/Components/DashboardInputGroup.vue";
import { showToast } from "@/Utils/Helper.js";
import default_image from "@/assets/img/default-image.jpg";

defineProps({
  filters:   { type: Object },
  customers: { type: Object },
});

const selectedCustomer = ref(null);
const showCreateModal  = ref(false);
const showEditModal    = ref(false);
const showDeleteModal  = ref(false);
const nameInput        = ref(null);

const tableHeads = ref(['#', 'Nombre', 'Correo', 'Teléfono', 'Acción']);

const form = useForm({
  name:     null,
  email:    null,
  phone:    null,
  address:  null,
  photo:    null,
  password: null,
});

const previewImage = ref(null);

function handleFileChange(event) {
  const file = event.target.files[0];
  if (file) {
    previewImage.value = URL.createObjectURL(file);
    form.photo = file;
  }
}

function createCustomerModal() {
  showCreateModal.value = true;
  previewImage.value = null;
  nextTick(() => nameInput.value && nameInput.value.focus());
}

function editCustomerModal(customer) {
  selectedCustomer.value = customer;
  Object.assign(form, {
    name:    customer.name,
    email:   customer.email,
    phone:   customer.phone,
    address: customer.address,
    photo:   null,
    password: null,
  });
  previewImage.value = customer.photo || null;
  showEditModal.value = true;
  nextTick(() => nameInput.value && nameInput.value.focus());
}

function deleteCustomerModal(customer) {
  selectedCustomer.value = customer;
  showDeleteModal.value  = true;
}

function createCustomer() {
  form.post(route('customers.store'), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
    onError:   () => nameInput.value && nameInput.value.focus(),
  });
}

function updateCustomer() {
  form.transform(data => ({ ...data, _method: 'put' }))
      .post(route('customers.update', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => { closeModal(); showToast(); },
        onError:   () => nameInput.value && nameInput.value.focus(),
      });
}

function deleteCustomer() {
  form.delete(route('customers.destroy', selectedCustomer.value.id), {
    preserveScroll: true,
    onSuccess: () => { closeModal(); showToast(); },
  });
}

function closeModal() {
  showCreateModal.value = false;
  showEditModal.value   = false;
  showDeleteModal.value = false;
  previewImage.value = null;
  form.reset();
}
</script>

<template>
  <Head title="Clientes" />

  <AuthenticatedLayout>
    <template #breadcrumb>
      Clientes
    </template>

    <div class="flex flex-wrap">
      <div class="w-full px-4">
        <CardTable
          indexRoute="customers.index"
          :paginatedData="customers"
          :filters="filters"
          :tableHeads="tableHeads"
        >
          <template #cardHeader>
            <div class="flex justify-between items-center">
              <h4 class="text-2xl">Aplicar filtros ({{ customers.total }})</h4>
              <Button @click="createCustomerModal">Crear cliente</Button>
            </div>
          </template>

          <tr v-for="(customer, index) in customers.data" :key="customer.id">
            <TableData>
              {{ (customers.current_page - 1) * customers.per_page + index + 1 }}
            </TableData>
            <TableData class="flex items-center">
              <img
                :src="customer.photo || default_image"
                alt="Foto de cliente"
                class="h-12 w-12 rounded-full border mr-2 object-cover"
              />
              <span class="font-bold">{{ customer.name }}</span>
            </TableData>
            <TableData>{{ customer.email }}</TableData>
            <TableData>{{ customer.phone }}</TableData>
            <TableData>
              <Button @click="editCustomerModal(customer)" class="mr-2">
                <i class="fa fa-edit"></i>
              </Button>
              <Button @click="deleteCustomerModal(customer)" type="red">
                <i class="fa fa-trash-alt"></i>
              </Button>
            </TableData>
          </tr>
        </CardTable>
      </div>
    </div>

    <!-- Modal: Crear cliente -->
    <Modal
      title="Crear cliente"
      cancelButtonText="Cancelar"
      submitButtonText="Guardar"
      :show="showCreateModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="createCustomer"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <DashboardInputGroup
          ref="nameInput"
          label="Nombre"
          name="name"
          v-model="form.name"
          placeholder="Ingrese nombre"
          :errorMessage="form.errors.name"
          @keyupEnter="createCustomer"
        />
        <DashboardInputGroup
          label="Correo"
          name="email"
          v-model="form.email"
          placeholder="Ingrese correo"
          :errorMessage="form.errors.email"
          @keyupEnter="createCustomer"
          type="email"
        />
        <DashboardInputGroup
          label="Teléfono"
          name="phone"
          v-model="form.phone"
          placeholder="Ingrese teléfono"
          :errorMessage="form.errors.phone"
          @keyupEnter="createCustomer"
        />
        <DashboardInputGroup
          label="Contraseña (opcional)"
          name="password"
          v-model="form.password"
          placeholder="Ingrese contraseña (solo si requiere login)"
          :errorMessage="form.errors.password"
          type="password"
          @keyupEnter="createCustomer"
        />
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
              class="max-h-full object-contain"
            />
            <img
              v-else
              :src="default_image"
              alt="Imagen por defecto"
              class="max-h-full opacity-50 object-contain"
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
        <!-- Dirección -->
        <div class="flex flex-col">
          <label for="address" class="text-stone-600 text-sm font-medium">Dirección</label>
          <textarea
            id="address"
            v-model="form.address"
            rows="3"
            placeholder="Ingrese dirección"
            class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
          ></textarea>
          <InputError :message="form.errors.address"/>
        </div>
      </div>
    </Modal>

    <!-- Modal: Editar cliente -->
    <Modal
      title="Editar cliente"
      cancelButtonText="Cancelar"
      submitButtonText="Guardar cambios"
      :show="showEditModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="updateCustomer"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <DashboardInputGroup
          ref="nameInput"
          label="Nombre"
          name="name"
          v-model="form.name"
          placeholder="Ingrese nombre"
          :errorMessage="form.errors.name"
          @keyupEnter="updateCustomer"
        />
        <DashboardInputGroup
          label="Correo"
          name="email"
          v-model="form.email"
          placeholder="Ingrese correo"
          :errorMessage="form.errors.email"
          @keyupEnter="updateCustomer"
          type="email"
        />
        <DashboardInputGroup
          label="Teléfono"
          name="phone"
          v-model="form.phone"
          placeholder="Ingrese teléfono"
          :errorMessage="form.errors.phone"
          @keyupEnter="updateCustomer"
        />
        <DashboardInputGroup
          label="Contraseña (opcional)"
          name="password"
          v-model="form.password"
          placeholder="Ingrese contraseña (solo si requiere login)"
          :errorMessage="form.errors.password"
          type="password"
          @keyupEnter="updateCustomer"
        />
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
              class="max-h-full object-contain"
            />
            <img
              v-else
              :src="selectedCustomer?.photo || default_image"
              alt="Imagen por defecto"
              class="max-h-full opacity-50 object-contain"
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
        <!-- Dirección -->
        <div class="flex flex-col">
          <label for="address" class="text-stone-600 text-sm font-medium">Dirección</label>
          <textarea
            id="address"
            v-model="form.address"
            rows="3"
            placeholder="Ingrese dirección"
            class="mt-2 block w-full rounded-md border border-gray-200 px-2 py-2 shadow-sm focus:outline-none"
          ></textarea>
          <InputError :message="form.errors.address"/>
        </div>
      </div>
    </Modal>

    <!-- Modal: Eliminar cliente -->
    <Modal
      title="Eliminar cliente"
      cancelButtonText="Cancelar"
      submitButtonText="¡Sí, eliminar!"
      :show="showDeleteModal"
      :formProcessing="form.processing"
      @close="closeModal"
      @submitAction="deleteCustomer"
      maxWidth="sm"
    >
      ¿Está seguro de que desea eliminar este cliente?
    </Modal>
  </AuthenticatedLayout>
</template>

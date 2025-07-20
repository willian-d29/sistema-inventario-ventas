<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-blue-100 py-10 px-4 font-sans">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl shadow-2xl animate-fade-in relative">
      
      <!-- Botón para volver -->
      <button @click="goBack" class="absolute top-4 left-4 flex items-center gap-1 text-blue-600 hover:text-blue-800 transition">
        <ArrowLeftIcon class="w-5 h-5" />
        <span class="text-sm font-semibold">Volver</span>
      </button>

      <h2 class="text-3xl font-bold text-blue-700 mb-8 text-center">Mi perfil</h2>

      <!-- Foto de perfil -->
      <div class="flex justify-center mb-6">
        <label class="relative cursor-pointer group">
          <img
  src="/assets/img/avatar.png"
  alt="avatar"
  class="w-10 h-10 rounded-full object-cover cursor-pointer hover:ring-2 hover:ring-blue-400 transition"
  @click="showUserMenu = !showUserMenu"
/>

          <input type="file" class="hidden" @change="handlePhotoUpload" accept="image/*" />
          <div class="absolute inset-0 flex items-center justify-center text-white text-xs font-semibold bg-black/40 opacity-0 group-hover:opacity-100 rounded-full">
            Cambiar
          </div>
        </label>
      </div>

      <form @submit.prevent="showModal = true" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
          <div class="flex items-center border rounded-md px-3 py-2 bg-white shadow-sm">
            <UserIcon class="w-5 h-5 text-gray-400 mr-2" />
            <input v-model="form.name" type="text" class="flex-1 outline-none" required />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Correo</label>
          <div class="flex items-center border rounded-md px-3 py-2 bg-white shadow-sm">
            <EnvelopeIcon class="w-5 h-5 text-gray-400 mr-2" />
            <input v-model="form.email" type="email" class="flex-1 outline-none" required />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
          <div class="flex items-center border rounded-md px-3 py-2 bg-white shadow-sm">
            <MapPinIcon class="w-5 h-5 text-gray-400 mr-2" />
            <input v-model="form.address" type="text" class="flex-1 outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
          <div class="flex items-center border rounded-md px-3 py-2 bg-white shadow-sm">
            <PhoneIcon class="w-5 h-5 text-gray-400 mr-2" />
            <input v-model="form.phone" type="text" class="flex-1 outline-none" />
          </div>
        </div>

        <button
          type="submit"
          class="w-full mt-4 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition-all shadow"
        >
          Guardar cambios
        </button>
      </form>
    </div>

    <!-- Modal de confirmación -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 shadow-lg w-full max-w-md space-y-4 animate-fade-in">
          <h3 class="text-lg font-semibold text-gray-800">¿Confirmar cambios?</h3>
          <p class="text-sm text-gray-600">¿Deseas guardar los cambios realizados en tu perfil?</p>
          <div class="flex justify-end gap-4 mt-4">
            <button
              class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-100 transition"
              @click="showModal = false"
            >Cancelar</button>
            <button
              class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition"
              @click="confirmSubmit"
            >Sí, guardar</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import {
  UserIcon,
  EnvelopeIcon,
  MapPinIcon,
  PhoneIcon,
  ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const user = usePage().props.auth.user

const form = useForm({
  name: user.name,
  email: user.email,
  address: user.address ?? '',
  phone: user.phone ?? '',
  photo: null,
  previewPhoto: null,
})

const showModal = ref(false)

const handlePhotoUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
    const reader = new FileReader()
    reader.onload = (e) => {
      form.previewPhoto = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const updateProfile = () => {
  router.post('/perfil', form, {
    forceFormData: true,
    preserveScroll: true
  })
}

const confirmSubmit = () => {
  showModal.value = false
  updateProfile()
}

const goBack = () => window.history.back()
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

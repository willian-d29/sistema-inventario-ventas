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
            :src="form.previewPhoto || user.photo || '/images/user-default.png'"
            class="w-28 h-28 rounded-full object-cover border-4 border-blue-400 shadow-md group-hover:opacity-80 transition"
          />
          <input type="file" class="hidden" @change="handlePhotoUpload" accept="image/*" />
          <div class="absolute inset-0 flex items-center justify-center text-white text-xs font-semibold bg-black/40 opacity-0 group-hover:opacity-100 rounded-full">
            Cambiar
          </div>
        </label>
      </div>

      <form @submit.prevent="updateProfile" class="space-y-5">
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
  </div>
</template>

<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3'
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
</style>

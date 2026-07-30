<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import InputError from '@/Components/InputError.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { showToast } from '@/Utils/Helper.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const { t } = useI18n();
const user = computed(() => page.props.auth.user || {});
const fileInput = ref(null);
const previewImage = ref(null);

const form = useForm({
  photo: null,
});

const initials = computed(() => String(user.value.name || 'LT')
  .split(' ')
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0])
  .join('')
  .toUpperCase());

const profileImage = computed(() => previewImage.value || user.value.photo || null);

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileChange(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  previewImage.value = URL.createObjectURL(file);
  form.photo = file;
  form.post(route('profile.image'), {
    preserveScroll: true,
    onSuccess: () => showToast(),
  });
}
</script>

<template>
  <aside class="profile-showcase" data-tour="profile-card">
    <div class="profile-showcase-cover">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <div class="profile-showcase-body">
      <div class="profile-avatar-wrap">
        <img
          v-if="profileImage"
          :src="profileImage"
          :alt="user.name"
          class="profile-avatar-image"
        />
        <div v-else class="profile-avatar-fallback" aria-hidden="true">
          {{ initials }}
        </div>
        <button
          type="button"
          class="profile-avatar-action"
          :aria-label="t('admin.profile.change_photo')"
          :title="t('admin.profile.change_photo')"
          @click="triggerFileInput"
        >
          <i class="fas fa-camera"></i>
        </button>
        <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleFileChange" />
      </div>

      <InputError :message="form.errors.photo" />

      <div class="mt-4 text-center">
        <h2 class="text-xl font-black text-[var(--color-text-primary)]">{{ user.name }}</h2>
        <a :href="`mailto:${user.email}`" class="mt-1 inline-flex items-center gap-2 text-sm font-bold text-[var(--color-primary)]">
          <i class="fas fa-envelope"></i>{{ user.email }}
        </a>
      </div>

      <div class="mt-5 grid gap-2">
        <AppButton type="button" variant="secondary" icon="fa-camera" full-width :loading="form.processing" @click="triggerFileInput">
          {{ t('admin.profile.change_photo') }}
        </AppButton>
        <div class="profile-role-pill">
          <i class="fas fa-id-badge"></i>
          <span>{{ user.role }}</span>
        </div>
      </div>
    </div>
  </aside>
</template>

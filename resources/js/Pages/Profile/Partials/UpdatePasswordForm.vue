<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const { t } = useI18n();

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function updatePassword() {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value?.focus?.();
      }
      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value?.focus?.();
      }
    },
  });
}
</script>

<template>
  <section class="space-y-4">
    <div>
      <h2 class="ihc-section-title">{{ t('admin.profile.password_title') }}</h2>
      <p class="ihc-help">{{ t('admin.profile.password_description') }}</p>
    </div>

    <form class="grid gap-4 lg:grid-cols-3" @submit.prevent="updatePassword">
      <AppInput
        ref="currentPasswordInput"
        v-model="form.current_password"
        id="current_password"
        :label="t('admin.profile.current_password')"
        type="password"
        autocomplete="current-password"
        :error="form.errors.current_password"
      />
      <AppInput
        ref="passwordInput"
        v-model="form.password"
        id="password"
        :label="t('admin.profile.new_password')"
        type="password"
        autocomplete="new-password"
        :error="form.errors.password"
      />
      <AppInput
        v-model="form.password_confirmation"
        id="password_confirmation"
        :label="t('admin.profile.confirm_password')"
        type="password"
        autocomplete="new-password"
        :error="form.errors.password_confirmation"
      />

      <div class="flex justify-end lg:col-span-3">
        <AppButton type="submit" :loading="form.processing" :loading-text="t('admin.profile.saving')" icon="fa-key">
          {{ form.recentlySuccessful ? t('admin.profile.saved') : t('admin.profile.update_password') }}
        </AppButton>
      </div>
    </form>
  </section>
</template>

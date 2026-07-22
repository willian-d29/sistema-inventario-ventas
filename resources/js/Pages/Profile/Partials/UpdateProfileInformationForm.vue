<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
  mustVerifyEmail: { type: Boolean },
  status: { type: String },
});

const user = usePage().props.auth.user;
const { t } = useI18n();

const form = useForm({
  name: user.name,
  email: user.email,
});
</script>

<template>
  <section class="space-y-4">
    <div>
      <h2 class="ihc-section-title">{{ t('admin.profile.personal_title') }}</h2>
      <p class="ihc-help">{{ t('admin.profile.personal_description') }}</p>
    </div>

    <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
      <AppInput
        v-model="form.name"
        id="profile-name"
        :label="t('common.name')"
        required
        autocomplete="name"
        autofocus
        :error="form.errors.name"
      />
      <AppInput
        v-model="form.email"
        id="profile-email"
        :label="t('admin.fields.email')"
        type="email"
        required
        autocomplete="username"
        :error="form.errors.email"
      />

      <div v-if="mustVerifyEmail && user.email_verified_at === null" class="lg:col-span-2">
        <p class="text-sm font-semibold text-[var(--color-text-secondary)]">
          {{ t('admin.profile.email_unverified') }}
          <Link
            :href="route('verification.send')"
            method="post"
            as="button"
            class="font-bold text-[var(--color-primary)] underline"
          >
            {{ t('admin.profile.resend_verification') }}
          </Link>
        </p>

        <p v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-bold text-[var(--color-success)]">
          {{ t('admin.profile.verification_sent') }}
        </p>
      </div>

      <div class="flex justify-end lg:col-span-2">
        <AppButton type="submit" :loading="form.processing" :loading-text="t('admin.profile.saving')" icon="fa-save">
          {{ form.recentlySuccessful ? t('admin.profile.saved') : t('admin.profile.save_data') }}
        </AppButton>
      </div>
    </form>
  </section>
</template>

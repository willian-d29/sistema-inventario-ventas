<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { useI18n } from '@/Composables/useI18n.js';
import {Head, Link, useForm} from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    pageTitle: {
        type: String,
    },
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const { t } = useI18n();
const showPassword = ref(false);
const accountDomain = 'laratory.pe';

function loginAlias(value) {
    return String(value || '').split('@')[0].trim().toLowerCase();
}

const submit = () => {
    form.email = loginAlias(form.email);
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head :title="pageTitle"/>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div class="container mx-auto px-4 h-full">
            <div class="flex content-center items-center justify-center h-full">
                <div class="w-full px-4 sm:w-10/12 md:w-7/12 lg:w-5/12 xl:w-4/12">
                    <div class="auth-card-motion relative mb-6 overflow-hidden rounded-lg border border-white/70 bg-white/95 shadow-2xl shadow-slate-950/20">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[var(--color-primary)] via-[var(--color-info)] to-[var(--color-secondary)]"></div>

                        <div class="px-6 pb-5 pt-8 lg:px-9">
                            <div class="auth-icon-motion mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-[var(--color-primary-soft)] text-[var(--color-primary)] shadow-inner">
                                <i class="fas fa-shield-alt text-xl"></i>
                            </div>

                            <div class="mt-4 text-center">
                                <p class="text-xs font-black uppercase tracking-wide text-[var(--color-primary)]">{{ t('auth.secure_access') }}</p>
                                <h1 class="mt-2 text-2xl font-black text-slate-950 drop-shadow-sm">{{ t('auth.app_title') }}</h1>
                                <p class="mt-2 text-sm font-medium leading-6 text-[var(--color-text-muted)]">{{ t('auth.app_subtitle') }}</p>
                            </div>
                        </div>

                        <div class="px-6 pb-7 lg:px-9">
                            <form class="space-y-4" @submit.prevent="submit">
                                <div>
                                    <label
                                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-800"
                                        for="email"
                                    >
                                        {{ t('auth.email') }}
                                    </label>
                                    <div class="auth-login-email-control">
                                        <span class="pointer-events-none absolute left-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md bg-[var(--color-primary-soft)] text-[var(--color-primary)]">
                                            <i class="fas fa-envelope text-xs"></i>
                                        </span>
                                        <input
                                            id="email"
                                            type="text"
                                            v-model="form.email"
                                            required
                                            autofocus
                                            autocomplete="username"
                                            inputmode="email"
                                            class="auth-login-email-input"
                                            :placeholder="t('auth.email_placeholder')"
                                        />
                                        <span class="auth-login-email-domain">@{{ accountDomain }}</span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.email"/>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label
                                            class="block text-xs font-black uppercase tracking-wide text-slate-800"
                                            for="password"
                                        >
                                            {{ t('auth.password') }}
                                        </label>
                                        <Link
                                            v-if="canResetPassword"
                                            :href="route('password.request')"
                                            class="inline-flex items-center gap-1.5 text-xs font-black text-[var(--color-primary)] transition hover:-translate-y-0.5 hover:text-[var(--color-primary-hover)]"
                                        >
                                            <i class="fas fa-unlock-alt text-[10px]"></i>
                                            {{ t('auth.forgot_password') }}
                                        </Link>
                                    </div>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md bg-[var(--color-primary-soft)] text-[var(--color-primary)]">
                                            <i class="fas fa-key text-xs"></i>
                                        </span>
                                        <input
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            class="min-h-12 w-full rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] py-3 pl-14 pr-12 text-sm font-bold text-slate-900 shadow-sm outline-none transition duration-150 placeholder:font-semibold placeholder:text-slate-400 placeholder:opacity-70 focus:border-[var(--color-primary)] focus:bg-white focus:ring-4 focus:ring-[color-mix(in_srgb,var(--color-focus)_38%,transparent)]"
                                            :placeholder="t('auth.password_placeholder')"
                                            v-model="form.password"
                                            required
                                            autocomplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-[var(--color-text-muted)] transition hover:scale-105 hover:bg-[var(--color-primary-soft)] hover:text-[var(--color-primary)] focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--color-focus)_38%,transparent)]"
                                            :title="showPassword ? t('auth.hide_password') : t('auth.show_password')"
                                            :aria-label="showPassword ? t('auth.hide_password') : t('auth.show_password')"
                                            @click="showPassword = !showPassword"
                                        >
                                            <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password"/>
                                </div>

                                <div
                                    class="rounded-md border px-4 py-3 transition"
                                    :class="form.remember
                                        ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] shadow-sm'
                                        : 'border-slate-300 bg-slate-100'"
                                >
                                    <label class="inline-flex cursor-pointer items-center">
                                        <Checkbox
                                            name="remember"
                                            v-model:checked="form.remember"
                                            class="form-checkbox ml-1 h-5 w-5 rounded border-0 text-[var(--color-primary)] transition duration-150"
                                        />
                                        <span class="ml-2 text-sm font-bold text-slate-800">
                                            {{ t('auth.remember') }}
                                        </span>
                                    </label>
                                </div>

                                <div class="pt-2 text-center">
                                    <button
                                        type="submit"
                                        class="auth-primary-action group flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] px-6 py-3 text-sm font-black uppercase text-white shadow-lg shadow-blue-200 transition duration-150 hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0 focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--color-focus)_42%,transparent)] disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="form.processing"
                                    >
                                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-sign-in-alt transition group-hover:translate-x-0.5"></i>
                                        <span>{{ form.processing ? t('common.loading') : t('auth.login') }}</span>
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { useI18n } from '@/Composables/useI18n.js';
import {Head, Link, useForm} from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});
const { t } = useI18n();

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout>
        <Head :title="t('auth.forgot_title')"/>

        <div class="container mx-auto px-4 h-full">
            <div class="flex content-center items-center justify-center h-full">
                <div class="w-full px-4 sm:w-10/12 md:w-7/12 lg:w-5/12 xl:w-4/12">
                    <div class="auth-card-motion relative mb-6 overflow-hidden rounded-lg border border-white/70 bg-white/95 shadow-2xl shadow-slate-950/20">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[var(--color-primary)] via-[var(--color-info)] to-[var(--color-secondary)]"></div>

                        <div class="px-6 pb-5 pt-8 lg:px-9">
                            <div class="auth-icon-motion mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-[var(--color-primary-soft)] text-[var(--color-primary)] shadow-inner">
                                <i class="fas fa-unlock-alt text-xl"></i>
                            </div>

                            <div class="mt-4 text-center">
                                <p class="text-xs font-black uppercase tracking-wide text-[var(--color-primary)]">{{ t('auth.secure_access') }}</p>
                                <h1 class="mt-2 text-2xl font-black text-slate-950 drop-shadow-sm">{{ t('auth.forgot_title') }}</h1>
                                <p class="mt-2 text-sm font-medium leading-6 text-[var(--color-text-muted)]">{{ t('auth.forgot_description') }}</p>
                            </div>
                        </div>

                        <div class="px-6 pb-7 lg:px-9">
                            <div v-if="status" class="mb-4 rounded-md border border-[var(--color-success)] bg-[var(--color-success-soft)] px-4 py-3 text-sm font-bold text-[var(--color-success)]">
                                <i class="fas fa-check-circle mr-2"></i>{{ status }}
                            </div>

                            <form class="space-y-4" @submit.prevent="submit">
                                <div>
                                    <label
                                        class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-800"
                                        for="email"
                                    >
                                        {{ t('auth.email') }}
                                    </label>

                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md bg-[var(--color-primary-soft)] text-[var(--color-primary)]">
                                            <i class="fas fa-envelope text-xs"></i>
                                        </span>
                                        <input
                                            id="email"
                                            type="email"
                                            v-model="form.email"
                                            required
                                            autofocus
                                            autocomplete="username"
                                            class="min-h-12 w-full rounded-md border border-[var(--color-border)] bg-[var(--color-surface-alt)] py-3 pl-14 pr-4 text-sm font-bold text-slate-900 shadow-sm outline-none transition duration-150 placeholder:font-semibold placeholder:text-slate-400 placeholder:opacity-70 focus:border-[var(--color-primary)] focus:bg-white focus:ring-4 focus:ring-[color-mix(in_srgb,var(--color-focus)_38%,transparent)]"
                                            :placeholder="t('auth.email_placeholder')"
                                        />
                                    </div>

                                    <InputError class="mt-2" :message="form.errors.email"/>
                                </div>

                                <div class="pt-2 text-center">
                                    <button
                                        type="submit"
                                        class="auth-primary-action group flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-gradient-to-r from-[var(--color-primary)] to-[var(--color-secondary)] px-6 py-3 text-sm font-black uppercase text-white shadow-lg shadow-blue-200 transition duration-150 hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0 focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--color-focus)_42%,transparent)] disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="form.processing"
                                    >
                                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                                        <i v-else class="fas fa-paper-plane transition group-hover:translate-x-0.5"></i>
                                        <span>{{ form.processing ? t('auth.sending') : t('auth.send_reset') }}</span>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-5 rounded-md border border-[var(--color-info)] bg-[var(--color-info-soft)] px-4 py-3 text-xs font-bold leading-5 text-[var(--color-info)]">
                                <i class="fas fa-info-circle mr-2"></i>{{ t('auth.forgot_hint') }}
                            </div>

                            <div class="mt-5 text-center">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center gap-2 text-sm font-black text-[var(--color-primary)] transition hover:-translate-y-0.5 hover:text-[var(--color-primary-hover)]"
                                >
                                    <i class="fas fa-arrow-left transition"></i>
                                    {{ t('auth.back_to_login') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

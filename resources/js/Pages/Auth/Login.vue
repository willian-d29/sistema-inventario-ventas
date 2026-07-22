<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { useI18n } from '@/Composables/useI18n.js';
import {Head, Link, useForm} from '@inertiajs/vue3';
import SubmitButton from "@/Components/SubmitButton.vue";

defineProps({
    pageTitle: {
        type: String,
    },
    canResetPassword: {
        type: Boolean,
    },
    canRegister: {
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

const submit = () => {
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
                <div class="w-full lg:w-4/12 px-4">
                    <div class="relative flex flex-col min-w-0 break-words w-full mb-6 shadow-xl rounded-lg bg-white border border-slate-200">
                        <div class="rounded-t mb-0 px-6 py-6">
                            <div class="text-center mb-3">
                                <h1 class="text-slate-800 text-xl font-bold">{{ t('auth.app_title') }}</h1>
                                <p class="mt-1 text-slate-500 text-sm">{{ t('auth.app_subtitle') }}</p>
                            </div>
                            <hr class="mt-6 border-b-1 border-slate-300"/>
                        </div>
                        <div class="flex-auto px-4 lg:px-10 py-10 pt-0">
                            <form @submit.prevent="submit">
                                <div class="relative w-full mb-3">
                                    <label
                                        class="block uppercase text-slate-600 text-xs font-bold mb-2"
                                        for="email"
                                    >
                                        {{ t('auth.email') }}
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        required
                                        autofocus
                                        class="border-0 px-3 py-3 placeholder-slate-300 text-slate-600 bg-white rounded text-sm shadow focus:outline-none focus:ring w-full ease-linear transition-all duration-150"
                                        :placeholder="t('auth.email_placeholder')"
                                    />
                                    <InputError :message="form.errors.email"/>
                                </div>

                                <div class="relative w-full mb-3">
                                    <label
                                        class="block uppercase text-slate-600 text-xs font-bold mb-2"
                                        for="password"
                                    >
                                        {{ t('auth.password') }}
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        class="border-0 px-3 py-3 placeholder-slate-300 text-slate-600 bg-white rounded text-sm shadow focus:outline-none focus:ring w-full ease-linear transition-all duration-150"
                                        :placeholder="t('auth.password_placeholder')"
                                        v-model="form.password"
                                        required
                                        autocomplete="current-password"
                                    />
                                    <InputError :message="form.errors.password"/>
                                </div>
                                <div>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <Checkbox
                                            name="remember"
                                            v-model:checked="form.remember"
                                            class="form-checkbox border-0 rounded text-slate-700 ml-1 w-5 h-5 ease-linear transition-all duration-150"
                                        />
                                        <span class="ml-2 text-sm font-semibold text-slate-600">
                                            {{ t('auth.remember') }}
                                        </span>
                                    </label>
                                </div>

                                <div class="text-center mt-6">
                                    <SubmitButton
                                        class="bg-slate-800 text-white active:bg-slate-600 text-sm font-bold uppercase px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 w-full ease-linear transition-all duration-150"
                                        :processing="form.processing"
                                    >
                                        {{ t('auth.login') }}
                                    </SubmitButton>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="flex flex-wrap mt-6 relative">
                        <div class="w-1/2">
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-slate-200"
                            >
                                <small>{{ t('auth.forgot_password') }}</small>
                            </Link>
                        </div>
                        <div class="w-1/2 text-right">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="text-slate-200"
                            >
                                <small>{{ t('auth.create_account') }}</small>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

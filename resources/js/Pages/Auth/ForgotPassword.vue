<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useI18n } from '@/Composables/useI18n.js';
import {Head, Link, useForm} from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";
import SubmitButton from "@/Components/SubmitButton.vue";

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
                <div class="w-full lg:w-4/12 px-4">
                    <div
                        class="relative flex flex-col min-w-0 break-words w-full mb-6 shadow-lg rounded-lg bg-slate-200 border-0"
                    >
                        <div class="rounded-t mb-0 px-6 py-6">
                            <div class="text-center">
                                <h6 class="text-slate-500 text-sm font-bold">
                                    {{ t('auth.forgot_title') }}
                                </h6>
                                <p>{{ t('auth.forgot_description') }}</p>
                            </div>
                        </div>
                        <div class="flex-auto px-4 lg:px-10 py-10 pt-0">
                            <form @submit.prevent="submit">
                                <div class="relative w-full mb-3">
                                    <InputLabel for="email" :value="t('auth.email')"
                                                class="block uppercase text-slate-600 text-xs font-bold mb-2"/>

                                    <TextInput
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        class="border-0 px-3 py-3 placeholder-slate-300 text-slate-600 bg-white rounded text-sm shadow focus:outline-none focus:ring w-full ease-linear transition-all duration-150"
                                        :placeholder="t('auth.email_placeholder')"
                                    />

                                    <InputError class="mt-1" :message="form.errors.email"/>
                                    <div v-if="status" class="mt-1 text-sm text-green-600">
                                        {{ status }}
                                    </div>
                                </div>

                                <div class="text-center mt-6">
                                    <SubmitButton
                                        class="bg-slate-800 text-white active:bg-slate-600 text-sm font-bold uppercase px-6 py-3 rounded shadow hover:shadow-lg outline-none focus:outline-none mr-1 mb-1 w-full ease-linear transition-all duration-150"
                                        :processing="form.processing"
                                    >
                                        {{ t('auth.send_reset') }}
                                    </SubmitButton>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="flex flex-wrap mt-6 relative">
                        <div class="w-1/2">
                            <Link
                                :href="route('login')"
                                class="text-slate-200"
                            >
                                <small>{{ t('auth.back_to_login') }}</small>
                            </Link>
                        </div>
                        <div class="w-1/2 text-right">
                            <Link
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

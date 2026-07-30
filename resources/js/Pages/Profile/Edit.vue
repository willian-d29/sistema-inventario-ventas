<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import CardProfile from "@/Components/Cards/CardProfile.vue";
import AppearancePanel from "@/Pages/Profile/Partials/AppearancePanel.vue";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import { useI18n } from '@/Composables/useI18n.js';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head :title="t('admin.profile.title')"/>

    <AuthenticatedLayout>
        <template #breadcrumb>
            {{ t('admin.profile.title') }}
        </template>

        <div class="grid min-w-0 gap-5 lg:grid-cols-12">
            <div class="min-w-0 lg:col-span-8">
                <AppearancePanel class="mb-6" />

                <div
                    class="ihc-panel relative mb-6 flex w-full min-w-0 flex-col break-words"
                    data-tour="profile-account"
                >
                    <div class="mb-0 rounded-t border-b border-[var(--color-border)] bg-[var(--color-surface)] px-6 py-6">
                        <div class="text-center flex justify-between">
                            <h6 class="text-xl font-bold text-[var(--color-text-primary)]">{{ t('admin.profile.account') }}</h6>
                        </div>
                    </div>
                    <div class="flex-auto px-4 lg:px-10 py-10 pt-0">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />

                        <hr class="mt-6 border-b-1 border-[var(--color-border)]"/>

                        <UpdatePasswordForm/>
                    </div>
                </div>
            </div>
            <div class="min-w-0 lg:col-span-4">
                <CardProfile/>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

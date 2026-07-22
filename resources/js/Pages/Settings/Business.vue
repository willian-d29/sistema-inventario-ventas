<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { useI18n } from '@/Composables/useI18n.js';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  settings: Object,
  documentTypes: Array,
  thermalWidths: Array,
  dateFormats: Array,
  timeFormats: Array,
});

const logoPreview = ref(props.settings.logo_url || null);
const showDeleteLogoDialog = ref(false);
const { t } = useI18n();
const configuredDefaultDocument = ['receipt', 'invoice'].includes(props.settings.default_sale_document)
  ? props.settings.default_sale_document
  : 'receipt';

const form = useForm({
  business_name: props.settings.business_name || 'LaraTory',
  legal_name: props.settings.legal_name || '',
  tax_id: props.settings.tax_id || '',
  address: props.settings.address || '',
  phone: props.settings.phone || '',
  email: props.settings.email || '',
  logo_path: null,
  currency_code: props.settings.currency_code || 'PEN',
  currency_symbol: props.settings.currency_symbol || 'S/',
  timezone: props.settings.timezone || 'America/Lima',
  date_format: props.settings.date_format || 'd/m/Y',
  time_format: props.settings.time_format || 'H:i',
  receipt_footer: props.settings.receipt_footer || '',
  thermal_paper_width: props.settings.thermal_paper_width || 80,
  thermal_show_logo: Boolean(props.settings.thermal_show_logo),
  thermal_show_customer: Boolean(props.settings.thermal_show_customer),
  thermal_show_payment_refs: Boolean(props.settings.thermal_show_payment_refs),
  print_copies: props.settings.print_copies || 1,
  auto_open_print_dialog: Boolean(props.settings.auto_open_print_dialog),
  return_to_pos_after_print: Boolean(props.settings.return_to_pos_after_print),
  keep_sale_confirmation: Boolean(props.settings.keep_sale_confirmation),
  default_sale_document: configuredDefaultDocument,
  cash_register_name: props.settings.cash_register_name || 'Caja',
});

const hasLogo = computed(() => Boolean(logoPreview.value));

function updateLogo(event) {
  const file = event.target.files?.[0] || null;
  form.logo_path = file;
  logoPreview.value = file ? URL.createObjectURL(file) : props.settings.logo_url;
}

function submit() {
  form.post(route('settings.update'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      form.logo_path = null;
    },
  });
}

function documentLabel(value, fallback) {
  return {
    receipt: t('states.receipt'),
    invoice: t('states.invoice'),
  }[value] || fallback;
}

function deleteLogo() {
  router.delete(route('settings.logo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      form.logo_path = null;
      logoPreview.value = null;
      showDeleteLogoDialog.value = false;
    },
  });
}
</script>

<template>
  <Head :title="t('settings.title')" />

  <AuthenticatedLayout>
    <template #breadcrumb>{{ t('settings.breadcrumb') }}</template>

    <form class="mx-auto max-w-6xl space-y-5 px-4" @submit.prevent="submit">
      <PageHeader :title="t('settings.title')" :description="t('settings.description')">
        <template #actions>
          <Link :href="route('settings.printer-test')" target="_blank" rel="noopener noreferrer" class="app-ui-button app-ui-button-md doc-button is-thermal">
            <i class="fas fa-print"></i>{{ t('settings.test_ticket') }}
          </Link>
          <AppButton type="submit" :loading="form.processing" icon="fa-save">{{ t('actions.save') }}</AppButton>
        </template>
      </PageHeader>

      <section class="ihc-panel p-5">
        <h2 class="text-lg font-bold text-slate-800">{{ t('settings.business_data') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <label class="block">
            <span class="ihc-label">{{ t('settings.business_name') }}</span>
            <input v-model="form.business_name" class="mt-1 w-full rounded-md border-slate-300" autocomplete="organization" />
            <InputError :message="form.errors.business_name" />
          </label>
          <label class="block">
            <span class="ihc-label">{{ t('settings.legal_name') }}</span>
            <input v-model="form.legal_name" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.legal_name" />
          </label>
          <label class="block">
            <span class="ihc-label">{{ t('settings.tax_id') }}</span>
            <input v-model="form.tax_id" inputmode="numeric" maxlength="11" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.tax_id" />
          </label>
          <label class="block">
            <span class="ihc-label">{{ t('settings.phone') }}</span>
            <input v-model="form.phone" class="mt-1 w-full rounded-md border-slate-300" autocomplete="tel" />
            <InputError :message="form.errors.phone" />
          </label>
          <label class="block md:col-span-2">
            <span class="ihc-label">{{ t('settings.address') }}</span>
            <input v-model="form.address" class="mt-1 w-full rounded-md border-slate-300" autocomplete="street-address" />
            <InputError :message="form.errors.address" />
          </label>
          <label class="block">
            <span class="ihc-label">{{ t('settings.email') }}</span>
            <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border-slate-300" autocomplete="email" />
            <InputError :message="form.errors.email" />
          </label>
          <div>
            <span class="ihc-label">{{ t('settings.logo') }}</span>
            <div class="mt-1 flex flex-wrap items-center gap-3">
              <div class="flex h-16 w-28 items-center justify-center rounded-md border border-slate-200 bg-slate-50">
                <img v-if="hasLogo" :src="logoPreview" :alt="t('settings.logo')" class="max-h-14 max-w-24 object-contain" />
                <strong v-else class="text-sm text-slate-700">{{ form.business_name || 'LaraTory' }}</strong>
              </div>
              <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                <i class="fas fa-upload"></i>{{ t('settings.upload') }}
                <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="updateLogo" />
              </label>
              <AppButton v-if="hasLogo" type="button" variant="danger" size="sm" icon="fa-trash" @click="showDeleteLogoDialog = true">
                {{ t('settings.remove') }}
              </AppButton>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ t('settings.logo_help') }}</p>
            <InputError :message="form.errors.logo_path" />
          </div>
        </div>
      </section>

      <section class="ihc-panel p-5">
        <h2 class="text-lg font-bold text-slate-800">{{ t('settings.regional_format') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-4">
          <label>
            <span class="ihc-label">{{ t('settings.currency') }}</span>
            <input v-model="form.currency_code" maxlength="3" class="mt-1 w-full rounded-md border-slate-300 uppercase" />
            <InputError :message="form.errors.currency_code" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.symbol') }}</span>
            <input v-model="form.currency_symbol" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.currency_symbol" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.timezone') }}</span>
            <input v-model="form.timezone" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.timezone" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.decimals') }}</span>
            <input v-model.number="form.decimal_point" type="number" min="0" max="8" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.decimal_point" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.date_format') }}</span>
            <select v-model="form.date_format" class="mt-1 w-full rounded-md border-slate-300">
              <option v-for="option in dateFormats" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <InputError :message="form.errors.date_format" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.time_format') }}</span>
            <select v-model="form.time_format" class="mt-1 w-full rounded-md border-slate-300">
              <option v-for="option in timeFormats" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <InputError :message="form.errors.time_format" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.tax') }}</span>
            <input v-model.number="form.tax" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.tax" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.discount') }}</span>
            <input v-model.number="form.discount" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.discount" />
          </label>
        </div>
      </section>

      <section class="ihc-panel p-5">
        <h2 class="text-lg font-bold text-slate-800">{{ t('settings.documents_printing') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <label class="md:col-span-3">
            <span class="ihc-label">{{ t('settings.footer_message') }}</span>
            <input v-model="form.receipt_footer" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.receipt_footer" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.default_document') }}</span>
            <select v-model="form.default_sale_document" class="mt-1 w-full rounded-md border-slate-300">
              <option v-for="option in documentTypes" :key="option.value" :value="option.value">{{ documentLabel(option.value, option.label) }}</option>
            </select>
            <InputError :message="form.errors.default_sale_document" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.paper_width') }}</span>
            <select v-model.number="form.thermal_paper_width" class="mt-1 w-full rounded-md border-slate-300">
              <option v-for="option in thermalWidths" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <InputError :message="form.errors.thermal_paper_width" />
          </label>
          <label>
            <span class="ihc-label">{{ t('settings.print_copies') }}</span>
            <input v-model.number="form.print_copies" type="number" min="1" max="5" class="mt-1 w-full rounded-md border-slate-300" />
            <InputError :message="form.errors.print_copies" />
          </label>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
          <label v-for="field in [
            ['thermal_show_logo', t('settings.flags.thermal_show_logo')],
            ['thermal_show_payment_refs', t('settings.flags.thermal_show_payment_refs')],
            ['auto_open_print_dialog', t('settings.flags.auto_open_print_dialog')],
            ['return_to_pos_after_print', t('settings.flags.return_to_pos_after_print')],
            ['keep_sale_confirmation', t('settings.flags.keep_sale_confirmation')]
          ]" :key="field[0]" class="flex min-h-12 items-center justify-between gap-4 rounded-md border border-slate-200 px-4 py-3">
            <span class="font-semibold text-slate-700">{{ field[1] }}</span>
            <input v-model="form[field[0]]" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
          </label>
        </div>
      </section>

      <section class="ihc-panel p-5">
        <h2 class="text-lg font-bold text-slate-800">{{ t('cash.title') }}</h2>
        <label class="mt-4 block max-w-md">
          <span class="ihc-label">{{ t('settings.register_name') }}</span>
          <input v-model="form.cash_register_name" class="mt-1 w-full rounded-md border-slate-300" />
          <InputError :message="form.errors.cash_register_name" />
        </label>
      </section>
    </form>

    <ConfirmDialog
      :show="showDeleteLogoDialog"
      :title="t('settings.delete_logo_title')"
      :action="t('settings.delete_logo_action')"
      :consequence="t('settings.delete_logo_consequence')"
      :confirm-text="t('settings.delete_logo_confirm')"
      @cancel="showDeleteLogoDialog = false"
      @confirm="deleteLogo"
    />
  </AuthenticatedLayout>
</template>

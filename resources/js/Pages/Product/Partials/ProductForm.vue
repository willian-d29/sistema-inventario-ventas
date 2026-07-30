<script setup>
import AsyncVueSelect from '@/Components/AsyncVueSelect.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import SmartImage from '@/Components/UI/SmartImage.vue';
import { useI18n } from '@/Composables/useI18n.js';
import defaultImage from '@/assets/img/default-image.jpg';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: 'create' },
  productPhoto: { type: String, default: null },
  showActions: { type: Boolean, default: true },
});

const emit = defineEmits(['submit']);
const { t } = useI18n();
const fileInput = ref(null);
const nameInput = ref(null);
const barcodeInput = ref(null);
const buyingPriceInput = ref(null);
const previewImage = ref(props.productPhoto || defaultImage);
const lookupLoading = ref(false);
const lookupState = ref('');
const scannerBuffer = ref('');
const scannerTimer = ref(null);
const barcodeLookupTimer = ref(null);
const lastScannerKeyAt = ref(0);
const lastLookupBarcode = ref('');
const SCANNER_DELAY = 90;
const SCANNER_RESET_MS = 260;
const LOOKUP_DELAY = 420;

const statusOptions = computed(() => [
  { value: 'active', label: t('states.active') },
  { value: 'inactive', label: t('states.inactive') },
]);

const submitLabel = computed(() => props.mode === 'edit' ? t('actions.save_changes') : t('actions.save'));
const submitIcon = computed(() => props.mode === 'edit' ? 'fa-save' : 'fa-plus');

function openFileDialog() {
  fileInput.value?.click();
}

function handleFileChange(event) {
  const file = event.target.files?.[0] ?? null;
  if (!file) return;

  previewImage.value = URL.createObjectURL(file);
  props.form.photo = file;
}

function isTextField(target) {
  return ['input', 'textarea', 'select'].includes(target?.tagName?.toLowerCase()) || target?.isContentEditable;
}

function isLikelyBarcode(code) {
  return /^\d{6,32}$/.test(String(code || '').trim());
}

function applyScannedBarcode(code) {
  props.form.barcode = code;
  if (!String(props.form.product_code || '').trim()) {
    props.form.product_code = code;
  }

  lookupBarcode(code, { advance: true });
}

function flushScannerBuffer() {
  window.clearTimeout(scannerTimer.value);
  const code = scannerBuffer.value.trim();
  scannerBuffer.value = '';

  if (!isLikelyBarcode(code)) return;

  applyScannedBarcode(code);
}

function handleScannerKeydown(event) {
  if (event.metaKey || event.ctrlKey || event.altKey || isTextField(event.target)) return;

  if (/^\d$/.test(event.key)) {
    const now = Date.now();
    if (now - lastScannerKeyAt.value > SCANNER_RESET_MS) {
      scannerBuffer.value = '';
    }

    lastScannerKeyAt.value = now;
    scannerBuffer.value += event.key;
    window.clearTimeout(scannerTimer.value);
    scannerTimer.value = window.setTimeout(flushScannerBuffer, SCANNER_DELAY);
    event.preventDefault();
    return;
  }

  if (event.key === 'Enter' && scannerBuffer.value) {
    event.preventDefault();
    flushScannerBuffer();
  }
}

function focusNextField(currentElement) {
  const formElement = currentElement?.closest?.('form');
  if (!formElement) return;

  const fields = [...formElement.querySelectorAll('input:not([type="hidden"]), select, textarea, button')]
    .filter((element) => !element.disabled && element.offsetParent !== null);
  const currentIndex = fields.indexOf(currentElement);
  fields[currentIndex + 1]?.focus?.();
}

function handleFormEnter(event) {
  const target = event.target;
  const tag = target?.tagName?.toLowerCase();

  if (tag === 'textarea' || tag === 'select') return;
  if (!['input', 'button'].includes(tag)) return;

  if (target.id === 'product_barcode') {
    event.preventDefault();
    const code = String(props.form.barcode || '').trim();
    if (isLikelyBarcode(code)) {
      lookupBarcode(code, { advance: true });
      return;
    }
  }

  if (tag === 'input') {
    event.preventDefault();
    focusNextField(target);
  }
}

async function lookupBarcode(code, options = {}) {
  const barcode = String(code || '').trim();
  if (!isLikelyBarcode(barcode) || barcode === lastLookupBarcode.value || props.mode !== 'create') return;

  lastLookupBarcode.value = barcode;
  lookupLoading.value = true;
  lookupState.value = '';

  try {
    const { data } = await window.axios.get(route('carts.barcode.lookup', barcode));

    if (data?.found) {
      if (!String(props.form.name || '').trim()) {
        props.form.name = data.name || '';
      }
      if (!String(props.form.description || '').trim()) {
        props.form.description = data.description || data.brand || '';
      }
      if (data.image_url && !props.form.photo) {
        previewImage.value = data.image_url;
      }
      lookupState.value = 'found';
      await nextTick();
      buyingPriceInput.value?.focus?.();
      return;
    }

    lookupState.value = 'manual';
    if (options.advance) {
      await nextTick();
      nameInput.value?.focus?.();
    }
  } catch (error) {
    lookupState.value = 'manual';
    if (options.advance) {
      await nextTick();
      nameInput.value?.focus?.();
    }
  } finally {
    lookupLoading.value = false;
  }
}

watch(() => props.productPhoto, (photo) => {
  if (!props.form.photo) {
    previewImage.value = photo || defaultImage;
  }
});

watch(() => props.form.barcode, (value) => {
  window.clearTimeout(barcodeLookupTimer.value);
  const barcode = String(value || '').trim();

  if (!isLikelyBarcode(barcode) || props.mode !== 'create') return;

  barcodeLookupTimer.value = window.setTimeout(() => {
    if (!String(props.form.product_code || '').trim()) {
      props.form.product_code = barcode;
    }
    lookupBarcode(barcode, { advance: false });
  }, LOOKUP_DELAY);
});

onMounted(() => window.addEventListener('keydown', handleScannerKeydown));
onUnmounted(() => {
  window.clearTimeout(scannerTimer.value);
  window.clearTimeout(barcodeLookupTimer.value);
  window.removeEventListener('keydown', handleScannerKeydown);
});

defineExpose({
  focusBarcode: () => barcodeInput.value?.focus?.(),
});
</script>

<template>
  <form class="space-y-3" @submit.prevent="emit('submit')" @keydown.enter="handleFormEnter">
    <section class="product-form-minimal-head" data-tour="product-form-photo">
      <button type="button" class="product-form-photo" @click="openFileDialog">
        <SmartImage :src="previewImage" :alt="form.name || t('products.photo_preview')" class="product-form-photo-preview" eager contain />
        <span class="product-form-photo-action">
          <i class="fas fa-camera" aria-hidden="true"></i>
          {{ t('products.change_photo') }}
        </span>
      </button>
      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileChange" />

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <span class="product-form-kicker">
            <i class="fas fa-box-open" aria-hidden="true"></i>
            {{ t('products.form_badge') }}
          </span>
          <span v-if="lookupLoading" class="product-form-scan-badge is-loading">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>{{ t('products.lookup_loading') }}
          </span>
          <span v-else-if="lookupState === 'found'" class="product-form-scan-badge is-found">
            <i class="fas fa-check" aria-hidden="true"></i>{{ t('products.lookup_found_short') }}
          </span>
          <span v-else-if="lookupState === 'manual'" class="product-form-scan-badge is-manual">
            <i class="fas fa-keyboard" aria-hidden="true"></i>{{ t('products.lookup_manual_short') }}
          </span>
        </div>
      </div>

      <AppSelect
        v-model="form.status"
        class="min-w-[11rem]"
        :label="t('common.status')"
        :options="statusOptions"
        required
        :error="form.errors.status"
      />
    </section>

    <section class="product-form-section" data-tour="product-form-identity">
      <div class="product-form-section-heading">
        <span><i class="fas fa-barcode" aria-hidden="true"></i></span>
        <h3>{{ t('products.section_identity') }}</h3>
      </div>

      <div class="product-form-grid">
        <AppInput
          ref="barcodeInput"
          id="product_barcode"
          v-model="form.barcode"
          :label="t('common.barcode')"
          :placeholder="t('products.barcode_placeholder')"
          inputmode="numeric"
          autocomplete="off"
          :error="form.errors.barcode"
        />
        <AppInput
          ref="nameInput"
          v-model="form.name"
          :label="t('common.name')"
          :placeholder="t('products.name_placeholder')"
          required
          autofocus
          autocomplete="off"
          :error="form.errors.name"
        />
      </div>
    </section>

    <section class="product-form-section" data-tour="product-form-classification">
      <div class="product-form-section-heading">
        <span><i class="fas fa-layer-group" aria-hidden="true"></i></span>
        <h3>{{ t('products.section_classification') }}</h3>
      </div>

      <div class="product-form-grid">
        <label class="app-ui-field">
          <span class="app-ui-label">{{ t('common.category') }} <span aria-hidden="true">*</span></span>
          <AsyncVueSelect
            v-model="form.category_id"
            resource="categories.index"
            :placeholder="t('products.select_category')"
            class="app-async-select"
          />
          <span v-if="form.errors.category_id" class="app-ui-error">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ form.errors.category_id }}
          </span>
        </label>

        <label class="app-ui-field">
          <span class="app-ui-label">{{ t('products.unit_type') }} <span aria-hidden="true">*</span></span>
          <AsyncVueSelect
            v-model="form.unit_type_id"
            resource="unit-types.index"
            :placeholder="t('products.select_unit')"
            class="app-async-select"
          />
          <span v-if="form.errors.unit_type_id" class="app-ui-error">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ form.errors.unit_type_id }}
          </span>
        </label>

        <label class="app-ui-field">
          <span class="app-ui-label">{{ t('products.supplier') }}</span>
          <AsyncVueSelect
            v-model="form.supplier_id"
            resource="suppliers.index"
            :placeholder="t('products.without_supplier')"
            class="app-async-select"
          />
          <span v-if="form.errors.supplier_id" class="app-ui-error">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ form.errors.supplier_id }}
          </span>
        </label>
      </div>
    </section>

    <section class="product-form-section" data-tour="product-form-stock-price">
      <div class="product-form-section-heading">
        <span><i class="fas fa-coins" aria-hidden="true"></i></span>
        <h3>{{ t('products.section_stock_price') }}</h3>
      </div>

      <div class="product-form-grid">
        <AppInput
          ref="buyingPriceInput"
          v-model="form.buying_price"
          :label="t('products.buying_price')"
          type="number"
          min="0"
          step="0.01"
          required
          :error="form.errors.buying_price"
        />
        <AppInput
          v-model="form.selling_price"
          :label="t('products.selling_price')"
          type="number"
          min="0.01"
          step="0.01"
          required
          :error="form.errors.selling_price"
        />
        <AppInput
          v-model="form.quantity"
          :label="t('common.quantity')"
          type="number"
          min="0"
          step="0.01"
          required
          :error="form.errors.quantity"
        />
        <AppInput
          v-model="form.buying_date"
          :label="t('products.buying_date')"
          type="date"
          :error="form.errors.buying_date"
        />
      </div>
    </section>

    <section class="product-form-section" data-tour="product-form-details">
      <div class="product-form-section-heading">
        <span><i class="fas fa-align-left" aria-hidden="true"></i></span>
        <h3>{{ t('products.section_details') }}</h3>
      </div>

      <div class="product-form-grid">
        <AppInput
          v-model="form.root"
          :label="t('products.root_product')"
          :placeholder="t('products.root_placeholder')"
          :error="form.errors.root"
        />
        <div class="md:col-span-2">
          <AppTextarea
            v-model="form.description"
            :label="t('common.description')"
            rows="3"
            :placeholder="t('products.description_placeholder')"
            :error="form.errors.description"
          />
        </div>
      </div>
    </section>

    <div v-if="showActions" class="product-form-actions">
      <AppButton :href="route('products.index')" variant="secondary" icon="fa-arrow-left">
        {{ t('actions.cancel') }}
      </AppButton>
      <AppButton type="submit" :icon="submitIcon" :loading="form.processing">
        {{ submitLabel }}
      </AppButton>
    </div>
  </form>
</template>

<script setup>
import {computed, nextTick, onMounted, onUnmounted, ref, watch} from 'vue';
import Button from "@/Components/Button.vue";
import SubmitButton from "@/Components/SubmitButton.vue";

const props = defineProps({
    title: {
        type: String,
    },
    formProcessing: {
        type: Boolean,
        default: false,
    },
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    showSubmitButton: {
        type: Boolean,
        default: true,
    },
    submitDisabled: {
        type: Boolean,
        default: false,
    },
    submitButtonText: {
        type: String,
        default: "Guardar",
    },
});

const emit = defineEmits(['close', 'submitAction']);
const dialogRef = ref(null);
let previouslyFocusedElement = null;

const titleId = computed(() => `modal-title-${String(props.title || 'dialog').toLowerCase().replace(/[^a-z0-9]+/g, '-')}`);

watch(
    () => props.show,
    () => {
        if (props.show) {
            previouslyFocusedElement = document.activeElement;
            document.body.style.overflow = 'hidden';
            nextTick(() => dialogRef.value?.focus());
        } else {
            document.body.style.overflow = null;
            previouslyFocusedElement?.focus?.();
        }
    }
);

const submitAction = () => {
    emit('submitAction');
};

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '4xl': 'max-w-4xl',
    }[props.maxWidth];
});
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <div v-show="show"
                 class="overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center flex"
                 scroll-region>
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-[var(--color-overlay)]"/>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        ref="dialogRef"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="titleId"
                        tabindex="-1"
                        class="mx-3 mb-6 max-h-[90vh] overflow-hidden bg-[var(--color-surface)] text-[var(--color-text-primary)] shadow-[var(--shadow-lg)] transform transition-all sm:w-full sm:mx-auto"
                        :class="maxWidthClass"
                        :style="{ borderRadius: 'var(--radius-lg)', border: '1px solid var(--color-border)' }"
                    >
                        <div
                            class="flex items-start justify-between border-b border-solid border-[var(--color-border)] p-3">
                            <h6 :id="titleId" class="text-lg font-semibold text-[var(--color-text-primary)]">
                                {{ title }}
                            </h6>
                            <button
                                class="ihc-icon-button ml-auto"
                                @click="close"
                                title="Cerrar"
                                aria-label="Cerrar modal"
                            >
                                <span
                                    class="block h-6 w-6 bg-transparent text-2xl text-[var(--color-text-primary)] opacity-100 outline-none focus:outline-none">
                                    <i class="fas fa-times text-base"></i>
                                </span>
                            </button>
                        </div>

                        <div class="max-h-[calc(90vh-4rem)] overflow-y-auto p-5">

                            <slot v-if="show"/>

                            <div class="mt-6 flex justify-end">
                                <Button type="gray" @click="close">Cancelar</Button>
                                <SubmitButton
                                    v-if="showSubmitButton"
                                    :processing="formProcessing"
                                    :disabled="submitDisabled"
                                    @click="submitAction"
                                    class="app-button app-button-primary mr-1"
                                >
                                    {{ submitButtonText }}
                                </SubmitButton>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

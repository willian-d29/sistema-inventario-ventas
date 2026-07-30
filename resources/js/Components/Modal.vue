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
                 class="fixed inset-0 z-[60] flex items-start justify-center overflow-x-hidden overflow-y-auto px-2 py-4 outline-none focus:outline-none sm:items-center sm:px-4"
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
                        class="mx-2 flex max-h-[94dvh] min-h-0 transform flex-col overflow-hidden rounded-[24px] border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] shadow-[0_40px_120px_rgba(15,23,42,0.16)] transition-all sm:mx-auto sm:w-full sm:rounded-[28px]"
                        :class="maxWidthClass"
                    >
                        <div
                            class="flex shrink-0 items-start justify-between gap-4 border-b border-solid border-[var(--color-border)] px-4 py-4 sm:px-6 sm:py-5">
                            <h6 :id="titleId" class="min-w-0 break-words text-lg font-semibold text-[var(--color-text-primary)]">
                                {{ title }}
                            </h6>
                            <button
                                class="ihc-icon-button ml-auto"
                                @click="close"
                                title="Cerrar"
                                aria-label="Cerrar modal"
                            >
                                <span
                                    class="block h-8 w-8 rounded-full bg-[var(--color-surface-alt)] text-center leading-8 text-[var(--color-text-primary)] outline-none focus:outline-none hover:bg-[var(--color-surface)] transition"
                                >
                                    <i class="fas fa-times text-sm"></i>
                                </span>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                            <slot v-if="show"/>
                        </div>

                        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-3 sm:flex-row sm:justify-end sm:px-6 sm:py-4">
                            <Button type="gray" @click="close">Cancelar</Button>
                            <SubmitButton
                                v-if="showSubmitButton"
                                :processing="formProcessing"
                                :disabled="submitDisabled"
                                @click="submitAction"
                                class="app-button app-button-primary"
                            >
                                {{ submitButtonText }}
                            </SubmitButton>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

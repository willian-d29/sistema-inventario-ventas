<script setup>
import InputError from "@/Components/InputError.vue";

const model = defineModel({
    type: [String, Number],
    required: true,
});

defineProps({
    label: {
        type: String,
    },
    name: {
        type: String,
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
    },
    errorMessage: {
        type: String,
    },
});

const emit = defineEmits(['keyupEnter']);
const keyupEnter = () => {
    emit('keyupEnter');
};
</script>

<template>
    <label :for="name" class="ihc-label">{{ label }}</label>
    <input
        :id="name"
        v-model="model"
        @keyup.enter="keyupEnter"
        :type="type"
        :placeholder="placeholder"
        class="ihc-field"
        :aria-invalid="errorMessage ? 'true' : 'false'"
        :aria-describedby="errorMessage ? `${name}-error` : null"
    />
    <InputError :id="`${name}-error`" :message="errorMessage"/>
</template>

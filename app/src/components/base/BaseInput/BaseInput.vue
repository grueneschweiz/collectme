<template>
  <div class="collectme-base-input">
    <label
        :for="id"
        :class="{'collectme-base-input__label--above':showLabelAbove}"
        class="collectme-base-input__label"
    >
      {{ label }}
      <span v-if="required">*</span>
    </label>
    <input
        v-model="currentValue"
        ref="input"
        @input="onInput"
        :id="id"
        :name="id"
        :required="required"
        :type="type"
        :autocomplete="autocomplete"
        :disabled="disabled"
        :class="{
          'collectme-base-input__field--is-empty':!currentValue,
          'collectme-base-input__field--invalid':isInvalid,
          'collectme-base-input__field--valid':isValid
        }"
        class="collectme-base-input__field"
    >

    <template v-if="helptext">
      <div class="collectme-base-input__helptext">
        {{ helptext }}
      </div>
    </template>

    <TransitionAppearFade>
      <template v-if="showInvalidMessage">
        <div class="collectme-base-input__validation-message">
          {{ validationMessage }}
        </div>
      </template>
    </TransitionAppearFade>
  </div>
</template>

<script setup lang="ts">
import type {PropType} from 'vue';
import {computed, onBeforeUnmount, onMounted, ref} from "vue";
import type {ValidationStatus} from "@/components/base/BaseInput/BaseInput";
import TransitionAppearFade from '@/components/transition/TransitionAppearFade.vue'

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
}>();

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  label: {
    type: String,
    required: true
  },
  required: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'text'
  },
  modelValue: {
    type: [String, Number],
  },
  autocomplete: {
    type: String,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  helptext: {
    type: String
  },
  validationStatus: {
    type: String as PropType<ValidationStatus>,
    default: 'unvalidated'
  },
  validationMessage: {
    type: String
  }
})

const input = ref<HTMLInputElement | null>(null)
const inputFocused = ref(false);

const currentValue = ref(props.modelValue)

const onInput = (e: Event) => {
  const input = e.target as HTMLInputElement;
  emit('update:modelValue', input.value);
}

const showLabelAbove = computed(() => {
  return (currentValue.value || inputFocused.value);
})

const showInvalidMessage = computed(() => {
  return props.validationStatus === 'invalid' && props.validationMessage;
})

const isInvalid = computed(() => {
  return props.validationStatus === 'invalid' && !inputFocused.value;
})

const isValid = computed(() => {
  return props.validationStatus === 'valid' && !inputFocused.value;
})

const setFocus = () => inputFocused.value = true;
const removeFocus = () => inputFocused.value = false;

onMounted(() => {
  input.value?.addEventListener('focus', setFocus);
  input.value?.addEventListener('blur', removeFocus);
})

onBeforeUnmount(() => {
  input.value?.removeEventListener('focus', setFocus)
  input.value?.removeEventListener('blur', removeFocus);
})

</script>

<style>

.collectme-base-input {
  position: relative;
  width: 100%;
  margin-top: 0.4em;
}

.collectme-base-input__label {
  position: absolute;
  left: 0.75rem;
  font-size: 1em;
  font-weight: normal;
  text-transform: none;
  top: 1.75rem;
  transition-timing-function: ease-in-out;
  transition-duration: var(--transition-speed-normal);
  transition-property: font-size, top, font-weight;
}

/*noinspection CssUnusedSymbol*/
.collectme-base-input__label--above {
  font-size: 0.625rem;
  font-weight: bold;
  text-transform: uppercase;
  top: 0;
}

.collectme-base-input__field {
  width: 100%;
  background: var(--color-grey-1);
  border: none;
  height: 2.625rem;
  color: var(--color-text);
  font-size: 1rem;
  padding-left: 0.75rem;
  margin-top: 1rem;
  border-bottom: 2px solid var(--color-grey-2);
  transition: border-bottom-color var(--transition-speed-fast) ease-in-out;
  border-radius: 2px;
}

/*noinspection CssUnusedSymbol*/
.collectme-base-input__field--valid {
  border-bottom-color: var(--color-primary);
}

/*noinspection CssUnusedSymbol*/
.collectme-base-input__field--invalid {
  border-bottom-color: var(--color-red);
}

.collectme-base-input__field:focus {
  outline: none;
  border-bottom: 2px solid var(--color-grey-3);
}

.collectme-base-input__helptext {
  font-size: 0.75em;
  color: var(--color-grey-3);
  margin-top: 0.4em;
}

.collectme-base-input__validation-message {
  font-size: 0.75em;
  color: var(--color-red);
  margin-top: 0.4em;
}

</style>
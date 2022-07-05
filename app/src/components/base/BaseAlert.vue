<template>
  <div
      class="collectme-base-alert"
      :class="{
          'collectme-base-alert--success': success,
          'collectme-base-alert--error': error
      }"
  >
    <div
        v-if="closable"
        class="collectme-base-alert__close"
        @click="$emit('close')"
    >
      <BaseButtonClose :secondary="error"/>
    </div>
    <div class="collectme-base-alert__message">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import BaseButtonClose from "@/components/base/BaseButtonClose.vue";

defineEmits(['close']);

const props = defineProps({
  closable: {
    type: Boolean,
    default: true,
  },
  success: {
    type: Boolean,
    default: true,
  },
  error: {
    type: Boolean,
    default: false,
  }
});
</script>

<style>

.collectme-base-alert {
  padding: clamp(10px, 10px + 10 * (100vw - 375px) / 1305, 20px);
  border-radius: 3px;
}

.collectme-base-alert--success {
  background-color: var(--color-primary-super-light);
}

.collectme-base-alert--error {
  background-color: var(--color-secondary-light);
}

.collectme-base-alert__close {
  float: right;
  width: 1em;
}
</style>
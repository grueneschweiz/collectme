<template>
  <div class="collectme-base-notice">
    <slot></slot>
    <BaseButtonClose
      v-if="props.dismissible"
      @click="$emit('dismiss')"
      class="collectme-base-notice__dismiss"
    />
  </div>
</template>

<script setup lang="ts">
import BaseButtonClose from "@/components/base/BaseButtonClose.vue";

defineEmits<{
  (e: "dismiss"): void;
}>();

const props = defineProps({
  dismissible: {
    type: Boolean,
    required: false,
    default: true,
  },
});
</script>

<style>
.collectme-base-notice {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: clamp(10px, 10px + 10 * (100vw - 375px) / 1305, 20px);
  background-color: var(--color-primary-super-light);
  padding: clamp(10px, 10px + 10 * (100vw - 375px) / 1305, 20px);
  border-radius: 5px;
  color: var(--color-primary-dark);
}

.collectme-base-notice__dismiss {
  width: 20px;
  stroke: var(--color-primary-dark);
  flex: none;
}
</style>

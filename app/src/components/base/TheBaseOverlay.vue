<template>
  <div class="collectme-the-base-overlay">
    <header
        v-if="$slots.header || props.closeable"
        class="collectme-the-base-overlay__header"
    >
      <h2
          class="collectme-the-base-overlay__title"
      >
        <slot name="header"></slot>
      </h2>
      <BaseButtonClose
          v-if="props.closeable"
          @click="$emit('close')"
          class="collectme-the-base-overlay__close"
      />
    </header>

    <main>
      <slot></slot>
    </main>

    <footer>
      <slot name="footer"></slot>
    </footer>
  </div>
</template>

<script setup lang="ts">
import BaseButtonClose from '@/components/base/BaseButtonClose.vue';

defineEmits<{
  (e: 'close'): void
}>()


const props = defineProps({
  closeable: {
    type: Boolean,
    required: false,
    default: true
  },
})

</script>

<style>
.collectme-the-base-overlay {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(280px, 100vw - 40px, 790px);
  height: calc(100vh - 200px);
  background-color: var(--color-white);
  box-shadow: 0 -20px 30px 10px rgba(217, 217, 217, 0.95);
  border-top: 2px solid var(--color-primary);
  z-index: 1;
  padding: clamp(10px, 10 * (100vw + 80px) / 455, 20px);
  overflow: scroll;
}

.admin-bar .collectme-the-base-overlay {
  height: calc(100vh - 246px);
}

.collectme-the-base-overlay__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: clamp(10px, 10 * (100vw + 80px) / 455, 20px);
}

.collectme-the-base-overlay__title {
}

.collectme-the-base-overlay__close {
  width: 30px;
  flex: none;
}
</style>
<template>
  <div class="collectme-the-base-overlay" ref="container">
    <header
      v-if="$slots.header || props.closeable"
      class="collectme-the-base-overlay__header"
    >
      <h2 class="collectme-the-base-overlay__title">
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

    <footer v-if="$slots.footer">
      <slot name="footer"></slot>
    </footer>
  </div>
</template>

<script setup lang="ts">
import BaseButtonClose from "@/components/base/BaseButtonClose.vue";
import { onBeforeUnmount, onMounted, ref } from "vue";

defineEmits<{
  (e: "close"): void;
}>();

const props = defineProps({
  closeable: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const container = ref<HTMLDivElement>();

function adjustHeightForSafariMobile() {
  if (!container.value) {
    return;
  }

  if (container.value?.offsetHeight > window.innerHeight) {
    container.value?.classList.add("collectme-the-base-overlay--mobile-safari");
  }
}

onMounted(() => {
  document.querySelector("body")?.classList.add("collectme-overlay-open");
  adjustHeightForSafariMobile();
});

onBeforeUnmount(() => {
  document.querySelector("body")?.classList.remove("collectme-overlay-open");
});
</script>

<style>
.collectme-the-base-overlay {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(280px, 100vw - 40px, 790px);
  min-height: calc(100vh - 200px);
  max-height: calc(100vh - 40px);
  background-color: var(--color-white);
  box-shadow: 0 -20px 30px 10px rgba(217, 217, 217, 0.95);
  border-top: 2px solid var(--color-primary);
  z-index: 999;
  padding: clamp(10px, 10 * (100vw + 80px) / 455, 20px);
  overflow-y: scroll;
}

/*noinspection CssUnusedSymbol*/
.collectme-the-base-overlay--mobile-safari {
  max-height: calc(100vh - 140px);
}

.admin-bar .collectme-the-base-overlay {
  height: calc(100vh - 86px);
}

/*noinspection CssUnusedSymbol*/
.admin-bar .collectme-the-base-overlay--mobile-safari {
  max-height: calc(100vh - 186px);
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

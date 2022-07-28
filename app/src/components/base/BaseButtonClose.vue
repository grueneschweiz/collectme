<template>
  <button class="collectme-base-button-close">
    <svg
      role="img"
      viewBox="0 0 30 30"
      preserveAspectRatio="xMinYMin meet"
      class="collectme-base-button-close__svg"
      :class="{
        'collectme-base-button-close__svg--primary': !secondary,
        'collectme-base-button-close__svg--secondary': secondary,
        'collectme-base-button-close__svg--muted': muted,
      }"
    >
      <g>
        <path stroke-width="2" fill="none" d="M29 1L1 29M1 1l28 28" />
      </g>
    </svg>
  </button>
</template>

<script setup lang="ts">
defineProps({
  secondary: {
    type: Boolean,
    default: false,
  },
  muted: {
    type: Boolean,
    default: false,
  },
});
</script>

<style>
.collectme-base-button-close {
  padding: 0;
}

.collectme-base-button-close__svg {
  width: 100%;
  height: 100%;
  transition-duration: var(--transition-speed-fast);
  transition-timing-function: ease-out;
}

.collectme-base-button-close__svg--primary {
  stroke: var(--color-primary);
}

.collectme-base-button-close__svg--primary:hover,
.collectme-base-button-close__svg--primary:focus {
  stroke: var(--color-primary-dark);
}

.collectme-base-button-close__svg--secondary {
  stroke: var(--color-secondary);
}

.collectme-base-button-close__svg--secondary:hover,
.collectme-base-button-close__svg--secondary:focus {
  stroke: var(--color-secondary-dark);
}

.collectme-base-button-close__svg--muted {
  stroke: var(--color-grey-2);
}

.collectme-base-button-close__svg--muted:hover,
.collectme-base-button-close__svg--muted:focus {
  stroke: var(--color-grey-3);
}
</style>

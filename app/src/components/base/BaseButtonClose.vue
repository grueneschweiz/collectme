<template>
  <button
      class="collectme-base-button-close"
  >
    <svg
        role="img"
        viewBox="0 0 30 30"
        preserveAspectRatio="xMinYMin meet"
        class="collectme-base-button-close__svg"
    >
      <g>
        <path stroke-width="2" fill="none" d="M29 1L1 29M1 1l28 28"/>
      </g>
    </svg>
  </button>
</template>

<script setup lang="ts">

</script>

<style>
.collectme-base-button-close {
  padding: 0;
}

.collectme-base-button-close__svg {
  stroke: var(--color-grey-2);
  width: 100%;
  height: 100%;
  transition-duration: var(--transition-speed-fast);
  transition-timing-function: ease-out;
}

.collectme-base-button-close__svg:hover,
.collectme-base-button-close__svg:focus {
  stroke: var(--color-primary);
}
</style>
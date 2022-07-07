<template>
  <div class="collectme-base-content-card">
    <div class="collectme-base-content-card__card">
      <slot name="default"></slot>
    </div>

    <div
        v-if="$slots.trailer"
        class="collectme-base-content-card__trailer"
    >
      <slot name="trailer"></slot>
    </div>

  </div>
</template>

<script setup lang="ts">

</script>

<style>
.collectme-base-content-card__card {
  background: var(--color-white);
  border-left: 2px solid var(--color-secondary);
  border-radius: 3px;
  padding: 0.5rem;
  box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.25);
}

.collectme-base-content-card__trailer {
  text-align: right;
  font-size: 0.875rem;
  color: var(--color-grey-3);
  padding: 0.25rem;
}
</style>
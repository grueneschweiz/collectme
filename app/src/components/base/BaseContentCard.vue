<template>
  <div class="collectme-base-content-card">
    <div
        class="collectme-base-content-card__card"
        :class="{'collectme-base-content-card__card--disabled': disabled}"
    >
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

defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})

</script>

<style>
.collectme-base-content-card__card {
  background: var(--color-white);
  border-left: 2px solid var(--color-secondary);
  border-radius: 3px;
  padding: 0.5rem;
  box-shadow: 1px 2px 6px 0 rgb(0 0 0 / 15%);
}

.collectme-base-content-card__card--disabled {
  border: 1px solid var(--color-grey-1);
  border-left: 2px solid var(--color-grey-3);
  box-shadow: none;
}

.collectme-base-content-card__trailer {
  text-align: right;
  font-size: 0.875rem;
  color: var(--color-grey-3);
  padding: 0.25rem;
}
</style>
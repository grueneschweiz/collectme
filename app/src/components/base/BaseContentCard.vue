<template>
  <div class="collectme-base-content-card">
    <button
      v-if="button"
      class="collectme-base-content-card__card collectme-base-content-card__card--btn"
      :class="{ 'collectme-base-content-card__card--disabled': disabled }"
      :disabled="disabled"
    >
      <slot name="default"></slot>
    </button>

    <div v-else class="collectme-base-content-card__card">
      <slot name="default"></slot>
    </div>

    <div v-if="$slots.trailer" class="collectme-base-content-card__trailer">
      <slot name="trailer"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  button: {
    type: Boolean,
    default: false,
  },
});
</script>

<style>
.collectme-base-content-card__card {
  background: var(--color-white);
  border-left: 2px solid var(--color-secondary);
  border-radius: 3px;
  padding: 0.5rem;
  box-shadow: 1px 2px 6px 0 rgb(0 0 0 / 15%);
  transition: ease 0.4s;
}

.collectme-base-content-card__card--btn:hover {
  box-shadow: 1px 2px 6px 0 rgb(225 0 120 / 25%);
  transform: scale(105%);
}

.collectme-base-content-card__card--disabled,
.collectme-base-content-card__card--disabled:hover {
  border: 1px solid var(--color-grey-1);
  border-left: 2px solid var(--color-grey-3);
  box-shadow: none;
  cursor: default;
  transform: none;
}

.collectme-base-content-card__trailer {
  text-align: right;
  font-size: 0.875rem;
  color: var(--color-grey-3);
  padding: 0.25rem;
}
</style>

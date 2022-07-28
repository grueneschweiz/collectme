<template>
  <a :href="url" class="collectme-base-share-button">
    <div class="collectme-base-share-button__svg">
      <slot />
    </div>
  </a>
</template>

<script setup lang="ts">
defineProps({
  url: {
    type: String,
    required: true,
  },
});
</script>

<style>
.collectme-base-share-button,
.collectme-base-share-button:hover,
.collectme-base-share-button:focus {
  text-decoration: none;
  box-shadow: none;
  background: none;
}

.collectme-base-share-button__svg {
  height: 100%;
  width: 100%;
  fill: var(--color-primary);
  transition: all 0.4s ease;
}

.collectme-base-share-button__svg:hover,
.collectme-base-share-button__svg:focus {
  fill: var(--color-primary-dark);
}
</style>

<template>
  <div class="collectme-the-snackbar">
    <TheSnackbarCard
        v-for="(snackbar, idx) in snackbarStore.snackbars"
        :key="idx"
        :snackbar="snackbar"
    />
  </div>
</template>

<script setup lang="ts">
import {useSnackbarStore} from "@/stores/SnackbarStore";
import TheSnackbarCard from "@/components/base/TheSnackbar/TheSnackbarCard.vue";

const snackbarStore = useSnackbarStore();
</script>

<style>
.collectme-the-snackbar {
  position: fixed;
  z-index: 9999;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(calc(100% - 1rem), calc(100% - 4vw), 768px);
  display: flex;
  flex-direction: column-reverse;
  align-items: stretch;
  gap: clamp(0.5rem, 2vw, 1rem);
  padding-bottom: clamp(0.5rem, 2vw, 1rem);
}
</style>
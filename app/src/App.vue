<script setup lang="ts">
import { RouterView } from "vue-router";
import TheSnackbar from "@/components/base/TheSnackbar/TheSnackbar.vue";
</script>

<template>
  <RouterView />
  <TheSnackbar />
</template>

<style>
@import "@/assets/base.css";
</style>

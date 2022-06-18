<script setup lang="ts">
import { RouterView } from 'vue-router'
import MyContribution from "@/components/specific/home/MyContribution.vue";
</script>

<template>
  <div class="collectme-home">
    <MyContribution />
  </div>
  <RouterView />
</template>

<style>

</style>
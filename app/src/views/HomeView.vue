<script setup lang="ts">
import { RouterView } from 'vue-router'
import MyContribution from "@/components/specific/home/MyContribution/MyContribution.vue";
import ActivityFeed from "@/components/specific/home/ActivityFeed/ActivityFeed.vue";
</script>

<template>
  <div class="collectme-home-view">
    <MyContribution />
    <ActivityFeed />
  </div>
  <RouterView />
</template>

<style>

</style>
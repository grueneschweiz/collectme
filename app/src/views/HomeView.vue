<template>
  <div class="collectme-home-view">
    <MyContribution :key="refreshKey" />
    <ActivityFeed :key="refreshKey" />
  </div>
  <RouterView @login="refreshKey++" />
</template>

<script setup lang="ts">
import { RouterView } from "vue-router";
import MyContribution from "@/components/specific/home/MyContribution/MyContribution.vue";
import ActivityFeed from "@/components/specific/home/ActivityFeed/ActivityFeed.vue";
import { ref } from "vue";

const refreshKey = ref(0);
</script>

<style>
/*noinspection CssUnusedSymbol*/
.collectme-overlay-open .collectme-home-view {
  filter: blur(1px);
}
</style>

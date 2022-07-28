<template>
  <Transition name="collectme-appear-fade-">
    <slot></slot>
  </Transition>
</template>

<script setup lang="ts"></script>

<!--suppress CssUnusedSymbol -->
<style>
.collectme-appear-fade--enter-active,
.collectme-appear-fade--leave-active {
  transition: opacity var(--transition-speed-normal) ease;
}

.collectme-appear-fade--enter-from,
.collectme-appear-fade--leave-to {
  opacity: 0;
}
</style>

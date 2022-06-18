<template>
  <div class="collectme-base-card">
    <header>
      <h3 class="collectme-base-card__title">
        <slot name="header"></slot>
      </h3>
    </header>
    <main>
      <slot></slot>
    </main>
    <footer>
      <slot name="footer"></slot>
    </footer>
  </div>
</template>

<script setup lang="ts">

</script>

<style>
  .collectme-base-card {
    background-color: var(--bg-base-card);
    border-bottom: 2px solid var(--color-primary);
    border-radius: 3px;
  }
</style>
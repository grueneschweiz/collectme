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

<script setup lang="ts"></script>

<style>
.collectme-base-card {
  background-color: var(--bg-base-card);
  border-bottom: 2px solid var(--color-primary);
  border-radius: 5px;
  padding: clamp(10px, calc(3vw), 20px);
  margin-bottom: clamp(20px, calc(6vw), 40px);
}

.collectme-base-card__title {
  margin-top: 0;
  margin-bottom: 1.5rem;
  text-align: center;
}
</style>

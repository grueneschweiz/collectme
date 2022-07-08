<template>
  <button
      :class="{
        'collectme-base-button--primary': !secondary && !outline,
        'collectme-base-button--secondary': secondary && !outline,
        'collectme-base-button--primary-outline': !secondary && outline,
        'collectme-base-button--secondary-outline': secondary && outline,
        'collectme-base-button--muted-outline': muted,
        'collectme-base-button--full-width': fullWidth,
        'collectme-base-button--disabled': disabled,
        ['collectme-base-button--size--'+size]: true
      }"
      :disabled="disabled"
      class="collectme-base-button"
  >
    <slot></slot>
  </button>
</template>

<script setup lang="ts">

import type {PropType} from "vue";

defineProps({
  outline: {
    type: Boolean,
    default: false,
  },
  secondary: {
    type: Boolean,
    default: false
  },
  muted: {
    type: Boolean,
    default: false
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  size: {
    type: String as PropType<'sm'|'md'|'lg'>,
    default: 'md'
  }
})

</script>

<style>
.collectme-base-button {
  font-size: 0.875rem;
  display: inline-block;
  line-height: 1.71428em;
  letter-spacing: .08928em;
  padding: clamp(6px, 6px + 4 * (100vw - 375px) / 1305, 10px) 1.57142em;
  border-radius: .14285rem;
  border-width: 1px;
  border-style: solid;
  font-family: var(--font-secondary);
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  cursor: pointer;
  transition: all var(--transition-speed-fast) ease;
}

.collectme-base-button--primary {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-white);
}

.collectme-base-button--primary:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

.collectme-base-button--primary-outline {
  background-color: transparent;
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.collectme-base-button--primary-outline:hover {
  border-color: var(--color-primary-dark);
  color: var(--color-primary-dark);
}

.collectme-base-button--secondary {
  background-color: var(--color-secondary);
  border-color: var(--color-secondary);
  color: var(--color-white);
}

.collectme-base-button--secondary:hover {
  background-color: var(--color-secondary-dark);
  border-color: var(--color-secondary-dark);
}

.collectme-base-button--secondary-outline {
  background-color: transparent;
  border-color: var(--color-secondary);
  color: var(--color-secondary);
}

.collectme-base-button--secondary-outline:hover {
  border-color: var(--color-secondary-dark);
  color: var(--color-secondary-dark);
}

.collectme-base-button--muted-outline {
  background-color: transparent;
  border-color: var(--color-grey-3);
  color: var(--color-grey-3);
}

.collectme-base-button--muted-outline:hover {
  background-color: transparent;
  border-color: var(--color-grey-4);
  color: var(--color-grey-4);
}

.collectme-base-button--disabled:hover {
  cursor: not-allowed;
}

.collectme-base-button--full-width {
  width: 100%;
}

/*noinspection CssUnusedSymbol*/
.collectme-base-button--size--sm {
  font-size: 0.75rem;
}

/*noinspection CssUnusedSymbol*/
.collectme-base-button--size--lg {
  font-size: 1rem;
}

</style>
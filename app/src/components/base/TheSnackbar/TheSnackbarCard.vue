<template>
  <div class="collectme-the-snackbar-card">
    <div
        class="collectme-the-snackbar-card__short"
    >
      {{ snackbar.shortDesc }}
    </div>

    <div
        class="collectme-the-snackbar-card__long"
        v-if="snackbar.longDesc"
    >
      {{ snackbar.longDesc }}
    </div>

    <div class="collectme-the-snackbar-card__action-group">
      <BaseLoader
          class="collectme-the-snackbar-card__loader"
          v-if="working"
      />

      <button
          class="collectme-the-snackbar-card__action"
          v-if="snackbar.actionLabel"
          :disabled="working"
          @click="triggerAction"
          ref="button"
      >
        {{ snackbar.actionLabel }}
      </button>
    </div>

  </div>
</template>

<script setup lang="ts">
import type {PropType} from "vue";
import {computed, onBeforeUnmount, onMounted, ref, watch} from "vue";
import type {Snackbar} from "@/stores/SnackbarStore";
import {useSnackbarStore} from "@/stores/SnackbarStore";
import BaseLoader from '@/components/base/BaseLoader/BaseLoader.vue';

const snackbarStore = useSnackbarStore()

const props = defineProps({
  snackbar: {
    type: Object as PropType<Snackbar>,
    required: true
  }
})

const working = ref(false);
const button = ref<HTMLButtonElement>()

function triggerAction() {
  button.value?.blur()

  if (!props.snackbar || 'function' !== typeof props.snackbar.action) {
    return;
  }

  if (autoHideTimer) {
    window.clearTimeout(autoHideTimer)
  }

  working.value = true
  props.snackbar
      .action()
      .then(() => working.value = false)
      .then(close)
      .catch(() => working.value = false)
}

function close() {
  if (props.snackbar) {
    snackbarStore.hide(props.snackbar)
  }
}

const vanishAfter = computed<number>(() => {
  if (!props.snackbar || 'undefined' === typeof props.snackbar.vanishAfter) {
    return 0
  }

  return props.snackbar.vanishAfter
})

let autoHideTimer: ReturnType<typeof setTimeout>

function initializeAutoHide() {
  if (vanishAfter.value) {
    window.setTimeout(close, vanishAfter.value)
  }
}

function disableAutoHide() {
  if (autoHideTimer) {
    window.clearTimeout(autoHideTimer)
  }
}

watch(vanishAfter, () => {
  disableAutoHide()
  initializeAutoHide()
});

onMounted(() => {
  initializeAutoHide()
})

onBeforeUnmount(() => {
  disableAutoHide()
})
</script>

<style>
.collectme-the-snackbar-card {
  background: var(--color-grey-4);
  color: var(--color-white);
  font-size: 0.875rem;
  line-height: 1.4em;
  border-radius: 3px;
  box-shadow: 1px 4px 10px 1px var(--color-grey-3);
}

.collectme-the-snackbar-card__short {
  font-weight: bold;
  padding: clamp(0.75rem, 3vw, 1rem);
  float: left;
}

.collectme-the-snackbar-card__long {
  padding: 0 clamp(0.75rem, 3vw, 1rem);
  float: left;
  clear: left;
}

.collectme-the-snackbar-card__action-group {
  float: right;
  padding: clamp(0.75rem, 3vw, 1rem);
  display: grid;
  grid-template-columns: [loader] 2.5rem [action] auto;
  align-items: center;
  column-gap: 0.5rem;
}

.collectme-the-snackbar-card__action {
  color: var(--color-primary);
  text-transform: uppercase;
  letter-spacing: 0.09em;
  font-size: 0.75rem;
  font-weight: bold;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  grid-area: action;
}

.collectme-the-snackbar-card__action:hover,
.collectme-the-snackbar-card__action:focus {
  background: var(--color-grey-3);
  color: var(--color-primary-super-light);
}

.collectme-the-snackbar-card__loader {
  width: 100%;
  grid-area: loader;
  margin: 0 !important;
}

</style>
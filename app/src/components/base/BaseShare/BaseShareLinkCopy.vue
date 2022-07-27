<template>
  <button @click="copyLink" class="collectme-base-share-link-copy__btn">
    <svg
      viewBox="0 0 32 32"
      stroke-width="0"
      class="collectme-base-share-link-copy__svg"
    >
      <path
        d="M10.956 14.528 8.85 16.635a4.606 4.607 0 1 0 6.514 6.515l2.807-2.808a4.606 4.606 0 0 0-1.271-7.414l-.9.9a1.538 1.539 0 0 0-.237.306 3.07 3.07 0 0 1 1.322 5.124l-2.805 2.806a3.072 3.073 0 1 1-4.345-4.345l1.218-1.216a6.169 6.17 0 0 1-.197-1.976z"
      />
      <path
        d="M13.829 11.657A4.606 4.606 0 0 0 15.1 19.07l1.19-1.191a3.07 3.07 0 0 1-1.376-5.138l2.806-2.807a3.072 3.073 0 1 1 4.344 4.346l-1.217 1.216c.172.645.238 1.312.196 1.976l2.107-2.107a4.606 4.607 0 1 0-6.514-6.515Z"
      />
    </svg>
    <div class="collectme-base-share-link-copy__copied" ref="copyEl">
      {{ t("General.BaseShare.BaseShareLinkCopy.copied") }}
    </div>
  </button>
</template>

<script setup lang="ts">
import t from "@/utility/i18n";
import { ref } from "vue";

const props = defineProps({
  url: {
    type: String,
    required: true,
  },
});

const copyEl = ref<HTMLDivElement>();

function copyLink() {
  navigator.clipboard.writeText(props.url).then(showMessage);
}

function showMessage() {
  copyEl.value?.classList.add(
    "collectme-base-share-link-copy__copied--visible"
  );

  window.setTimeout(() => {
    copyEl.value?.classList.remove(
      "collectme-base-share-link-copy__copied--visible"
    );
  }, 100);
}
</script>

<style>
.collectme-base-share-link-copy__btn {
  position: relative;
  background: none;
  border: none;
  padding: 0;
}

.collectme-base-share-link-copy__svg {
  height: 100%;
  width: 100%;
  fill: var(--color-primary);
  transition: all 0.4s ease;
}

.collectme-base-share-link-copy__svg:hover,
.collectme-base-share-link-copy__svg:focus {
  fill: var(--color-primary-dark);
}

.collectme-base-share-link-copy__copied {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  opacity: 0;
  transition: top ease-out 1s, opacity linear 0.5s 0.5s;
  text-transform: uppercase;
  font-size: 0.6125rem;
  color: var(--color-primary-dark);
  font-weight: bold;
  letter-spacing: 0.08928em;
  z-index: 1;
  font-family: var(--font-secondary);
}

/*noinspection CssUnusedSymbol*/
.collectme-base-share-link-copy__copied--visible {
  top: 50%;
  opacity: 1;
  transition: none;
}
</style>

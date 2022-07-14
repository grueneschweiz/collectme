<template>
  <svg
    class="collectme-base-loader"
    :class="`collectme-base-loader--${scheme}`"
    viewBox="0 0 120 30"
    xmlns="http://www.w3.org/2000/svg"
  >
    <circle cx="15" cy="15" r="15">
      <animate
        attributeName="r"
        from="15"
        to="15"
        begin="0s"
        dur="0.8s"
        values="15;9;15"
        calcMode="linear"
        repeatCount="indefinite"
      />
      <animate
        attributeName="fill-opacity"
        from="1"
        to="1"
        begin="0s"
        dur="0.8s"
        values="1;.5;1"
        calcMode="linear"
        repeatCount="indefinite"
      />
    </circle>
    <circle cx="60" cy="15" r="9" fill-opacity="0.3">
      <animate
        attributeName="r"
        from="9"
        to="9"
        begin="0s"
        dur="0.8s"
        values="9;15;9"
        calcMode="linear"
        repeatCount="indefinite"
      />
      <animate
        attributeName="fill-opacity"
        from="0.5"
        to="0.5"
        begin="0s"
        dur="0.8s"
        values=".5;1;.5"
        calcMode="linear"
        repeatCount="indefinite"
      />
    </circle>
    <circle cx="105" cy="15" r="15">
      <animate
        attributeName="r"
        from="15"
        to="15"
        begin="0s"
        dur="0.8s"
        values="15;9;15"
        calcMode="linear"
        repeatCount="indefinite"
      />
      <animate
        attributeName="fill-opacity"
        from="1"
        to="1"
        begin="0s"
        dur="0.8s"
        values="1;.5;1"
        calcMode="linear"
        repeatCount="indefinite"
      />
    </circle>
  </svg>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
import type { BaseLoaderScheme } from "@/components/base/BaseLoader/BaseLoader";

const props = defineProps({
  scheme: {
    type: String as PropType<BaseLoaderScheme>,
    default: "primary",
  },
});
</script>

<!--suppress CssUnusedSymbol -->
<style>
.collectme-base-loader {
  display: block;
}

.collectme-base-loader--primary {
  fill: var(--color-primary);
}

.collectme-base-loader--secondary {
  fill: var(--color-secondary);
}

.collectme-base-loader--muted {
  fill: var(--color-grey-3);
}

.collectme-base-loader--inverted {
  fill: var(--color-white);
}
</style>

<template>
  <div class="collectme-base-doughnut-chart">
    <svg viewBox="0 0 200 200" style="stroke-linecap: butt">
      <!-- Background circle -->
      <path
        :d="dBg"
        fill="transparent"
        :stroke="backgroundColor"
        :stroke-width="strokeWidth"
      />
      <!-- Move to start position, start drawing arc -->
      <path
        :d="d"
        fill="transparent"
        :stroke="foregroundColor"
        :stroke-width="strokeWidth"
      />
    </svg>
  </div>
</template>

<script setup lang="ts">
/**
 * This component is a simplified port of https://github.com/mazipan/vue-doughnut-chart
 * to Vue3 with composition api and typescript
 */

import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
  percent: {
    type: Number,
    default: 0,
  },
  foregroundColor: {
    type: String,
    default: "var(--color-primary)",
  },
  backgroundColor: {
    type: String,
    default: "var(--color-grey-2)",
  },
  strokeWidth: {
    type: Number,
    default: 10,
  },
  animationDelay: {
    type: Number,
    default: 1000,
  },
  animationDuration: {
    type: Number,
    default: 2000,
  },
});

const animatedValue = ref(0);
let delayTimer: ReturnType<typeof setTimeout> | null;

// If more than 50% filled we need to switch arc drawing mode from less than 180 deg to more than 180 deg
const largeArc = computed(() => {
  return animatedValue.value >= 50 ? "1" : "0";
});

const radius = computed(() => {
  return 100 - props.strokeWidth / 2;
});

// Where to put x coordinate of center of circle
const x = computed(() => {
  return 100;
});

// Where to put y coordinate of center of circle
const y = computed(() => {
  return 100 - radius.value;
});

// Calculate X coordinate of end of arc (+ 100 to move it to middle of image)
// add some rounding error to make arc not disappear at 100%
const endX = computed(() => {
  return -Math.sin(radians.value) * radius.value + 100 - 0.0001; // eslint-disable-line no-mixed-operators
});

// Calculate Y coordinate of end of arc (+ 100 to move it to middle of image)
const endY = computed(() => {
  return Math.cos(radians.value) * radius.value + 100; // eslint-disable-line no-mixed-operators
});

// Calculate length of arc in radians
const radians = computed(() => {
  const number = animatedValue.value;
  const degrees = (number / 100) * 360;
  const value = degrees - 180; // Turn the circle 180 degrees counterclockwise
  return (value * Math.PI) / 180;
});

// If we reach full circle we need to complete the circle, this ties into the rounding error in X coordinate above
const z = computed(() => {
  return animatedValue.value === 100 ? "z" : "";
});

const dBg = computed(() => {
  return `M ${x.value} ${y.value} A ${radius.value} ${radius.value} 0 1 1 ${
    x.value - 0.0001
  } ${y.value} z`;
});

const d = computed(() => {
  return `M ${x.value} ${y.value} A ${radius.value} ${radius.value} 0 ${largeArc.value} 1 ${endX.value} ${endY.value} ${z.value}`;
});

onMounted(() => {
  animate();
});

watch(
  () => props.percent,
  () => {
    if (delayTimer) {
      clearTimeout(delayTimer);
      delayTimer = null;
    }

    delayTimer = setTimeout(() => {
      animate();
    }, props.animationDelay!);
  }
);

function animate() {
  const initialValue = animatedValue.value;
  const targetValue = props.percent;
  const delta = targetValue - initialValue;

  if (0 === delta) {
    return;
  }

  const animationDuration = props.animationDuration;
  const frameDuration = 1000 / 60; // Calculate how long each 'frame' should last if we want to update the animation 60 times per second
  const totalFrames = Math.round(animationDuration / frameDuration); // Use that to calculate how many frames we need to complete the animation
  const easeOutQuad = (t: number) => t * (2 - t); // An ease-out function that slows the count as it progresses
  let frame = 0; // The animation function, which takes an Element

  const counter = setInterval(() => {
    frame++;
    const progress = easeOutQuad(frame / totalFrames); // Calculate our progress as a value between 0 and 1
    animatedValue.value = initialValue + delta * progress;
    if (frame === totalFrames) {
      clearInterval(counter);
    }
  }, frameDuration);
}
</script>

<style>
.collectme-base-doughnut-chart {
  width: 100%;
}
</style>

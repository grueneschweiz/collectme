<template>
  <div class="collectme-base-step-element">
    <div
        class="collectme-base-step-element__indicator collectme-base-step-element__indicator__liveline"
        :class="liveLineClasses"
    >
      <div class="collectme-base-step-element__icon">
        <svg
            v-if="status === 'pending'"
            viewBox="0 0 28 27"
            preserveAspectRatio="xMinYMin meet"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
          <mask id="path-1-outside-1_37_168" maskUnits="userSpaceOnUse" x="0" y="-0.460022" width="28" height="27" fill="black">
            <rect fill="white" y="-0.460022" width="28" height="27"/>
            <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z"/>
          </mask>
          <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z" fill="#A5A5A5"/>
          <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z" stroke="#F1F1F1" stroke-width="4" mask="url(#path-1-outside-1_37_168)"/>
        </svg>

        <svg
            v-if="status === 'active'"
            viewBox="0 0 28 28"
            preserveAspectRatio="xMinYMin meet"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
          <path d="M25 14L22.56 11.22L22.9 7.53998L19.29 6.71998L17.4 3.53998L14 4.99998L10.6 3.53998L8.71 6.71998L5.1 7.52998L5.44 11.21L3 14L5.44 16.78L5.1 20.47L8.71 21.29L10.6 24.47L14 23L17.4 24.46L19.29 21.28L22.9 20.46L22.56 16.78L25 14ZM12 19L8 15L9.41 13.59L12 16.17L18.59 9.57998L20 11L12 19Z" fill="#F1F1F1"/>
          <mask id="path-2-outside-1_37_166" maskUnits="userSpaceOnUse" x="0" y="0.5" width="28" height="27" fill="black">
            <rect fill="white" y="0.5" width="28" height="27"/>
            <path d="M25 14L22.6 11.2L22.9 7.5L19.3 6.7L17.4 3.5L14 5L10.6 3.5L8.7 6.7L5.1 7.5L5.4 11.2L3 14L5.4 16.8L5.1 20.5L8.7 21.3L10.6 24.5L14 23L17.4 24.5L19.3 21.3L22.9 20.5L22.6 16.8L25 14ZM20.7 18.9L18 19.5L16.6 21.9L14 20.8L11.4 21.9L10 19.5L7.3 18.9L7.5 16.1L5.7 14L7.5 11.9L7.3 9.1L10 8.5L11.4 6.1L14 7.2L16.6 6.1L18 8.5L20.7 9.1L20.5 11.9L22.3 14L20.5 16.1L20.7 18.9ZM18.6 9.6L20 11L12 19L8 15L9.4 13.6L12 16.2L18.6 9.6Z"/>
          </mask>
          <path d="M25 14L22.6 11.2L22.9 7.5L19.3 6.7L17.4 3.5L14 5L10.6 3.5L8.7 6.7L5.1 7.5L5.4 11.2L3 14L5.4 16.8L5.1 20.5L8.7 21.3L10.6 24.5L14 23L17.4 24.5L19.3 21.3L22.9 20.5L22.6 16.8L25 14ZM20.7 18.9L18 19.5L16.6 21.9L14 20.8L11.4 21.9L10 19.5L7.3 18.9L7.5 16.1L5.7 14L7.5 11.9L7.3 9.1L10 8.5L11.4 6.1L14 7.2L16.6 6.1L18 8.5L20.7 9.1L20.5 11.9L22.3 14L20.5 16.1L20.7 18.9ZM18.6 9.6L20 11L12 19L8 15L9.4 13.6L12 16.2L18.6 9.6Z" fill="#84B414"/>
          <path d="M25 14L22.6 11.2L22.9 7.5L19.3 6.7L17.4 3.5L14 5L10.6 3.5L8.7 6.7L5.1 7.5L5.4 11.2L3 14L5.4 16.8L5.1 20.5L8.7 21.3L10.6 24.5L14 23L17.4 24.5L19.3 21.3L22.9 20.5L22.6 16.8L25 14ZM20.7 18.9L18 19.5L16.6 21.9L14 20.8L11.4 21.9L10 19.5L7.3 18.9L7.5 16.1L5.7 14L7.5 11.9L7.3 9.1L10 8.5L11.4 6.1L14 7.2L16.6 6.1L18 8.5L20.7 9.1L20.5 11.9L22.3 14L20.5 16.1L20.7 18.9ZM18.6 9.6L20 11L12 19L8 15L9.4 13.6L12 16.2L18.6 9.6Z" stroke="#F1F1F1" stroke-width="4" mask="url(#path-2-outside-1_37_166)"/>
        </svg>

        <svg
            v-if="status === 'completed'"
            viewBox="0 0 28 27"
            preserveAspectRatio="xMinYMin meet"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
          <mask id="path-1-outside-1_37_136" maskUnits="userSpaceOnUse" x="0" y="-0.460022" width="28" height="27" fill="black">
            <rect fill="white" y="-0.460022" width="28" height="27"/>
            <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z"/>
          </mask>
          <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z" fill="#84B414"/>
          <path d="M25 13L22.56 10.22L22.9 6.53998L19.29 5.71998L17.4 2.53998L14 3.99998L10.6 2.53998L8.71 5.71998L5.1 6.52998L5.44 10.21L3 13L5.44 15.78L5.1 19.47L8.71 20.29L10.6 23.47L14 22L17.4 23.46L19.29 20.28L22.9 19.46L22.56 15.78L25 13ZM12 18L8 14L9.41 12.59L12 15.17L18.59 8.57998L20 9.99998L12 18Z" stroke="#F1F1F1" stroke-width="4" mask="url(#path-1-outside-1_37_136)"/>
        </svg>


      </div>
    </div>
    <div class="collectme-base-step-element__content">
      <h4
          class="collectme-base-step-element__title"
          :class="'collectme-base-step-element__title--' + status"
      >
        <slot name="title"></slot>
      </h4>
      <div class="collectme-base-step-element__body">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed, PropType} from "vue";
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElementTypes";

const props = defineProps({
  status: {
    type: String as PropType<StepStatus>,
    required: true
  },
  prev: {
    type: String as PropType<StepStatus>,
    required: false,
    default: null,
  },
  next: {
    type: String as PropType<StepStatus>,
    required: false,
    default: null,
  },
});

const liveLineClasses = computed(() => {
  const classes = [];

  if (props.prev) {
    classes.push('collectme-base-step-element__indicator__liveline--before--' + props.status)
  }

  if (props.next) {
    classes.push('collectme-base-step-element__indicator__liveline--after--' + props.next)
  }

  return classes.join(' ');
});
</script>

<!--suppress CssUnusedSymbol -->
<style>
.collectme-base-step-element {
  display: flex;
  align-items: stretch;
}

.collectme-base-step-element__indicator {
  width: 3rem;
  position: relative;
}

.collectme-base-step-element__icon {
  position: absolute;
  top: 1.8rem;
  left: 1.5rem;
  transform: translate(-50%, -50%);
  width: 2rem;
  z-index: 1;
}

.collectme-base-step-element__title {
  font-family: var(--font-primary);
  color: var(--color-text);
  font-weight: normal;
  font-size: 1rem;
  margin-top: 0.5rem;
}

.collectme-base-step-element__title--active {
  font-weight: 600;
}

.collectme-base-step-element__content {
  padding: 0.5rem 0;
}

.collectme-base-step-element__body {
  font-size: 0.75rem;
  color: var(--color-grey-3);
}

.collectme-base-step-element__indicator__liveline::before,
.collectme-base-step-element__indicator__liveline::after {
  content: '';
  position: absolute;
  left: calc(1.5rem - 2px);
  width: 4px;
}

.collectme-base-step-element__indicator__liveline::before {
  top: 0;
  height: 1.8rem;
}

.collectme-base-step-element__indicator__liveline::after {
  top: 1.8rem;
  height: calc(100% - 1.8rem);
}

.collectme-base-step-element__indicator__liveline--before--completed::before,
.collectme-base-step-element__indicator__liveline--after--completed::after {
  background-color: var(--color-primary);
}

.collectme-base-step-element__indicator__liveline--before--active::before,
.collectme-base-step-element__indicator__liveline--after--active::after {
  background-color: var(--color-primary-light);
}

.collectme-base-step-element__indicator__liveline--before--pending::before,
.collectme-base-step-element__indicator__liveline--after--pending::after {
  background-color: var(--color-grey-2);
}

</style>
<template>
  <div
    class="collectme-base-tooltip"
    :class="{ 'collectme-base-tooltip--open': open }"
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      viewBox="0 0 29.536 29.536"
      class="collectme-base-tooltip__icon"
      :class="{ 'collectme-base-tooltip__icon--open': open }"
      @click.stop="toggleClicked"
      @mouseover="showHover"
      @mouseout="hideHover"
      v-if="outline"
    >
      <path
        d="M14.768 0C6.611 0 0 6.609 0 14.768c0 8.155 6.611 14.767 14.768 14.767s14.768-6.612 14.768-14.767C29.535 6.609 22.924 0 14.768 0zm0 27.126c-6.828 0-12.361-5.532-12.361-12.359 0-6.828 5.533-12.362 12.361-12.362 6.826 0 12.359 5.535 12.359 12.362s-5.533 12.359-12.359 12.359z"
      />
      <path
        d="M14.385 19.337c-1.338 0-2.289.951-2.289 2.34 0 1.336.926 2.339 2.289 2.339 1.414 0 2.314-1.003 2.314-2.339-.027-1.389-.928-2.34-2.314-2.34zM14.742 6.092c-1.824 0-3.34.513-4.293 1.053l.875 2.804c.668-.462 1.697-.772 2.545-.772 1.285.027 1.879.644 1.879 1.543 0 .85-.67 1.697-1.494 2.701-1.156 1.364-1.594 2.701-1.516 4.012l.025.669h3.42v-.463c-.025-1.158.387-2.162 1.311-3.215.979-1.08 2.211-2.366 2.211-4.321 0-2.135-1.566-4.011-4.963-4.011z"
      />
    </svg>
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      viewBox="0 0 44.301 44.302"
      class="collectme-base-tooltip__icon"
      :class="{ 'collectme-base-tooltip__icon--open': open }"
      @click.stop="toggleClicked"
      @mouseover="showHover"
      @mouseout="hideHover"
      v-else
    >
      <path
        d="M22.15 0C9.918 0 0 9.917 0 22.15c0 12.234 9.918 22.151 22.15 22.151 12.233 0 22.151-9.917 22.151-22.151C44.301 9.917 34.384 0 22.15 0zm-.087 34.794c-1.649 0-2.771-1.214-2.771-2.833 0-1.65 1.151-2.833 2.771-2.833 1.681 0 2.771 1.183 2.802 2.833-.001 1.619-1.121 2.833-2.802 2.833zm4.42-13.548c-1.395 1.543-1.929 3.014-1.929 4.708v.119c0 .813-.659 1.474-1.474 1.474h-2.075a1.474 1.474 0 0 1-1.473-1.444l-.01-.512c-.113-1.921.527-3.849 2.222-5.883 1.205-1.431 2.185-2.621 2.185-3.902 0-1.318-.865-2.176-2.749-2.251-.742 0-1.576.163-2.336.436a1.473 1.473 0 0 1-1.907-.945l-.392-1.253a1.472 1.472 0 0 1 .856-1.804c1.327-.535 3.079-.947 5.059-.947 4.972 0 7.229 2.75 7.229 5.877.001 2.861-1.772 4.745-3.206 6.327z"
      />
    </svg>

    <div
      class="collectme-base-tooltip__message"
      :class="{ 'collectme-base-tooltip__message--visible': open }"
      ref="msgBox"
      @click.stop=""
    >
      <slot>The tooltip message</slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";

defineProps({
  outline: {
    type: Boolean,
    default: false,
  },
});

const marginSide = 10;

const msgBox = ref<HTMLDivElement>();
const open = ref(false);
const hover = ref(false);

function toggleClicked() {
  if (hover.value) {
    hover.value = false;
  } else {
    open.value = !open.value;
  }

  if (open.value) {
    addEventListener("click", close, { once: true });
  }
}

function close() {
  open.value = false;
}

function showHover() {
  if (!open.value) {
    open.value = true;
    hover.value = true;
  }
}

function hideHover() {
  if (hover.value) {
    hover.value = false;
    close();
  }
}

function setSize() {
  if (!msgBox.value) {
    return;
  }

  msgBox.value.classList.add("collectme-base-tooltip__message--initializing");

  msgBox.value.style.left = "0";

  const vw = document.documentElement.clientWidth;
  const tooltipDims = msgBox.value.getBoundingClientRect();

  const overflowRight = tooltipDims.left + tooltipDims.width - vw;
  if (overflowRight > -marginSide) {
    msgBox.value.style.left = `${-overflowRight - marginSide}px`;
  }

  const overflowLeft = tooltipDims.left;
  if (overflowLeft < marginSide) {
    msgBox.value.style.left = `${marginSide - overflowLeft}px`;
  }

  msgBox.value.classList.remove(
    "collectme-base-tooltip__message--initializing"
  );
}

onMounted(() => {
  addEventListener("resize", setSize);
  setSize();
});

onBeforeUnmount(() => {
  removeEventListener("resize", setSize);
  addEventListener("click", close);
});
</script>

<style>
.collectme-base-tooltip {
  display: inline-block;
  position: relative;
}

.collectme-base-tooltip__icon {
  fill: var(--color-grey-3);
  height: 1em;
  margin-bottom: -0.175em;
  cursor: pointer;
  transition: fill ease 0.2s;
}

.collectme-base-tooltip__icon--open,
.collectme-base-tooltip__icon:hover,
.collectme-base-tooltip__icon:focus {
  fill: var(--color-primary);
}

.collectme-base-tooltip--open:before {
  content: "";
  background: var(--color-grey-3);
  box-shadow: 1px 2px 6px 0 rgb(0 0 0 / 15%);
  width: 1em;
  height: 1em;
  position: absolute;
  transform: translateY(calc(-100% - 1px)) rotate(45deg);
}

.collectme-base-tooltip__message {
  display: none;
  position: absolute;
  max-width: min(280px, calc(100vw - 20px));
  width: max-content;
  background: var(--color-grey-3);
  color: var(--color-white);
  padding: 0.25em 0.5em;
  transform: translate(-50%, calc(-100% - 0.25em));
  top: 0;
  box-shadow: 1px 2px 6px 0 rgb(0 0 0 / 15%);
  z-index: 1;
}

/* noinspection CssUnusedSymbol */
.collectme-base-tooltip__message--initializing {
  display: block;
  opacity: 0;
}

/* noinspection CssUnusedSymbol */
.collectme-base-tooltip__message--visible {
  display: block;
  opacity: 1;
}
</style>

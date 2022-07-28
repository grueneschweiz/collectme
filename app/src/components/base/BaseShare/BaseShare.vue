<template>
  <div class="collectme-base-share">
    <div class="collectme-base-share__title">
      {{ t("General.BaseShare.share") }}
    </div>
    <BaseShareLinkCopy class="collectme-base-share__icon" :url="url" />
    <BaseShareButton
      class="collectme-base-share__icon"
      :url="`https://www.facebook.com/sharer.php?u=${encodedUrl}`"
    >
      <svg viewBox="0 0 32 32">
        <path
          stroke-width="0"
          fill-rule="evenodd"
          d="M19.869 16.491h-2.886V24h-2.94v-7.509H12V13.79h2.043v-2.308C14.043 9.666 15.248 8 18.028 8c1.125 0 1.958.106 1.958.106l-.066 2.522s-.848-.008-1.774-.008c-1.003 0-1.163.45-1.163 1.195V13.789H20l-.131 2.702z"
        />
      </svg>
    </BaseShareButton>
    <BaseShareButton
      class="collectme-base-share__icon"
      :url="`https://twitter.com/intent/tweet?url=${encodedUrl}`"
    >
      <svg viewBox="0 0 32 32">
        <path
          stroke-width="0"
          fill-rule="evenodd"
          d="M22.1155 11.0552c.6774-.4058 1.1973-1.0486 1.4426-1.8152a6.5543 6.5543 0 0 1-2.0847.7958A3.2786 3.2786 0 0 0 19.0773 9c-1.8125 0-3.2821 1.4694-3.2821 3.2815 0 .258.0285.5078.0848.7478-2.7285-.1373-5.147-1.4431-6.766-3.4293a3.2705 3.2705 0 0 0-.4448 1.651c0 1.1378.5791 2.1428 1.4606 2.7309a3.2702 3.2702 0 0 1-1.4869-.4095c0 1.632 1.1313 2.9574 2.6332 3.259a3.227 3.227 0 0 1-.865.1155 3.2211 3.2211 0 0 1-.6174-.0585c.4171 1.3036 1.6294 2.2531 3.066 2.2786-1.1238.8806-2.5386 1.4049-4.0773 1.4049-.2648 0-.5258-.015-.7824-.0458C9.4524 21.457 11.1785 22 13.0323 22c6.0382 0 9.339-5.0006 9.339-9.3375 0-.1432-.003-.285-.009-.4245A6.666 6.666 0 0 0 24 10.5391a6.5416 6.5416 0 0 1-1.8845.516z"
        />
      </svg>
    </BaseShareButton>
    <BaseShareButton class="collectme-base-share__icon" :url="emailUrl">
      <svg viewBox="0 0 32 32">
        <path
          stroke-width="0"
          fill-rule="evenodd"
          d="M7 10.867V21.11l5.897-5.265L7 10.866zM23.973 10H8.027L16 16.732 23.973 10zM16 18.467l-2.08-1.757L7.995 22h16.01l-5.926-5.29L16 18.467zm9 2.644V10.867l-5.897 4.979L25 21.111z"
        />
      </svg>
    </BaseShareButton>
    <BaseShareButton
      class="collectme-base-share__icon"
      :url="`https://wa.me/?text=${shareMsg}`"
    >
      <svg viewBox="0 0 32 32">
        <path
          stroke-width="0"
          fill-rule="evenodd"
          d="M24 15.794c0 4.304-3.516 7.794-7.854 7.794a7.863 7.863 0 0 1-3.797-.97L8 24l1.418-4.182a7.71 7.71 0 0 1-1.127-4.024C8.29 11.49 11.807 8 16.146 8 20.484 8 24 11.49 24 15.794zm-3.888 1.795c-.049-.08-.177-.127-.369-.223-.193-.095-1.14-.558-1.316-.621-.176-.064-.305-.096-.433.095s-.498.622-.61.75c-.112.127-.224.143-.417.047-.193-.095-.813-.298-1.549-.948a5.81 5.81 0 0 1-1.07-1.323c-.113-.19-.012-.294.083-.39.087-.086.193-.222.29-.334.096-.112.128-.192.192-.32.064-.127.032-.238-.016-.333-.048-.096-.433-1.036-.594-1.419-.16-.382-.32-.318-.433-.318-.112 0-.24-.016-.37-.016a.709.709 0 0 0-.512.239c-.176.19-.674.653-.674 1.593s.69 1.849.786 1.975c.096.128 1.331 2.12 3.289 2.885 1.957.765 1.957.51 2.31.477.353-.031 1.14-.461 1.3-.908.16-.446.16-.829.113-.908z"
        />
      </svg>
    </BaseShareButton>
  </div>
</template>

<script setup lang="ts">
import BaseShareLinkCopy from "@/components/base/BaseShare/BaseShareLinkCopy.vue";
import BaseShareButton from "@/components/base/BaseShare/BaseShareButton.vue";
import t from "@/utility/i18n";
import { computed } from "vue";

const props = defineProps({
  url: {
    type: String,
    required: true,
  },
  shareMsg: {
    type: String,
    default: "",
  },
  emailSubject: {
    type: String,
    default: "",
  },
});

const encodedUrl = computed(() => encodeURIComponent(props.url));

const shareMsg = computed(() =>
  encodeURIComponent((props.shareMsg?.trim() + " " + props.url).trim())
);

const emailUrl = computed(() => {
  const subject = encodeURIComponent(props.emailSubject?.trim());
  return `mailto:?subject=${subject}&body=${shareMsg.value}`;
});
</script>

<style>
.collectme-base-share {
  display: flex;
  align-items: center;
  justify-content: center;
}

.collectme-base-share__title {
  font-size: 0.75rem;
  color: var(--color-primary-dark);
  font-family: var(--font-secondary);
  font-weight: bold;
  text-transform: uppercase;
  cursor: pointer;
  letter-spacing: 0.08928em;
  margin-right: 0.75rem;
}

.collectme-base-share__icon {
  width: clamp(2rem, 6vw, 3rem);
  height: clamp(2rem, 6vw, 3rem);
}
</style>

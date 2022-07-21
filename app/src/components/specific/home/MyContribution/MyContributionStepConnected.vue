<template>
  <BaseStepElement :status="status" :next="next">
    <template #title>
      {{ t("HomeView.MyContribution.MyContributionStepConnected.connected") }}
    </template>
    <template #default>
      {{
        t("HomeView.MyContribution.MyContributionStepConnected.hello", {
          firstName: user?.attributes.firstName ?? "",
        })
      }}
      👋
    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
import type { StepStatus } from "@/components/base/BaseStepElement/BaseStepElement";
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import t from "@/utility/i18n";
import type { User } from "@/models/generated";

defineProps({
  status: {
    type: String as PropType<StepStatus>,
    required: true,
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
  user: {
    type: Object as PropType<User>,
  },
});
</script>

<style></style>

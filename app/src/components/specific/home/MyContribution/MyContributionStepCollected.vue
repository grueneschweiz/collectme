<template>
  <BaseStepElement :status="status" :prev="prev" :next="next">
    <template #title v-if="collected">
      {{ t("HomeView.MyContribution.MyContributionStepCollected.titleDone") }}
    </template>

    <template #title v-else>
      {{
        t("HomeView.MyContribution.MyContributionStepCollected.titlePending")
      }}
    </template>

    <template #default v-if="collected">
      {{
        t("HomeView.MyContribution.MyContributionStepCollected.collectedMsg")
      }}
      👏
    </template>

    <template #default v-else>
      <BaseButton
        size="sm"
        @click="$emit('collected')"
        :muted="status === 'pending'"
        secondary
        outline
      >
        {{
          t("HomeView.MyContribution.MyContributionStepCollected.collectedBtn")
        }}
      </BaseButton>
    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
import type { StepStatus } from "@/components/base/BaseStepElement/BaseStepElement";
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import BaseButton from "@/components/base/BaseButton.vue";
import t from "@/utility/i18n";

defineEmits(["collected"]);

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
  collected: {
    type: Boolean,
    required: true,
  },
});
</script>

<style></style>

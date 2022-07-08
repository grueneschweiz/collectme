<template>
  <BaseStepElement
      :status="status"
      :prev="prev"
      :next="next"
  >
    <template #title>
      {{t('HomeView.MyContribution.MyContributionStepEntered.title')}}
    </template>

    <template #default>
      <BaseButton
        size="sm"
        :secondary="status !== 'pending'"
        :muted="status === 'pending'"
        outline
        @click="$router.push('/home/enter-signatures')"
        :muted="status === 'pending'"
      >
        {{t('HomeView.MyContribution.MyContributionStepEntered.enterFirst')}}
      </BaseButton>
    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import type {PropType} from 'vue';
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import BaseStepElement from '@/components/base/BaseStepElement/BaseStepElement.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import t from "@/utility/i18n";

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
  signatures: {
    type: Number,
    required: true,
  }
});

</script>

<style>

</style>
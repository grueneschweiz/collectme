<template>
  <BaseStepElement
      :status="status"
      :prev="prev"
      :next="next"
  >
    <template
        #title
        v-if="objective"
    >
      {{t('HomeView.MyContribution.MyContributionStepObjective.goalSet')}}
    </template>
    <template
        #title
        v-else
    >
      {{t('HomeView.MyContribution.MyContributionStepObjective.setGoal')}}
    </template>

    <template
        #default
        v-if="objective"
    >
      {{t(
        'HomeView.MyContribution.MyContributionStepObjective.goal',
        {
          'date': (new Date(objective.attributes.created)).toLocaleDateString(),
          'count': objective.attributes.objective.toString(),
        }
    )}}
    </template>
    <template
        #default
        v-else
    >
      <BaseButton size="md">
        {{t('HomeView.MyContribution.MyContributionStepObjective.setGoalBtn')}}
      </BaseButton>
    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import type {PropType} from 'vue';
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import BaseStepElement from '@/components/base/BaseStepElement/BaseStepElement.vue';
import t from "@/utility/i18n";
import type {Objective} from "@/models/generated";
import BaseButton from '@/components/base/BaseButton.vue';

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
  objective: {
    type: Object as PropType<Objective>,
  }
});
</script>

<style>

</style>
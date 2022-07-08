<template>
  <BaseStepElement
      :status="status"
      :prev="prev"
  >
    <template
        #title
        v-if="fulfilled > 0"
    >
      {{
        t(
            'HomeView.MyContribution.MyContributionStepAchieved.titleSome',
            {percent: Math.round(fulfilled * 100).toString()}
        )
      }}
    </template>

    <template
        #title
        v-else
    >
      {{ t('HomeView.MyContribution.MyContributionStepAchieved.titleNone') }}
    </template>

    <template #default>
      <figure class="collectme-my-contribution-step-achieved__figure">
        <BaseDoughnutChart
            class="collectme-my-contribution-step-achieved__chart"
            :percent="Math.min(Math.round(fulfilled * 100), 100)"
            :stroke-width="40"
        />
        <figcaption class="collectme-my-contribution-step-achieved__caption" v-html="caption"/>
      </figure>

      <BaseButton
          v-if="fulfilled === 0"
          outline
          muted
          size="sm"
          @click="$router.push('/home/enter-signatures')"
      >
        {{ t('HomeView.MyContribution.MyContributionStepAchieved.registerSignaturesBtn') }}
      </BaseButton>

      <BaseButton
          v-else-if="fulfilled < 1 || (fulfilled >=1 && objective >= ObjectiveSizes.lg)"
          outline
          secondary
          size="sm"
          @click="$router.push('/home/enter-signatures')"
      >
        {{ t('HomeView.MyContribution.MyContributionStepAchieved.registerMoreSignaturesBtn') }}
      </BaseButton>

      <BaseButton
          v-else-if="fulfilled >= 1 && objective < ObjectiveSizes.lg"
          outline
          secondary
          size="sm"
          @click="$router.push('/home/set-goal')"
      >
        {{ t('HomeView.MyContribution.MyContributionStepAchieved.upgradeObjectiveBtn') }}
      </BaseButton>

    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import BaseButton from '@/components/base/BaseButton.vue'
import BaseDoughnutChart from "@/components/base/BaseDoughnutChart.vue";
import {ObjectiveSizes} from "@/components/specific/home/TheObjectiveSetter/ObjectiveSizes";
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import type {PropType} from "vue";
import {computed} from "vue";
import t from '@/utility/i18n';

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
    required: true
  },
  objective: {
    type: Number,
    required: true
  }
});

const fulfilled = computed<number>(() => {
  if (!props.signatures || !props.objective) {
    return 0
  }

  return props.signatures / props.objective
})

const caption = computed<string>(() => {
  return progressCaption.value + ' ' +
      t('HomeView.MyContribution.MyContributionStepAchieved.thank')
})

const progressCaption = computed<string>(() => {
  if (fulfilled.value >= 1) {
    return t(
        'HomeView.MyContribution.MyContributionStepAchieved.captionDone',
        {count: props.signatures.toString()}
    )
  }

  if (fulfilled.value > 0) {
    return t(
        'HomeView.MyContribution.MyContributionStepAchieved.captionWip',
        {count: props.signatures.toString(), goal: props.objective.toString()}
    )
  }

  return t('HomeView.MyContribution.MyContributionStepAchieved.captionPlaceholder')
})

</script>

<style>
.collectme-my-contribution-step-achieved__figure {
  display: flex;
  align-items: center;
  gap:  clamp(0.5rem, 2.5vw, 1rem);
  margin: 1em 0;
}

.collectme-my-contribution-step-achieved__chart {
  width: clamp(4rem, 18vw, 8rem);
  flex-shrink: 0;
}

.collectme-my-contribution-step-achieved__caption {
  padding-bottom: 1em;
}
</style>
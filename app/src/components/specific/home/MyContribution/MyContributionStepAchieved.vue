<template>
  <BaseStepElement :status="status" :prev="prev">
    <template #title v-if="fulfilled > 0">
      {{
        t("HomeView.MyContribution.MyContributionStepAchieved.titleSome", {
          percent: Math.round(fulfilled * 100).toString(),
        })
      }}
    </template>

    <template #title v-else>
      {{ t("HomeView.MyContribution.MyContributionStepAchieved.titleNone") }}
    </template>

    <template #default>
      <figure class="collectme-my-contribution-step-achieved__figure">
        <div class="collectme-my-contribution-step-achieved__chart">
          <BaseDoughnutChart
            class="collectme-my-contribution-step-achieved__chart-graph"
            :percent="Math.min(Math.round(fulfilled * 100), 100)"
            :stroke-width="6"
            @animation-finished="animateImage"
          />
          <img
            :src="myCurrentObjectiveSettings.img"
            alt="goal image"
            class="collectme-my-contribution-step-achieved__chart-img"
            :class="`collectme-my-contribution-step-achieved__chart-img--${myCurrentObjectiveSettings.objective}`"
            ref="imageElement"
          />
        </div>
        <figcaption
          class="collectme-my-contribution-step-achieved__caption"
          v-html="caption"
        />
      </figure>

      <BaseButton
        v-if="fulfilled === 0"
        outline
        muted
        size="sm"
        @click="$router.push('/home/enter-signatures')"
      >
        {{
          t(
            "HomeView.MyContribution.MyContributionStepAchieved.registerSignaturesBtn"
          )
        }}
      </BaseButton>

      <BaseButton
        v-else-if="
          fulfilled < 1 || (fulfilled >= 1 && objective >= greatestObjective)
        "
        outline
        secondary
        size="sm"
        @click="$router.push('/home/enter-signatures')"
      >
        {{
          t(
            "HomeView.MyContribution.MyContributionStepAchieved.registerMoreSignaturesBtn"
          )
        }}
      </BaseButton>

      <BaseButton
        v-else-if="fulfilled >= 1 && objective < greatestObjective"
        outline
        secondary
        size="sm"
        @click="$router.push('/home/set-goal')"
      >
        {{
          t(
            "HomeView.MyContribution.MyContributionStepAchieved.upgradeObjectiveBtn"
          )
        }}
      </BaseButton>
    </template>
  </BaseStepElement>
</template>

<script setup lang="ts">
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import BaseButton from "@/components/base/BaseButton.vue";
import BaseDoughnutChart from "@/components/base/BaseDoughnutChart.vue";
import type { StepStatus } from "@/components/base/BaseStepElement/BaseStepElement";
import { ref, type PropType } from "vue";
import { computed } from "vue";
import t from "@/utility/i18n";
import { useObjectiveSettings } from "@/components/specific/home/TheObjectiveSetter/ObjectiveSettings";
import { myCurrentObjectiveSettings } from "@/components/specific/home/MyContribution/MyContributionCurrentObjectiveSettings";

const props = defineProps({
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
  signatures: {
    type: Number,
    required: true,
  },
  objective: {
    type: Number,
    required: true,
  },
});

const imageElement = ref<HTMLImageElement>();

const greatestObjective = useObjectiveSettings().getGreatest().objective;

const fulfilled = computed<number>(() => {
  if (!props.signatures || !props.objective) {
    return 0;
  }

  return Math.max(0, props.signatures / props.objective);
});

const caption = computed<string>(() => {
  return (
    progressCaption.value +
    " " +
    t("HomeView.MyContribution.MyContributionStepAchieved.thank")
  );
});

const progressCaption = computed<string>(() => {
  if (fulfilled.value >= 1) {
    return t("HomeView.MyContribution.MyContributionStepAchieved.captionDone", {
      count: props.signatures.toString(),
    });
  }

  if (fulfilled.value > 0) {
    return t("HomeView.MyContribution.MyContributionStepAchieved.captionWip", {
      count: props.signatures.toString(),
      goal: props.objective.toString(),
    });
  }

  return t(
    "HomeView.MyContribution.MyContributionStepAchieved.captionPlaceholder"
  );
});

function animateImage() {
  imageElement.value?.classList.add(
    "collectme-my-contribution-step-achieved__chart-img--animated"
  );

  window.setTimeout(() => {
    imageElement.value?.classList.remove(
      "collectme-my-contribution-step-achieved__chart-img--animated"
    );
  }, 1000);
}
</script>

<style>
.collectme-my-contribution-step-achieved__figure {
  display: flex;
  align-items: center;
  gap: clamp(0.5rem, 2.5vw, 1rem);
  margin: 1em 0;
}

.collectme-my-contribution-step-achieved__chart {
  width: clamp(4rem, 18vw, 8rem);
  flex-shrink: 0;
  position: relative;
}

.collectme-my-contribution-step-achieved__chart-img {
  width: 100%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  transition: 0.2s ease;
}

/*noinspection CssUnusedSymbol*/
.collectme-my-contribution-step-achieved__chart-img--animated {
  animation-name: pulsate;
  animation-direction: alternate;
  animation-duration: 0.5s;
  animation-iteration-count: 2;
  animation-timing-function: ease-in-out;
}

.collectme-my-contribution-step-achieved__caption {
  padding-bottom: 1em;
}

@keyframes pulsate {
  0% {
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    transform: translate(-50%, -50%) scale(1.2);
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
  }
}
</style>

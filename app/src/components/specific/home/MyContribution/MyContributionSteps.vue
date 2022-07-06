<template>

  <BaseStepElement
      :status="connectedStatus"
      :next="objectiveStatus"
      next="completed"
  >
    <template #title>
      {{t('HomeView.MyContribution.Steps.connected')}}
    </template>
    <template #default>
      {{t('HomeView.MyContribution.Steps.hello', {firstName: userStore.me?.attributes.firstName ?? ''})}}
    </template>
  </BaseStepElement>

  <BaseStepElement
      :status="objectiveStatus"
      :prev="connectedStatus"
      :next="collectedStatus"
  >
    <template
        #title
        v-if="myObjective"
    >
      {{t('HomeView.MyContribution.Steps.goalSet')}}
    </template>
    <template
        #title
        v-else
    >
      {{t('HomeView.MyContribution.Steps.setGoal')}}
    </template>

    <template
        #default
        v-if="myObjective"
    >
      {{t(
        'HomeView.MyContribution.Steps.goal',
        {
          'date': (new Date(myObjective.attributes.created)).toLocaleDateString(),
          'count': myObjective.attributes.objective,
        }
    )}}
    </template>
    <template
      #default
      v-else
    >
      go go go // todo: continue here
    </template>
  </BaseStepElement>

  <BaseStepElement
      :status="collectedStatus"
      :prev="objectiveStatus"
      :next="enteredStatus"
  >
    <template #title>
      Unterschriften gesammelt
    </template>
    <template #default>
      hallo Cyrill
    </template>
  </BaseStepElement>

  <BaseStepElement
      :status="enteredStatus"
      :prev="collectedStatus"
      :next="achievedStatus"
  >
    <template #title>
      Unterschriften eingetragen
    </template>
    <template #default>
      hallo Cyrill
    </template>
  </BaseStepElement>

  <BaseStepElement
      :status="achievedStatus"
      :prev="enteredStatus"
  >
    <template #title>
      Sammelziel erreicht
    </template>
    <template #default>
      hallo Cyrill
    </template>
  </BaseStepElement>

</template>

<script setup lang="ts">
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import {useUserStore} from "@/stores/UserStore";
import t from "@/utility/i18n";
import {useGroupStore} from "@/stores/GroupStore";
import {computed, ref} from "vue";
import type {Group, Objective} from "@/models/generated";
import {useObjectiveStore} from "@/stores/ObjectiveStore";

enum Step {
  'connected' = 0,
  'objective' = 1,
  'collected' = 2,
  'entered' = 3,
  'achieved' = 4,
}

const userStore = useUserStore();
const groupStore = useGroupStore();
const objectiveStore = useObjectiveStore();
groupStore.fetch();

const collected = ref(false); // todo: initialize true if signatures entered

const myPersonalGroup = computed<Group|null>(() => {
  return groupStore.myPersonalGroup
})

const myObjective = computed<Objective|null>(() => {
  if (!myPersonalGroup.value?.id) {
    return null;
  }

  const objectives = objectiveStore.getObjectivesByGroupId(<string>(<Group>myPersonalGroup.value).id)

  if (!objectives.length) {
    return null;
  }

  let highest: Objective = objectives[0];

  objectives.forEach(objective => {
    if (objective.attributes.objective > highest.attributes.objective) {
      highest = objective
    }
  });

  return highest;
})

const activeStep = computed<Step>(() => {
  if (!userStore.me){
    return Step.connected
  } else if (!myObjective.value) {
    return Step.objective
  } else if (!collected.value) {
    return Step.collected
  } // todo
})

const connectedStatus = computed<StepStatus>(() => {
  return statusOf(Step.connected)
});

const objectiveStatus = computed<StepStatus>(() => {
  return statusOf(Step.objective)
});

const collectedStatus = computed<StepStatus>(() => {
  return statusOf(Step.collected)
});

const enteredStatus = computed<StepStatus>(() => {
  return statusOf(Step.entered)
});

const achievedStatus = computed<StepStatus>(() => {
  return statusOf(Step.achieved)
});

function statusOf(step: Step): StepStatus {
  if (step === activeStep.value) {
    return 'active'
  }

  return step < activeStep.value ? 'completed' : 'pending'
}

</script>

<style>

</style>
<template>

  <MyContributionStepConnected
      :status="connectedStatus"
      :next="objectiveStatus"
      :user="userStore.me ?? undefined"
  />

  <MyContributionStepObjective
      :status="objectiveStatus"
      :prev="connectedStatus"
      :next="collectedStatus"
      :objective="myObjective ?? undefined"
  />

  <MyContributionStepCollected
      :status="collectedStatus"
      :prev="objectiveStatus"
      :next="enteredStatus"
      :collected="hasSignatures"
      @collected="collected = true"
  />

  <MyContributionStepEntered
      :status="enteredStatus"
      :prev="collectedStatus"
      :next="achievedStatus"
      :signatures="myCount"
  />

  <MyContributionStepAchieved
      :status="achievedStatus"
      :prev="enteredStatus"
      :signatures="myCount"
      :objective="myObjective?.attributes.objective ?? 0"
  />

</template>

<script setup lang="ts">
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import {useUserStore} from "@/stores/UserStore";
import {useGroupStore} from "@/stores/GroupStore";
import {computed, ref} from "vue";
import type {Group, Objective} from "@/models/generated";
import {useObjectiveStore} from "@/stores/ObjectiveStore";
import MyContributionStepConnected from '@/components/specific/home/MyContribution/MyContributionStepConnected.vue';
import MyContributionStepObjective from "@/components/specific/home/MyContribution/MyContributionStepObjective.vue";
import MyContributionStepCollected from "@/components/specific/home/MyContribution/MyContributionStepCollected.vue";
import MyContributionStepEntered from "@/components/specific/home/MyContribution/MyContributionStepEntered.vue";
import MyContributionStepAchieved from "@/components/specific/home/MyContribution/MyContributionStepAchieved.vue";
import {useObjectiveSettings} from "@/components/specific/home/TheObjectiveSetter/ObjectiveSettings";

enum Step {
  'connected' = 0,
  'objective' = 1,
  'collected' = 2,
  'entered' = 3,
  'achieved' = 4,
  'completed' = 5,
}

const userStore = useUserStore();
const groupStore = useGroupStore();
const objectiveStore = useObjectiveStore();
groupStore.fetch();

const greatestObjective = useObjectiveSettings().getGreatest().objective;

const collected = ref(false);

const myPersonalGroup = computed<Group | null>(() => {
  return groupStore.myPersonalGroup
})

const myObjective = computed<Objective | null>(() => {
  if (!myPersonalGroup.value?.id) {
    return null;
  }

  return objectiveStore.getHighestObjectiveByGroupId(<string>(<Group>myPersonalGroup.value).id)
})

const myCount = computed<number>(() => {
  return myPersonalGroup.value?.attributes.signatures ?? 0
});

const hasSignatures = computed<boolean>(() => {
  return collected.value || myCount.value > 0
})

const activeStep = computed<Step>(() => {
  if (!userStore.me) {
    return Step.connected
  } else if (!myObjective.value) {
    return Step.objective
  } else if (!hasSignatures.value) {
    return Step.collected
  } else if (myCount.value <= 0) {
    return Step.entered
  } else if (myCount.value < greatestObjective) {
    return Step.achieved
  }

  return Step.completed
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
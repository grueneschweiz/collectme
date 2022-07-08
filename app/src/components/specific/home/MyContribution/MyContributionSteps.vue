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
      :collected="collected"
      @collected="collected = true"
  />

  <MyContributionStepEntered
      :status="enteredStatus"
      :prev="collectedStatus"
      :next="achievedStatus"
      :signatures="myPersonalGroup?.attributes.signatures ?? 0"
  />

  <MyContributionStepAchieved
      :status="achievedStatus"
      :prev="enteredStatus"
      :signatures="myPersonalGroup?.attributes.signatures ?? 0"
      :objective="myObjective?.attributes.objective ?? 0"
  />

</template>

<script setup lang="ts">
import type {StepStatus} from "@/components/base/BaseStepElement/BaseStepElement";
import {useUserStore} from "@/stores/UserStore";
import {useGroupStore} from "@/stores/GroupStore";
import {computed, ref, watch} from "vue";
import type {Group, Objective} from "@/models/generated";
import {useObjectiveStore} from "@/stores/ObjectiveStore";
import MyContributionStepConnected from '@/components/specific/home/MyContribution/MyContributionStepConnected.vue';
import MyContributionStepObjective from "@/components/specific/home/MyContribution/MyContributionStepObjective.vue";
import MyContributionStepCollected from "@/components/specific/home/MyContribution/MyContributionStepCollected.vue";
import MyContributionStepEntered from "@/components/specific/home/MyContribution/MyContributionStepEntered.vue";
import MyContributionStepAchieved from "@/components/specific/home/MyContribution/MyContributionStepAchieved.vue";

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

watch(myCount, newCount => {
  if (newCount > 0){
    collected.value = true;
  }
})

const collected = ref(false);

const activeStep = computed<Step>(() => {
  if (!userStore.me) {
    return Step.connected
  } else if (!myObjective.value) {
    return Step.objective
  } else if (!collected.value) {
    return Step.collected
  } else if (myCount.value <= 0) {
    return Step.entered
  } else if (myCount.value <= myObjective.value?.attributes.objective) {
    return Step.achieved
  }

  return Step.objective
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
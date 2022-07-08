<template>
  <TheBaseOverlay
      :closeable="true"
      @close="$router.back()"
  >
    <template #header>
      {{ t('HomeView.TheObjectiveSetter.title') }}
    </template>

    <template #default>
      <div
          class="collectme-the-base-overlay__intro"
          v-html="t('HomeView.TheObjectiveSetter.intro')"
      />

      <div class="collectme-the-objective-setter__card-wrapper">
        <TheObjectiveSetterCard
            v-for="key in Object.keys(ObjectiveSizes)"
            :key="key"
            :count="ObjectiveSizes[key]"
            :img="`${assetBaseUrl}/img/goal-${key}.png`"
            :disabled="disabled(ObjectiveSizes[key])"
            :ribbon="ribbon(ObjectiveSizes[key])"
            @saved="$router.back()"
        />
      </div>

    </template>
  </TheBaseOverlay>
</template>

<script setup lang="ts">
import TheBaseOverlay from '@/components/base/TheBaseOverlay.vue'
import TheObjectiveSetterCard from "@/components/specific/home/TheObjectiveSetter/TheObjectiveSetterCard.vue";
import {ObjectiveSizes} from "@/components/specific/home/TheObjectiveSetter/ObjectiveSizes";
import t from '@/utility/i18n';
import {useGroupStore} from "@/stores/GroupStore";
import {computed} from "vue";
import {useObjectiveStore} from "@/stores/ObjectiveStore";

const assetBaseUrl = collectme.assetBaseUrl;

const signatureCount = computed<number>(() => {
  return useGroupStore().myPersonalGroup?.attributes.signatures ?? 0;
});

const currentObjective = computed<number>(() => {
  const groupId = useGroupStore().myPersonalGroup?.id;

  if (!useGroupStore().myPersonalGroup?.id) {
    return 0;
  }

  return useObjectiveStore().getHighestObjectiveByGroupId(<string>groupId)?.attributes.objective ?? 0;
})

function disabled(objective: number): boolean {
  return signatureCount.value >= objective || currentObjective.value >= objective
}

function ribbon(objective: number): string | undefined {
  let defaultValue = undefined

  if (objective === ObjectiveSizes.lg && !currentObjective.value) {
    defaultValue = t('HomeView.TheObjectiveSetter.ribbonHot')
  }

  if (currentObjective.value === objective) {
    defaultValue = t('HomeView.TheObjectiveSetter.ribbonSelected')
  }

  return signatureCount.value >= objective ? t('HomeView.TheObjectiveSetter.ribbonDone') : defaultValue
}

</script>

<style>
.collectme-the-base-overlay__intro {
  color: var(--color-text);
  line-height: 1.4em;
}

.collectme-the-objective-setter__card-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr 1fr;
  row-gap: clamp(0.75rem, 2vw, 1rem);
  column-gap: clamp(0.5rem, 2vw, 1rem);
  margin: 1rem 0;
  justify-self: stretch;
  align-self: stretch;
}

</style>
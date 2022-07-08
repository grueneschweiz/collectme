<template>
  <BaseLayoutCard>
    <template #header>
      {{ t('HomeView.ActivityFeed.title') }}
    </template>

    <template #default>
      <ActivityFeedCard
          v-if="activities.length"
          v-for="(activity, idx) in activities"
          :activity="activity"
          :key="activity.id ?? idx"
          class="collectme-activity-feed__card"
      />
      <BaseLoader v-if="activityStore.isLoading"/>
      <BaseButton
          v-if="!activityStore.isLoading && activityStore.next && activities.length && !activityStore.error"
          outline
          muted
          full-width
          size="sm"
          @click="activityStore.fetchMore()"
      >
        {{t('HomeView.ActivityFeed.loadMore')}}
      </BaseButton>
      <BaseAlert
          v-if="activityStore.error && !activityStore.isLoading"
          error
          @close="activityStore.error = null"
      >
        <div class="collectme-activity-feed__error-msg">
          <h5 class="collectme-activity-feed__error-msg__title">{{t('General.Error.unspecificTitle')}}</h5>
          {{t('General.Error.blameTheGoblins')}}
        </div>
        <BaseButton
            outline
            secondary
            size="sm"
            @click="activityStore.fetchMore()"
        >
          {{t('General.Error.tryAgain')}}
        </BaseButton>
      </BaseAlert>
    </template>

    <template #default v-if="!activities.length && !activityStore.isLoading && !activityStore.error">
      {{ t('HomeView.ActivityFeed.noActivity') }}
    </template>

  </BaseLayoutCard>
</template>

<script setup lang="ts">
import BaseAlert from "@/components/base/BaseAlert.vue";
import BaseLayoutCard from "@/components/base/BaseLayoutCard.vue";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import BaseButton from '@/components/base/BaseButton.vue'
import ActivityFeedCard from "@/components/specific/home/ActivityFeed/ActivityFeedCard.vue";
import {useActivityStore} from "@/stores/ActivityStore";
import t from "@/utility/i18n";
import {onBeforeUnmount, onMounted} from "vue";

const activityStore = useActivityStore();
activityStore.fetchFirst();

const activities = activityStore.activities;

let timer: ReturnType<typeof setInterval>;

onMounted(() => {
  timer = setInterval(activityStore.update, 60000);
})

onBeforeUnmount(() => {
  clearInterval(timer)
})
</script>

<style>
/*noinspection CssUnusedSymbol*/
.collectme-base-loader {
  width: 100%;
  text-align: center;
  height: 1rem;
  margin: 1rem 0;
}

.collectme-activity-feed__card {
  margin: 0.75rem 0;
}

.collectme-activity-feed__error-msg {
  color: var(--color-secondary-dark);
  padding-bottom: 1em;
  line-height: 1.4em;
}

.collectme-activity-feed__error-msg__title {
  color: var(--color-secondary-dark);
  margin-top: 0;
}
</style>
<template>
  <BaseContentCard>
    <template #default>
      <div class="collectme-activity-feed-card__card">
        <div class="collectme-activity-feed-card__counter">
          {{ activity.attributes.count }}
        </div>
        <div class="collectme-activity-feed-card__message">{{ message }}</div>
      </div>
    </template>
    <template v-if="timeAgo" #trailer>
      {{ timeAgo }}
    </template>
  </BaseContentCard>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Activity, Group } from "@/models/generated";
import t from "@/utility/i18n";
import moment from "moment";
import "moment/locale/de";
import "moment/locale/fr";
import "moment/locale/it";
import { useGroupStore } from "@/stores/GroupStore";
import BaseContentCard from "@/components/base/BaseContentCard.vue";

const props = defineProps({
  activity: {
    type: Object as PropType<Activity>,
    required: true,
    validator: (activity: Activity) => activity.type === "activity",
  },
});

const activity = props.activity as Activity;

const message = computed<string>(() => {
  const type = activity.attributes.type;
  // convert space separated type to camelCasedKey
  const messageKey = type
    .split(" ")
    .map((str, idx) =>
      idx === 0 ? str : str.charAt(0).toUpperCase() + str.substring(1)
    )
    .reduce((a, b) => a + b);
  const messagePath = `HomeView.ActivityFeed.${messageKey}`;
  const replacements = {
    firstName: group.value?.attributes?.name || "",
    organization: group.value?.attributes?.name || "",
    count: String(activity.attributes.count),
  };
  return t(messagePath, replacements);
});

const group = computed<Group>(() => {
  return useGroupStore().groups.get(
    activity.relationships.group.data.id
  ) as Group;
});

let timer: ReturnType<typeof setInterval>;
const timeAgo = ref<string | null>(null);

function updateTimeAgo() {
  timeAgo.value = moment(activity.attributes.created)
    .locale(collectme.locale)
    .fromNow();
}

updateTimeAgo();

onMounted(() => {
  timer = setInterval(updateTimeAgo, 5000);
});

onBeforeUnmount(() => {
  clearInterval(timer);
});
</script>

<style>
.collectme-activity-feed-card__card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 2.625rem;
}

.collectme-activity-feed-card__counter {
  font-size: 1.25rem;
  font-weight: bold;
  width: 3.5rem;
  text-align: center;
}

.collectme-activity-feed-card__message {
  font-size: clamp(0.75rem, 2.5vw, 0.875rem);
  line-height: 1.4em;
  color: var(--color-text);
}
</style>

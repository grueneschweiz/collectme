<template>
  <div class="collectme-activity-feed-card">
    <div class="collectme-activity-feed-card__card">
      <div class="collectme-activity-feed-card__counter">{{ activity.attributes.count }}</div>
      <div class="collectme-activity-feed-card__message">{{ message }}</div>
    </div>
    <div
        v-if="timeAgo"
        class="collectme-activity-feed-card__timestamp"
    >{{ timeAgo }}
    </div>
  </div>
</template>

<script setup lang="ts">
import type {PropType} from "vue";
import {computed, onBeforeUnmount, onMounted, ref} from "vue";
import type {Activity, Group} from "@/models/generated";
import t from "@/utility/i18n";
import moment from "moment";
import 'moment/locale/de';
import 'moment/locale/fr';
import 'moment/locale/it';
import {useGroupStore} from "@/stores/GroupStore";

const props = defineProps({
  activity: {
    type: Object as PropType<Activity>,
    required: true,
    validator: (activity: Activity) => activity.type === 'activity'
  }
});

const activity = props.activity as Activity;

const message = computed<string>(() => {
  const type = activity.attributes.type;
  // convert space separated type to camelCasedKey
  const messageKey = type.split(' ')
      .map((str, idx) => idx === 0 ? str : str.charAt(0).toUpperCase() + str.substring(1))
      .reduce((a, b) => a + b)
  const messagePath = `HomeView.ActivityFeed.${messageKey}`
  const replacements = {
    firstName: group.value?.attributes?.name || '',
    organization: group.value?.attributes?.name || '',
    count: String(activity.attributes.count),
  }
  return t(messagePath, replacements);
});

const group = computed<Group>( () => {
  return useGroupStore().groups.get(activity.relationships.group.data.id) as Group;
});

let timer: ReturnType<typeof setInterval>;
let timeAgo = ref<string | null>(null);

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
  clearInterval(timer)
})

</script>

<style>
.collectme-activity-feed-card__card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-white);
  border-left: 2px solid var(--color-secondary);
  border-radius: 3px;
  padding: 0.5rem;
  box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.25);
  min-height: 3.625rem;
}

.collectme-activity-feed-card__counter {
  font-size: 1.25rem;
  font-weight: bold;
  width: 3.5rem;
  text-align: center;
}

.collectme-activity-feed-card__message {
  line-height: 1.3125em;
  color: var(--color-text);
}

.collectme-activity-feed-card__timestamp {
  text-align: right;
  font-size: 0.875rem;
  color: var(--color-grey-3);
  padding: 0.25rem 0.25rem 0.75rem;
}
</style>
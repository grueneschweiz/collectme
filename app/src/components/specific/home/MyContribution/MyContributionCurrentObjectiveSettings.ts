import { computed } from "vue";
import { useObjectiveSettings } from "@/components/specific/home/TheObjectiveSetter/ObjectiveSettings";
import type { ObjectiveSettings } from "@/components/specific/home/TheObjectiveSetter/ObjectiveSettings";
import { useGroupStore } from "@/stores/GroupStore";
import { useObjectiveStore } from "@/stores/ObjectiveStore";

const objectiveCount = computed<number>(() => {
  const groupStore = useGroupStore();

  if (!groupStore.myPersonalGroup?.id) {
    return 0;
  }

  return (
    useObjectiveStore().getHighestObjectiveByGroupId(
      groupStore.myPersonalGroup.id
    )?.attributes.objective || 0
  );
});

const myCurrentObjectiveSettings = computed<ObjectiveSettings>(() => {
  return useObjectiveSettings().getLowerOrEqual(objectiveCount.value);
});

export { myCurrentObjectiveSettings };

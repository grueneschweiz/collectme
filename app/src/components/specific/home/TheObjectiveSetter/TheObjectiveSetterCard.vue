<template>
  <BaseContentCard
    :button="true"
    :disabled="disabled || !!selected"
    @click="selectObjective"
  >
    <div class="collectme-the-objective-setter-card">
      <h3 class="collectme-the-objective-setter-card__title">{{ count }}</h3>
      <div class="collectme-the-objective-setter-card__img-wrapper">
        <img
          :src="img"
          :alt="`Goal ${count}`"
          class="collectme-the-objective-setter-card__img"
        />
      </div>
      <div class="collectme-the-objective-setter-card__subline">
        {{
          t("HomeView.TheObjectiveSetter.TheObjectiveSetterCard.subline", {
            count: count.toString(),
          })
        }}
      </div>
      <div
        class="collectme-the-objective-setter-card__ribbon"
        :class="{
          'collectme-the-objective-setter-card__ribbon--done': disabled,
        }"
        v-if="ribbonText"
      >
        {{ ribbonText }}
      </div>
    </div>
  </BaseContentCard>
</template>

<script setup lang="ts">
import BaseContentCard from "@/components/base/BaseContentCard.vue";
import t from "@/utility/i18n";
import { computed, ref } from "vue";
import { useObjectiveStore } from "@/stores/ObjectiveStore";
import type { Objective } from "@/models/generated";
import { useGroupStore } from "@/stores/GroupStore";
import { useActivityStore } from "@/stores/ActivityStore";

const props = defineProps({
  count: {
    type: Number,
    required: true,
  },
  img: {
    type: String,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  ribbon: {
    type: String,
  },
});

const emit = defineEmits(["saved"]);
const objectiveStore = useObjectiveStore();
const groupStore = useGroupStore();

const ribbonText = computed<string | unknown>(() => {
  return selected.value ? selected.value : props.ribbon;
});

const selected = ref<string | false>(false);

async function selectObjective() {
  if (props.disabled || selected.value) {
    return;
  }

  selected.value = t(
    "HomeView.TheObjectiveSetter.TheObjectiveSetterCard.saving"
  );

  try {
    await saveObjective();
    useActivityStore().update().then();
    selected.value = t("HomeView.TheObjectiveSetter.ribbonSelected");
    emit("saved", props.count);
  } catch {
    selected.value = false;
  }
}

function saveObjective() {
  return objectiveStore.create({
    id: null,
    type: "objective",
    attributes: {
      objective: props.count,
      source: "App",
    },
    relationships: {
      group: {
        data: {
          id: groupStore.myPersonalGroup?.id,
          type: "group",
        },
      },
    },
  } as Objective);
}
</script>

<style>
.collectme-the-objective-setter-card {
  text-align: center;
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.collectme-the-objective-setter-card__title {
  margin: 0 0 0.25rem;
  color: var(--color-text);
  flex: none;
}

.collectme-the-objective-setter-card__img-wrapper {
  max-height: calc(30vh - 6rem);
}

.collectme-the-objective-setter-card__img {
  margin: 0.5rem;
  max-width: calc(100% - 1rem);
  max-height: calc(100% - 1rem);
  flex: none;
}

.collectme-the-objective-setter-card__subline {
  color: var(--color-grey-3);
  font-size: clamp(0.75rem, 2.5vw, 0.875rem);
  line-height: 1.2em;
  flex: none;
}

.collectme-the-objective-setter-card__ribbon {
  position: absolute;
  background: var(--color-secondary);
  color: var(--color-white);
  text-transform: uppercase;
  font-weight: bold;
  transform: translateX(50%) translateY(-50%) rotate(45deg);
  box-shadow: 1px 1px 3px 1px rgb(0 0 0 / 15%);
  letter-spacing: 0.0875rem;
  font-size: 0.5625rem;
  width: 6rem;
  padding: 0.125rem;
  top: 1rem;
  right: 1rem;
}

@media screen and (min-width: 480px) {
  .collectme-the-objective-setter-card__ribbon {
    letter-spacing: 0.0875rem;
    font-size: 0.75rem;
    width: 8rem;
    padding: 0.25rem;
    top: 1.5rem;
    right: 1.5rem;
  }
}

/*noinspection CssUnusedSymbol*/
.collectme-base-content-card__card {
  overflow: hidden;
  height: 100%;
  width: 100%;
}

.collectme-the-objective-setter-card__ribbon--done {
  background: var(--color-primary);
}
</style>

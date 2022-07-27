<template>
  <BaseLayoutCard>
    <template #header>
      {{ t("HomeView.OverviewStats.title") }}
    </template>

    <template #default>
      <div class="collectme-overview-stats__charts-wrapper">
        <BaseDoughnutChart
          class="collectme-overview-stats__chart collectme-overview-stats__chart--pledged"
          :percent="pledgedPercent"
        />
        <BaseDoughnutChart
          class="collectme-overview-stats__chart collectme-overview-stats__chart--registered"
          :percent="registeredPercent"
        />

        <div
          class="collectme-overview-stats__desc-wrapper collectme-overview-stats__desc-wrapper--pledged"
        >
          <span
            class="collectme-overview-stats__desc-num collectme-overview-stats__desc-num--pledged"
          >
            {{ Math.round(pledgedPercent) }}%
          </span>
          <span
            class="collectme-overview-stats__desc collectme-overview-stats__desc--pledged"
          >
            {{ t("HomeView.OverviewStats.pledgedDesc") }}
          </span>
        </div>

        <div
          class="collectme-overview-stats__desc-wrapper collectme-overview-stats__desc-wrapper--entered"
        >
          <span
            class="collectme-overview-stats__desc-num collectme-overview-stats__desc-num--entered"
          >
            {{ Math.round(registeredPercent) }}%
          </span>
          <span
            class="collectme-overview-stats__desc collectme-overview-stats__desc--entered"
          >
            {{ t("HomeView.OverviewStats.enteredDesc") }}
          </span>
        </div>
      </div>

      <div class="collectme-overview-stats__share">
        <p class="collectme-overview-stats__share-paragraph">
          Danke für deinen Beitrag 💚
        </p>
        <p class="collectme-overview-stats__share-paragraph">
          Das Ziel erreichen wir nur gemeinsam. Lade deine Freunde ein und schon
          sind wir dem Ziel einen Schritt näher.
        </p>
        <BaseShare
          :url="shareUrl"
          :shareMsg="t('HomeView.OverviewStats.shareMsg')"
          :emailSubject="t('HomeView.OverviewStats.shareEmailSubject')"
        />
      </div>
    </template>
  </BaseLayoutCard>
</template>

<script setup lang="ts">
import BaseLayoutCard from "@/components/base/BaseLayoutCard.vue";
import BaseDoughnutChart from "@/components/base/BaseDoughnutChart.vue";
import t from "@/utility/i18n";
import { useStatStore } from "@/stores/StatStore";
import { computed, onBeforeUnmount, onMounted } from "vue";
import BaseShare from "@/components/base/BaseShare/BaseShare.vue";

const shareUrl = collectme.appUrl;

const statStore = useStatStore();
statStore.fetch();

const pledgedPercent = computed(
  () => (statStore.stat?.attributes.pledged ?? 0) * 100
);
const registeredPercent = computed(
  () => (statStore.stat?.attributes.registered ?? 0) * 100
);

let timer: ReturnType<typeof setInterval>;

onMounted(() => {
  timer = setInterval(statStore.fetch, 60000);
});

onBeforeUnmount(() => {
  clearInterval(timer);
});
</script>

<style>
.collectme-overview-stats__charts-wrapper {
  position: relative;
}

.collectme-overview-stats__chart {
  stroke-width: 10px;
}

.collectme-overview-stats__chart::after {
  content: none;
}

.collectme-overview-stats__chart--registered {
  position: absolute;
  top: 0;
  padding: 22px;
}

/*noinspection CssUnusedSymbol,CssMissingComma*/
.collectme-overview-stats__chart--pledged
  .collectme-base-doughnut-chart__svg-foreground {
  stroke: var(--color-primary);
}

/*noinspection CssUnusedSymbol,CssMissingComma*/
.collectme-overview-stats__chart--registered
  .collectme-base-doughnut-chart__svg-foreground {
  stroke: var(--color-secondary);
}

.collectme-overview-stats__desc-wrapper {
  position: absolute;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.collectme-overview-stats__desc-wrapper--pledged {
  top: 33%;
}

.collectme-overview-stats__desc-wrapper--entered {
  top: 66%;
}

.collectme-overview-stats__desc-num {
  display: block;
  font-size: clamp(2rem, 8vw, 3rem);
  font-weight: bold;
}

.collectme-overview-stats__desc-num--pledged {
  color: var(--color-primary);
}

.collectme-overview-stats__desc-num--entered {
  color: var(--color-secondary);
}

.collectme-overview-stats__desc {
  font-size: clamp(0.75rem, 2.5vw, 0.875rem);
  line-height: 1.15em;
  color: var(--color-grey-3);
}

.collectme-overview-stats__share {
  margin-top: clamp(1rem, 6vw, 3rem);
}

.collectme-overview-stats__share-paragraph {
  font-size: clamp(0.75rem, 2.5vw, 0.875rem);
  line-height: 1.4em;
  color: var(--color-grey-3);
  margin: 0.5rem 0;
  text-align: center;
}

@media all and (min-width: 480px) {
  .collectme-overview-stats__charts-wrapper {
    display: grid;
    grid-template-columns:
      [chart] minmax(150px, 250px)
      [desc] clamp(200px, 40vw, 250px);
    grid-template-rows: [row-start] 1fr 1fr [row-end];
    gap: clamp(1rem, 0.4vw, 2rem);
    margin: 0 auto;
    width: fit-content;
  }

  .collectme-overview-stats__chart {
    grid-column: chart;
    grid-row-start: row-start;
    grid-row-end: row-end;
    align-self: center;
    position: static;
  }

  .collectme-overview-stats__desc-wrapper {
    position: static;
    transform: none;
    grid-column: desc;

    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 250px;
    text-align: center;
    /*gap: clamp(0.5rem, 0.8vw, 1rem);*/
  }

  .collectme-overview-stats__desc-wrapper--pledged {
    align-self: end;
  }

  .collectme-overview-stats__desc-wrapper--entered {
    align-self: start;
  }

  .collectme-overview-stats__desc-num {
    font-size: clamp(1rem, calc(1rem + ((100vw - 480px) * 0.125)), 3rem);
  }
}
</style>

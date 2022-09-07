<template>
  <BaseLayoutCard class="collectme-my-contribution">
    <template #default>
      <router-link
        to="/home/set-goal"
        class="collectme-my-contribution__header-img-wrapper"
      >
        <img
          :src="myCurrentObjectiveSettings.img"
          alt="goal image"
          class="collectme-my-contribution__header-img"
          :class="`collectme-my-contribution__header-img--${myCurrentObjectiveSettings.objective}`"
        />
      </router-link>

      <h3 class="collectme-my-contribution__title">
        {{ t("HomeView.MyContribution.title") }}
      </h3>

      <div class="collectme-my-contribution__body" v-if="userStore.me">
        <MyContributionSteps />
      </div>

      <BaseLoader v-else-if="userStore.isLoading" />

      <template v-else>
        <p class="collectme-my-contribution__sign-in-msg">
          {{ t("HomeView.MyContribution.singInMsg") }}
        </p>
        <div class="collectme-my-contribution__login-button-wrapper">
          <BaseButton secondary size="md" @click="$router.push('/login')">
            {{ t("HomeView.MyContribution.signInBtn") }}
          </BaseButton>
          <span>{{ t("HomeView.MyContribution.noPasswordRequired") }}</span>
        </div>
      </template>
    </template>
  </BaseLayoutCard>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import BaseLayoutCard from "@/components/base/BaseLayoutCard.vue";
import t from "@/utility/i18n";
import { useUserStore } from "@/stores/UserStore";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import BaseButton from "@/components/base/BaseButton.vue";
import MyContributionSteps from "@/components/specific/home/MyContribution/MyContributionSteps.vue";
import { useGroupStore } from "@/stores/GroupStore";
import { myCurrentObjectiveSettings } from "@/components/specific/home/MyContribution/MyContributionCurrentObjectiveSettings";
import router from "@/router/index";
import isLoginPage from "@/router/isLoginPage";

const userStore = useUserStore();
const groupStore = useGroupStore();

onMounted(() => {
  userStore
    .fetch()
    .then(groupStore.fetch)
    .catch(() => {
      if (!isLoginPage.value) {
        router.push("/login");
      }
    });
});
</script>

<style>
.collectme-my-contribution {
  position: relative;
  margin-top: calc(75px + 2rem);
  padding-top: 75px;

  /* min-height prevents overview component from appearing
  (and thus triggering it's animations) while loading this
  component */
  min-height: 570px;
}

.collectme-my-contribution__header-img-wrapper {
  width: 150px;
  height: 150px;
  border-radius: 75px;
  border: solid 5px white;
  background: white;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translate(-50%, -50%);
  overflow: hidden;
  box-shadow: 0 0 9px 2px var(--color-grey-1);
}

.collectme-my-contribution__header-img-wrapper:hover,
.collectme-my-contribution__header-img-wrapper:focus {
  background: white;
  box-shadow: 0 0 9px 2px var(--color-grey-1);
  transform: translate(-50%, -50%) scale(1.1);
}

.collectme-my-contribution__header-img-wrapper::after {
  content: "";
  display: block;
  width: 100%;
  height: 100%;
  border: 2px solid var(--color-grey-2);
  border-radius: 75px;
}

.collectme-my-contribution__header-img {
  width: 100%;
  height: 100%;
  position: absolute;
}

.collectme-my-contribution__title {
  text-align: center;
}

.collectme-my-contribution__sign-in-msg {
  margin: 1em 0;
}

.collectme-my-contribution__login-button-wrapper {
  display: flex;
  gap: 1rem;
  justify-content: flex-start;
  align-items: center;
  margin: 1rem 0 0.5rem;
  color: var(--color-grey-4);
  font-size: 0.75rem;
}
</style>

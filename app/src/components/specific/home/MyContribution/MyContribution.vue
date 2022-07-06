<template>
  <BaseCard>
    <template #header>
      {{ t('HomeView.MyContribution.title') }}
    </template>

    <template
        #default
        v-if="userStore.me"
    >
      <div class="collectme-my-contribution__body">
        <MyContributionSteps/>
      </div>
    </template>

    <template
        #default
        v-else-if="userStore.isLoading"
    >
      <BaseLoader/>
    </template>

    <template
        #default
        v-else
    >
      <p class="collectme-my-contribution__sign-in-msg">
        {{ t('HomeView.MyContribution.singInMsg') }}
      </p>
      <div class="collectme-my-contribution__login-button-wrapper">
        <BaseButton
            secondary
            size="md"
            @click="$router.push('/login')"
        >
          {{ t('HomeView.MyContribution.signInBtn') }}
        </BaseButton>
        <span>{{t('HomeView.MyContribution.noPasswordRequired')}}</span>
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
import BaseCard from "@/components/base/BaseCard.vue";
import t from "@/utility/i18n";
import {useUserStore} from "@/stores/UserStore";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import BaseButton from "@/components/base/BaseButton.vue"
import MyContributionSteps from '@/components/specific/home/MyContribution/MyContributionSteps.vue'

const userStore = useUserStore();
userStore.fetch();

</script>

<style>
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
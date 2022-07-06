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
        <BaseStepElement status="completed" next="completed">
          <template #title>
            Erfolgreich Angemeldet
          </template>
          <template #default>
            hallo Cyrill
          </template>
        </BaseStepElement>
        <BaseStepElement status="completed" prev="completed" next="active">
          <template #title>
            Sammelziel gesetzt
          </template>
          <template #default>
            hallo Cyrill
          </template>
        </BaseStepElement>
        <BaseStepElement status="active" prev="completed" next="pending">
          <template #title>
            Unterschriften eingetragen
          </template>
          <template #default>
            hallo Cyrill
          </template>
        </BaseStepElement>
        <BaseStepElement status="pending" prev="active">
          <template #title>
            Sammelziel erreicht
          </template>
          <template #default>
            hallo Cyrill
          </template>
        </BaseStepElement>

        <div>
          <p>{{ t('HomeView.MyContribution.body') }}</p>
        </div>
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
        {{ t('HomeView.MyContribution.body') }}
      </p>
      <div class="collectme-my-contribution__login-button-wrapper">
        <BaseButton
            secondary
            size="md"
            @click="$router.push('/login')"
        >
          {{ t('HomeView.MyContribution.signIn') }}
        </BaseButton>
        <span>{{t('HomeView.MyContribution.noPasswordRequired')}}</span>
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
import BaseCard from "@/components/base/BaseCard.vue";
import BaseStepElement from "@/components/base/BaseStepElement/BaseStepElement.vue";
import t from "@/utility/i18n";
import {useUserStore} from "@/stores/UserStore";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import BaseButton from "@/components/base/BaseButton.vue"

const userStore = useUserStore();
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
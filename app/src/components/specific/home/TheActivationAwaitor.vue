<template>
  <TheBaseOverlay :closeable="false">
    <template #header>
      {{ t("HomeView.TheActivationAwaitor.title") }}
    </template>
    <template #default>
      <p v-html="t('HomeView.TheActivationAwaitor.descLogin')" />
      <p>{{ t("HomeView.TheActivationAwaitor.descSlowMail") }}</p>

      <div class="collectme-the-activation-awaitor__submit-wrapper">
        <BaseButton
          secondary
          :disabled="loginStore.isLoading"
          @click="loginStore.resendLoginData"
          class="collectme-the-activation-awaitor__submit-button"
        >
          <template v-if="!loginStore.isLoading">
            {{ t("HomeView.TheActivationAwaitor.retryBtn") }}
          </template>
          <BaseLoader
            v-else
            scheme="inverted"
            class="collectme-the-activation-awaitor__submit-button--loading"
          />
        </BaseButton>
        <p class="collectme-the-activation-awaitor__submit-byline">
          {{
            t("HomeView.TheActivationAwaitor.retryByline", {
              email:
                loginStore.login?.attributes.email ??
                t("HomeView.TheActivationAwaitor.invalidEmail"),
            })
          }}
        </p>
      </div>
    </template>
  </TheBaseOverlay>
</template>

<script setup lang="ts">
import TheBaseOverlay from "@/components/base/TheBaseOverlay.vue";
import BaseButton from "@/components/base/BaseButton.vue";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import t from "@/utility/i18n";
import { useLoginStore } from "@/stores/LoginStore";
import { onBeforeUnmount, onMounted } from "vue";
import { useSessionStore } from "@/stores/SessionStore";
import router from "@/router";
import {useUserStore} from "@/stores/UserStore";

const emit = defineEmits(["login"]);

const loginStore = useLoginStore();

let timer: ReturnType<typeof setInterval>;

function attemptLogin() {
  useSessionStore()
    .fetch()
    .then(() => emit("login"))
    .then(() => router.push("/"));
}

onMounted(() => {
  if (useUserStore().me?.id) {
    router.push("/");
  }

  timer = setInterval(attemptLogin, 4000);
});

onBeforeUnmount(() => {
  clearInterval(timer);
});
</script>

<style>
.collectme-the-activation-awaitor__submit-wrapper {
  margin: 20px 0;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 1em;
}

.collectme-the-activation-awaitor__submit-button {
  flex: none;
}

.collectme-the-activation-awaitor__submit-button--loading {
  height: 1rem;
  margin: 0.25rem;
}

.collectme-the-activation-awaitor__submit-byline {
  margin: 0;
  color: var(--color-grey-3);
  font-size: 0.75rem;
}
</style>

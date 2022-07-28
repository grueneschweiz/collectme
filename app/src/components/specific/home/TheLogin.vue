<template>
  <TheBaseOverlay :closeable="false">
    <template #header>
      {{ t("HomeView.TheLogin.title") }}
    </template>

    <template #default>
      <p class="collectme-the-login__login-msg">
        {{ t("HomeView.TheLogin.loginMsg") }}
      </p>
      <BaseInput
        id="email"
        :label="t('HomeView.TheLogin.emailLabel')"
        type="email"
        autocomplete="email"
        :helptext="t('HomeView.TheLogin.emailHelpText')"
        :validation-status="emailValid"
        :validation-message="t('HomeView.TheLogin.emailInvalid')"
        v-model.trim="email"
        required
      />

      <TransitionAppearFade>
        <div v-if="showNameInputs" class="collectme-the-login__name-inputs">
          <BaseInput
            id="firstName"
            :label="t('HomeView.TheLogin.firstNameLabel')"
            type="text"
            autocomplete="given-name"
            :validation-status="firstNameValid"
            :validation-message="t('HomeView.TheLogin.firstNameInvalid')"
            v-model.trim="firstName"
            required
          />

          <BaseInput
            id="lastName"
            :label="t('HomeView.TheLogin.lastNameLabel')"
            type="text"
            autocomplete="family-name"
            :validation-status="lastNameValid"
            :validation-message="t('HomeView.TheLogin.lastNameInvalid')"
            v-model.trim="lastName"
            required
          />
        </div>
      </TransitionAppearFade>

      <div class="collectme-the-login__submit-wrapper">
        <BaseButton
          secondary
          :muted="!isFormValid"
          :disabled="!isFormValid || loginStore.isLoading"
          @click="submitLoginData"
          class="collectme-the-login__submit-button"
        >
          <template v-if="!loginStore.isLoading">
            {{ t("HomeView.TheLogin.signIn") }}
          </template>
          <BaseLoader
            v-else
            scheme="inverted"
            class="collectme-the-login__submit-button--loading"
          />
        </BaseButton>
        <p class="collectme-the-login__submit-byline">
          {{ t("HomeView.TheLogin.submitByline") }}
        </p>
      </div>
    </template>
  </TheBaseOverlay>
</template>

<script setup lang="ts">
import TheBaseOverlay from "@/components/base/TheBaseOverlay.vue";
import t from "@/utility/i18n";
import BaseInput from "@/components/base/BaseInput/BaseInput.vue";
import { computed, onMounted, ref } from "vue";
import isEmail from "validator/es/lib/isEmail";
import isLength from "validator/es/lib/isLength";
import type { ValidationStatus } from "@/components/base/BaseInput/BaseInput";
import TransitionAppearFade from "@/components/transition/TransitionAppearFade.vue";
import BaseButton from "@/components/base/BaseButton.vue";
import { useSnackbarStore } from "@/stores/SnackbarStore";
import type { Snackbar } from "@/stores/SnackbarStore";
import router from "@/router";
import BaseLoader from "@/components/base/BaseLoader/BaseLoader.vue";
import type { Login } from "@/models/generated";
import { useLoginStore } from "@/stores/LoginStore";
import { useUserStore } from "@/stores/UserStore";

const loginStore = useLoginStore();

const email = ref("");
const firstName = ref("");
const lastName = ref("");

const emailValid = computed<ValidationStatus>(() => {
  if (!email.value) {
    return "unvalidated";
  }

  return isEmail(email.value) ? "valid" : "invalid";
});

const firstNameValid = computed<ValidationStatus>(() => {
  return minLen(firstName.value, 2);
});

const lastNameValid = computed<ValidationStatus>(() => {
  return minLen(lastName.value, 2);
});

const minLen: (name: string, len: number) => ValidationStatus = (name, len) => {
  if (!name) {
    return "unvalidated";
  }

  return isLength(name, { min: len }) ? "valid" : "invalid";
};

const isFormValid = computed(() => {
  return (
    emailValid.value === "valid" &&
    firstNameValid.value === "valid" &&
    lastNameValid.value === "valid"
  );
});

let nameInputsShown = false;
const showNameInputs = computed(() => {
  if (email.value && emailValid.value === "valid") {
    nameInputsShown = true;
  }

  return nameInputsShown;
});

async function submitLoginData() {
  const data = {
    attributes: {
      email: email.value,
      firstName: firstName.value,
      lastName: lastName.value,
      appUrl: collectme.appUrl,
      urlAuth: collectme.appUrlAuthentication,
    },
    relationships: {
      cause: {
        data: { id: collectme.cause },
      },
    },
  } as Login;

  try {
    useSnackbarStore().hide({ id: "login-validation-error" } as Snackbar);
    await loginStore.sendLoginData(data);
    await router.push("await-activation");
  } catch (error) {
    if (loginStore.invalidFields.length) {
      const invalidFields = loginStore.invalidFields
        .map<string>((field: string) => field.replace(/([A-Z])/g, " $1"))
        .map<string>(
          (field: string) => field.charAt(0).toUpperCase() + field.substring(1)
        );

      useSnackbarStore().show({
        id: "login-validation-error",
        type: error,
        shortDesc: t("General.Error.invalidData"),
        longDesc: t("General.Error.invalidFields", {
          fields: invalidFields.join(", "),
        }),
        vanishAfter: 10000,
      } as Snackbar);
    }
  }
}

onMounted(() => {
  if (useUserStore().me?.id) {
    router.push("/");
  }
});
</script>

<style>
.collectme-the-login__login-msg {
  margin: 0;
}

.collectme-the-login__submit-wrapper {
  margin: 20px 0;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 1em;
}

.collectme-the-login__submit-button {
  flex: none;
}

.collectme-the-login__submit-button--loading {
  height: 1rem;
  margin: 0.25rem;
}

.collectme-the-login__submit-byline {
  margin: 0;
  color: var(--color-grey-3);
  font-size: 0.75rem;
}
</style>

<template>
  <TheBaseOverlay
      :closeable="false"
  >
    <template #header>
      {{ t('HomeView.TheLogin.title') }}
    </template>

    <template #default>
      <p class="collectme-the-login__login-msg">{{ t('HomeView.TheLogin.loginMsg') }}</p>
      <BaseInput
          id="email"
          :label="t('HomeView.TheLogin.emailLabel')"
          type="email"
          autocomplete="email"
          :helptext="t('HomeView.TheLogin.emailHelpText')"
          :validation-status="emailValid"
          :validation-message="t('HomeView.TheLogin.emailInvalid')"
          v-model="email"
          required
      />

      <TransitionAppearFade>
        <div
            v-if="showNameInputs"
            class="collectme-the-login__name-inputs"
        >
          <BaseInput
              id="firstName"
              :label="t('HomeView.TheLogin.firstNameLabel')"
              type="text"
              autocomplete="given-name"
              :validation-status="firstNameValid"
              :validation-message="t('HomeView.TheLogin.firstNameInvalid')"
              v-model="firstName"
              required
          />

          <BaseInput
              id="lastName"
              :label="t('HomeView.TheLogin.lastNameLabel')"
              type="text"
              autocomplete="family-name"
              :validation-status="lastNameValid"
              :validation-message="t('HomeView.TheLogin.lastNameInvalid')"
              v-model="lastName"
              required
          />
        </div>
      </TransitionAppearFade>

      <div class="collectme-the-login__submit-wrapper">
        <BaseButton
            secondary
            :muted="!isFormValid"
            :disabled="!isFormValid"
            class="collectme-the-login__submit-button"
        >
          {{ t('HomeView.TheLogin.signIn') }}
        </BaseButton>
        <p class="collectme-the-login__submit-byline">{{t('HomeView.TheLogin.submitByline')}}</p>
      </div>
    </template>

  </TheBaseOverlay>
</template>

<script setup lang="ts">
import TheBaseOverlay from '@/components/base/TheBaseOverlay.vue'
import t from '@/utility/i18n'
import BaseInput from '@/components/base/BaseInput/BaseInput.vue'
import {computed, ref} from "vue";
import isEmail from 'validator/es/lib/isEmail';
import isLength from 'validator/es/lib/isLength';
import type {ValidationStatus} from "@/components/base/BaseInput/BaseInput";
import TransitionAppearFade from '@/components/transition/TransitionAppearFade.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const email = ref('');
const firstName = ref('');
const lastName = ref('');

const emailValid = computed<ValidationStatus>(() => {
  if (!email.value) {
    return 'unvalidated'
  }

  return isEmail(email.value) ? 'valid' : 'invalid'
})

const firstNameValid = computed<ValidationStatus>(() => {
  return minLen(firstName.value, 2)
})

const lastNameValid = computed<ValidationStatus>(() => {
  return minLen(lastName.value, 2)
})

const minLen: (name: string, len: number) => (ValidationStatus) = (name, len) => {
  if (!name) {
    return 'unvalidated'
  }

  return isLength(name, {min: len}) ? 'valid' : 'invalid'
}

const isFormValid = computed(() => {
  return emailValid.value === 'valid'
    && firstNameValid.value === 'valid'
    && lastNameValid.value === 'valid'
})

let nameInputsShown = false;
const showNameInputs = computed(() => {
  if (email.value && emailValid.value === 'valid') {
    nameInputsShown = true
  }

  return nameInputsShown
})
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

.collectme-the-login__submit-byline {
  margin: 0;
  color: var(--color-grey-3);
  font-size: 0.75rem;
}
</style>
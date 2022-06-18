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
              :validation-status="nameValid(firstName)"
              :validation-message="t('HomeView.TheLogin.firstNameInvalid')"
              v-model="firstName"
          />

          <BaseInput
              id="lastName"
              :label="t('HomeView.TheLogin.lastNameLabel')"
              type="text"
              autocomplete="family-name"
              :validation-status="nameValid(lastName)"
              :validation-message="t('HomeView.TheLogin.lastNameInvalid')"
              v-model="lastName"
          />
        </div>
      </TransitionAppearFade>
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
import type {ValidationStatus} from "@/components/base/BaseInput/BaseInputTypes";
import TransitionAppearFade from '@/components/transition/TransitionAppearFade.vue'

const email = ref('');
const firstName = ref('');
const lastName = ref('');

const emailValid = computed<ValidationStatus>(() => {
  if (!email.value) {
    return 'unvalidated'
  }

  return isEmail(email.value) ? 'valid' : 'invalid'
})

const nameValid: (name: string) => (ValidationStatus) = name => {
  if (!name) {
    return 'unvalidated'
  }

  return isLength(name, {min: 2}) ? 'valid' : 'invalid'
}

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
</style>
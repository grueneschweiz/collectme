<template>
  <TheBaseOverlay
      :closeable="true"
      @close="$router.back()"
  >
    <template #header>
      {{ t('HomeView.TheSignatureAdder.title') }}
    </template>

    <template #default>

      <div class="collectme-the-signature-adder__intro"
           v-html="t('HomeView.TheSignatureAdder.intro')"
      />

      <BaseInput
          id="collectme-the-signature-adder-input"
          :label="t('HomeView.TheSignatureAdder.input')"
          :required="true"
          type="number"
          :helptext="t('HomeView.TheSignatureAdder.helpText')"
          :validation-message="t('HomeView.TheSignatureAdder.invalid')"
          :validation-status="validationStatus"
          v-model="count"
      />

      <div class="collectme-the-signature-adder__submit-wrapper">
        <BaseButton
            class="collectme-the-signature-adder__submit"
            @click="save"
            :disabled="saving || !count"
            :muted="saving || !count "
            :secondary="!saving"
        >
          {{ saving ? t('HomeView.TheSignatureAdder.saving') : t('HomeView.TheSignatureAdder.submit') }}
        </BaseButton>
        <BaseLoader
            scheme="secondary"
            v-if="saving"
        />
      </div>

    </template>

  </TheBaseOverlay>
</template>

<script setup lang="ts">
import TheBaseOverlay from '@/components/base/TheBaseOverlay.vue'
import t from '@/utility/i18n';
import BaseInput from '@/components/base/BaseInput/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseLoader from '@/components/base/BaseLoader/BaseLoader.vue'
import type {ValidationStatus} from "@/components/base/BaseInput/BaseInput";
import {ref, watch} from "vue";
import {useSignatureStore} from "@/stores/SignatureStore";
import type {Signature} from "@/models/generated";
import {useUserStore} from "@/stores/UserStore";
import {useGroupStore} from "@/stores/GroupStore";
import {useActivityStore} from "@/stores/ActivityStore";
import router from "@/router";

const count = ref<undefined | number>();
const validationStatus = ref<ValidationStatus>('unvalidated');
const saving = ref<boolean>(false)

async function save() {
  saving.value = true

  const myGroupId = useGroupStore().myPersonalGroup?.id

  if (!myGroupId) {
    // todo: show error
    return;
  }

  const number = parseInt(count.value?.toString() ?? '0')

  if (0 === number) {
    return;
  }

  await useSignatureStore().create(<Signature>{
    id: null,
    type: 'signature',
    attributes: {
      count: number,
      created: null,
      updated: null,
    },
    relationships: {
      user: {
        data: {
          id: useUserStore().me?.id
        }
      },
      group: {
        data: {
          id: myGroupId
        }
      }
    }
  });

  // update dependant stores
  useGroupStore().groups.get(myGroupId)!.attributes.signatures += number
  await useActivityStore().update()
  saving.value = false

  router.back();
}

watch(count, newValue => {
  const val = newValue?.toString()
  const isInt = !!val && parseInt(val).toString() === val

  if (isInt && Number.isSafeInteger(parseInt(<string>val))) {
    validationStatus.value = 'valid'
  } else {
    validationStatus.value = 'invalid'
  }
})

</script>

<style>
.collectme-the-signature-adder__intro {
  color: var(--color-text);
  line-height: 1.4em;
}

.collectme-the-signature-adder__submit-wrapper {
  display: flex;
  align-items: center;
}

.collectme-the-signature-adder__submit {
  margin: 1rem 0;
}
</style>
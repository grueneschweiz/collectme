<template>
  <BaseCard>
    <template #header>
      {{t('HomeView.MyContribution.title')}}
    </template>

    <template #default>
      <div class="collectme-my-contribution__body">
        <div>donut</div>
        <div>
          <p>{{t('HomeView.MyContribution.body')}}</p>
          <button>asdf</button>
        </div>
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
import BaseCard from "@/components/layout/BaseCard.vue";
import t from "@/utility/i18n";
</script>

<style>
  .collectme-my-contribution__body {
    display: flex;
    align-items: center;
  }
</style>
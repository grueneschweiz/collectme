import { defineStore } from "pinia";
import api from "@/utility/api";
import type { AxiosResponse } from "axios";
import type { Stat } from "@/models/generated";

const endpointUrl = `causes/${collectme?.cause}/stats`;

interface StatResponseSuccess extends AxiosResponse {
  data: {
    data: Stat;
  };
}

interface StatStoreState {
  stat: Stat | null;
  isLoading: boolean;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  error: any | null;
}

export const useStatStore = defineStore("StatStore", {
  state: (): StatStoreState => {
    return {
      stat: null,
      isLoading: false,
      error: null,
    };
  },

  actions: {
    async fetch() {
      this.isLoading = true;

      try {
        await api(false)
          .get(endpointUrl)
          .then((resp: StatResponseSuccess) => (this.stat = resp.data.data));
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
      } catch (error: any) {
        this.error = error;
      } finally {
        this.isLoading = false;
      }
    },
  },
});

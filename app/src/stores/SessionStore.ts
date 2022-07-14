import type { AxiosError, AxiosResponse } from "axios";
import axios from "axios";
import type { Session } from "@/models/generated";
import { defineStore } from "pinia";
import api from "@/utility/api";
import router from "@/router";
import type { Snackbar } from "@/stores/SnackbarStore";
import { useSnackbarStore } from "@/stores/SnackbarStore";
import t from "@/utility/i18n";

const endpointUrl = "sessions/current";

export interface SessionResponseSuccess extends AxiosResponse {
  data: {
    data: Session;
  };
}

interface SessionStoreState {
  session: Session | null;
  isLoading: boolean;
  error: AxiosError | null;
  waitingForActivation: boolean;
}

export const useSessionStore = defineStore("SessionStore", {
  state: (): SessionStoreState => {
    return {
      session: null,
      isLoading: false,
      error: null,
      waitingForActivation: false,
    };
  },

  actions: {
    setSession(session: Session) {
      this.session = session;
    },

    async fetch() {
      this.isLoading = true;

      try {
        await api(false)
          .get(endpointUrl)
          .then(
            (resp: SessionResponseSuccess) => (this.session = resp.data.data)
          );
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
      } catch (error: any) {
        if (error && axios.isAxiosError(error)) {
          this.waitingForActivation = error.response?.status === 401;
        }

        if (
          error &&
          axios.isAxiosError(error) &&
          error.response?.status === 404
        ) {
          await router.push("/login");
          return Promise.reject(error);
        }

        if (!this.waitingForActivation) {
          useSnackbarStore().show({
            id: error.toString(),
            type: "error",
            shortDesc: t("General.Error.unspecificTitle"),
            longDesc: t("General.Error.blameTheGoblins"),
            vanishAfter: 10000,
          } as Snackbar);
        }

        return Promise.reject(error);
      } finally {
        this.isLoading = false;
      }

      return Promise.resolve();
    },
  },
});

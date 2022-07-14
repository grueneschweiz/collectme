import { defineStore } from "pinia";
import type { Login } from "@/models/generated";
import type { AxiosError } from "axios";
import axios from "axios";
import type { ErrorResponse, JsonApiError } from "@/utility/api";
import useApi from "@/utility/api";
import router from "@/router";
import type { SessionResponseSuccess } from "@/stores/SessionStore";
import { useSessionStore } from "@/stores/SessionStore";

const endpointUrl = "auth";

interface LoginStoreState {
  login: Login | null;
  isLoading: boolean;
  error: AxiosError | null;
  invalidFields: string[];
}

export const useLoginStore = defineStore("LoginStore", {
  state: (): LoginStoreState => {
    return {
      login: null,
      isLoading: false,
      error: null,
      invalidFields: [],
    };
  },

  actions: {
    async sendLoginData(data: Login) {
      this.isLoading = true;
      this.invalidFields = [];
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      this.login = data;

      try {
        const resp = await useApi(true).post<
          { data: Login },
          SessionResponseSuccess
        >(endpointUrl, { data: data });
        useSessionStore().setSession(resp.data.data);
      } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
          const errorResponse = (error as AxiosError).response as ErrorResponse;
          this.invalidFields = errorResponse.data.errors
            .map<string | false>(
              (error: JsonApiError) => error.source?.pointer ?? false
            )
            .filter((pointer) => !!pointer)
            .map<string>((pointer) =>
              (pointer as string).replace(/^\/data\/attributes\//, "")
            );
        }
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    async resendLoginData() {
      if (!this.login) {
        await router.push("/login");
        return;
      }

      this.isLoading = true;
      try {
        await useApi(true).post<{ data: Login }, SessionResponseSuccess>(
          endpointUrl,
          { data: this.login }
        );
      } finally {
        this.isLoading = false;
      }
    },
  },
});

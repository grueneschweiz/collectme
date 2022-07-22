import { defineStore } from "pinia";
import type { Signature } from "@/models/generated";
import api from "@/utility/api";
import type { AxiosResponse } from "axios";

const endpointUrl = "signatures";

interface SignatureResponseSuccess extends AxiosResponse {
  data: {
    data: Signature;
  };
}

interface SignatureStoreState {
  signatures: Map<string, Signature>;
  isLoading: boolean;
}

export const useSignatureStore = defineStore("SignatureStore", {
  state: (): SignatureStoreState => {
    return {
      signatures: new Map(),
      isLoading: false,
    };
  },

  actions: {
    async create(signature: Signature) {
      this.isLoading = true;

      try {
        const resp = await api(true).post<
          { data: Signature },
          SignatureResponseSuccess
        >(endpointUrl, { data: signature });
        this.addSignature(resp.data.data);
      } finally {
        this.isLoading = false;
      }
    },

    addSignature(signature: Signature) {
      if (signature.id != null) {
        this.signatures.set(signature.id, signature);
      }
    },

    async delete(signature: Signature) {
      this.isLoading = true;

      try {
        await api(true).delete(`${endpointUrl}/${signature.id}`);
        this.removeSignature(signature);
      } finally {
        this.isLoading = false;
      }
    },

    removeSignature(signature: Signature) {
      if (signature.id != null) {
        this.signatures.delete(signature.id);
      }
    },
  },
});

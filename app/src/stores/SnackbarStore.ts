import { defineStore } from "pinia";

export interface Snackbar {
  id: string;
  type: "success" | "error";
  shortDesc: string;
  longDesc?: string;
  action?: () => Promise<void>;
  actionLabel?: string;
  vanishAfter?: number;
}

interface SnackbarStoreState {
  snackbars: Snackbar[];
}

export const useSnackbarStore = defineStore("SnackbarStore", {
  state: (): SnackbarStoreState => {
    return {
      snackbars: [],
    };
  },

  actions: {
    show(snackbar: Snackbar) {
      this.hide(snackbar);
      this.snackbars.push(snackbar);
    },

    hide(snackbar: Snackbar) {
      this.snackbars = this.snackbars.filter((s) => s.id !== snackbar.id);
    },
  },
});

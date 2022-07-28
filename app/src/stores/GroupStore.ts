import { defineStore } from "pinia";
import api from "@/utility/api";
import type { AxiosError, AxiosResponse } from "axios";
import type { Group, Objective, Role } from "@/models/generated";
import { useObjectiveStore } from "@/stores/ObjectiveStore";
import { useRoleStore } from "@/stores/RoleStore";

const endpointUrl = `causes/${collectme?.cause}/groups`;

interface GroupResponseSuccess extends AxiosResponse {
  data: {
    data: Group[];
    included: Objective[] | Role[];
  };
}

interface GroupStoreState {
  groups: Map<string, Group>;
  isLoading: boolean;
  error: AxiosError | null;
}

async function addResponseData(response: GroupResponseSuccess) {
  useGroupStore().addGroups(response.data.data);

  response.data.included.forEach((item) => {
    if (item.type === "objective") {
      useObjectiveStore().addObjective(<Objective>item);
    } else if (item.type === "role") {
      useRoleStore().addRole(<Role>item);
    }
  });
}

export const useGroupStore = defineStore("GroupStore", {
  state: (): GroupStoreState => {
    return {
      groups: new Map(),
      isLoading: false,
      error: null,
    };
  },

  actions: {
    async fetch() {
      this.isLoading = true;

      await api(true)
        .get(endpointUrl)
        .then((resp: GroupResponseSuccess) => addResponseData(resp));

      this.isLoading = false;
    },

    addGroups(groups: Group[]) {
      for (const group of groups) {
        if (group.id != null) {
          this.groups.set(group.id, group);
        }
      }
    },
  },

  getters: {
    myPersonalGroup: (state: GroupStoreState): Group | null => {
      let myGroup: Group | null = null;
      state.groups.forEach((group: Group) => {
        if (group.attributes.type === "person" && group.attributes.writeable) {
          myGroup = group;
        }
      });

      return myGroup;
    },
  },
});

import {defineStore} from 'pinia';
import api from '@/utility/api';
import type {AxiosError, AxiosResponse} from "axios";
import type {Group, Objective, Role} from "@/models/generated";

const endpointUrl = `causes/${collectme?.cause}/group`;

interface GroupResponseSuccess extends AxiosResponse {
    data: {
        data: Group[];
        included: Objective[] | Role[];
    }
}

interface GroupStoreState {
    groups: Map<string, Group>;
    isLoading: boolean;
    error: AxiosError | null;
}

async function addResponseData(response: GroupResponseSuccess) {
    useGroupStore().addGroups(response.data.data);
}

export const useGroupStore = defineStore('GroupStore', {
    state: (): GroupStoreState => {
        return {
            groups: new Map(),
            isLoading: false,
            error: null,
        }
    },

    actions: {
        async fetch() {
            this.isLoading = true;

            await api(true, true).get(endpointUrl)
                .then((resp: GroupResponseSuccess) => addResponseData(resp))

            this.isLoading = false;
        },

        addGroups(groups: Group[]) {
            for (const group of groups) {
                if (group.id != null) {
                    this.groups.set(group.id, group);
                }
            }
        }
    },
});
import {defineStore} from 'pinia';
import api from '@/utility/api';
import type {AxiosResponse} from "axios";
import type {Activity, Group, PaginationLinks} from "@/models/generated";
import {useGroupStore} from "@/stores/GroupStore";

const endpointUrl = `causes/${collectme?.cause}/activity`;

interface ActivityResponseSuccess extends AxiosResponse {
    data: {
        data: Activity[];
        included: Group[];
        links: PaginationLinks;
    }
}

interface ActivityStoreState {
    activities: Activity[];
    isLoading: boolean;
    error: any | null;
    next: string | null;
    first: string | null;
}

function addResponseData(response: ActivityResponseSuccess) {
    useActivityStore().next = response.data.links.next ?? null;
    useActivityStore().first = response.data.links.first ?? null;
    useActivityStore().activities.push(...response.data.data);
    useGroupStore().addGroups(response.data.included);
}

export const useActivityStore = defineStore('ActivityStore', {
    state: (): ActivityStoreState => {
        return {
            activities: [],
            isLoading: false,
            error: null,
            next: null,
            first: null,
        }
    },

    actions: {
        async fetchFirst() {
            this.isLoading = true;
            this.error = null;

            try {
                await api(false, true)
                    .get(endpointUrl, {params: {'filter[count]': 'gt(0)'}})
                    .then((resp: ActivityResponseSuccess) => addResponseData(resp))
            } catch (error: any) {
                this.error = error;
            } finally {
                this.isLoading = false;
            }
        },

        async fetchMore() {
            if (!this.next) {
                return this.fetchFirst();
            }

            this.isLoading = true;
            this.error = null;

            try {
                await api(false, true)
                    .get(this.next)
                    .then((resp: ActivityResponseSuccess) => addResponseData(resp))
            } catch (error: any) {
                this.error = error;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
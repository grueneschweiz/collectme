import {defineStore} from 'pinia';
import api from '@/utility/api';
import type {AxiosResponse} from "axios";
import type {Activity, Group, PaginationLinks} from "@/models/generated";
import {useGroupStore} from "@/stores/GroupStore";

const endpointUrl = `causes/${collectme?.cause}/activities`;

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
    prev: string | null;
    next: string | null;
    first: string | null;
}

function addResponseData(response: ActivityResponseSuccess, prev: boolean = false) {
    useGroupStore().addGroups(response.data.included);
    addActivities(response.data.data);

    const store = useActivityStore();

    if (prev || !useActivityStore().prev) {
        store.prev = response.data.links.prev ?? null;
    }

    if (!prev || !useActivityStore().next) {
        store.next = response.data.links.next ?? null;
    }

    store.first = response.data.links.first ?? null;
}

function addActivities(newActivities: Activity[]) {
    const storedActivities: Activity[] = useActivityStore().activities;

    if (!storedActivities.length) {
        storedActivities.push(...newActivities);
        return;
    }

    newActivities.reverse().forEach((newActivity: Activity) => {
        if (!newActivity.attributes.created) {
            return;
        }

        if (newActivity.attributes.created > storedActivities[0]!.attributes.created!) {
            storedActivities.unshift(newActivity);
            return;
        }

        if (newActivity!.attributes.created < storedActivities[storedActivities.length - 1]!.attributes.created!) {
            storedActivities.push(newActivity);
            return;
        }

        for (let i = 0; i < storedActivities.length; i++) {
            if (newActivity.attributes.created >= storedActivities[i].attributes.created!) {
                if (newActivity.id !== storedActivities[i].id) {
                    storedActivities.splice(i, 0, newActivity);
                }

                return;
            }
        }
    })
}

export const useActivityStore = defineStore('ActivityStore', {
    state: (): ActivityStoreState => {
        return {
            activities: [],
            isLoading: false,
            error: null,
            prev: null,
            next: null,
            first: null,
        }
    },

    actions: {
        async fetchFirst() {
            this.isLoading = true;
            this.error = null;

            try {
                await api(false)
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
                await api(false)
                    .get(this.next)
                    .then((resp: ActivityResponseSuccess) => addResponseData(resp))
            } catch (error: any) {
                this.error = error;
            } finally {
                this.isLoading = false;
            }
        },

        async update() {
            if (!this.prev) {
                return this.fetchFirst();
            }

            this.isLoading = true;
            this.error = null;

            try {
                await api(false)
                    .get(this.prev)
                    .then((resp: ActivityResponseSuccess) => addResponseData(resp, true))
            } catch (error: any) {
                this.error = error;
            } finally {
                this.isLoading = false;
            }
        }
    },
});
import {defineStore} from 'pinia';
import type {Objective} from "@/models/generated";
import api from "@/utility/api";
import type {AxiosResponse} from "axios";

const endpointUrl = 'objectives';

interface ObjectiveResponseSuccess extends AxiosResponse {
    data: {
        data: Objective;
    }
}

interface ObjectiveStoreState {
    objectives: Map<string, Objective>;
    isLoading: boolean;
}

export const useObjectiveStore = defineStore('ObjectiveStore', {
    state: (): ObjectiveStoreState => {
        return {
            objectives: new Map(),
            isLoading: false,
        }
    },

    actions: {
        async create(objective: Objective) {
            this.isLoading = true;

            try {
                const resp = await api(true, false)
                    .post<{data: Objective}, ObjectiveResponseSuccess>(endpointUrl, {data: objective});
                this.addObjective(resp.data.data);
            } finally {
                this.isLoading = false;
            }
        },

        addObjective(objective: Objective) {
            if (objective.id != null) {
                this.objectives.set(objective.id, objective);
            }
        }
    },

    getters: {
        getObjectivesByGroupId: (state: ObjectiveStoreState): (groupId: string) => Objective[] => {
            return (groupId: string): Objective[] => {

                const groupObjectives: Objective[] = [];
                state.objectives.forEach((objective: Objective) => {
                    if (objective.relationships.group.data.id === groupId) {
                        groupObjectives.push(objective);
                    }
                });

                return groupObjectives;
            }
        },

        getHighestObjectiveByGroupId: () => {
            return (groupId: string): Objective|null => {

                const groupObjectives = useObjectiveStore().getObjectivesByGroupId(groupId);

                if (!groupObjectives.length) {
                    return null;
                }

                return groupObjectives.reduce((highest: Objective, objective: Objective) => {
                    if (objective.attributes.objective > highest.attributes.objective) {
                        return objective;
                    }
                    return highest;
                }, groupObjectives[0]);
            }
        }
    },
});
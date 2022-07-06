import {defineStore} from 'pinia';
import type {Objective} from "@/models/generated";


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
        addObjective(objective: Objective) {
            if (objective.id != null) {
                this.objectives.set(objective.id, objective);
            }
        }
    },

    getters: {
        getObjectivesByGroupId: (state: ObjectiveStoreState) => {
            return (groupId: string) => {

                const groupObjectives: Objective[] = [];
                state.objectives.forEach((objective: Objective) => {
                    if (objective.relationships.group.data.id === groupId) {
                        groupObjectives.push(objective);
                    }
                });

                return groupObjectives;
            }
        }
    },
});
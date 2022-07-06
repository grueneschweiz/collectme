import {defineStore} from 'pinia';
import type {Role} from "@/models/generated";


interface RoleStoreState {
    roles: Map<string, Role>;
    isLoading: boolean;
}

export const useRoleStore = defineStore('RoleStore', {
    state: (): RoleStoreState => {
        return {
            roles: new Map(),
            isLoading: false,
        }
    },

    actions: {
        addRole(role: Role) {
            if (role.id != null) {
                this.roles.set(role.id, role);
            }
        }
    },
});
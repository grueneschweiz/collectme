import {defineStore} from 'pinia';
import api from '@/utility/api';
import type {AxiosError, AxiosResponse} from "axios";
import type {User} from "@/models/generated";

const endpointUrl = `users/me`;

interface UserResponseSuccess extends AxiosResponse {
    data: {
        data: User;
    }
}

interface UserStoreState {
    me: User|null;
    isLoading: boolean;
    error: AxiosError | null;
}

export const useUserStore = defineStore('UserStore', {
    state: (): UserStoreState => {
        return {
            me: null,
            isLoading: false,
            error: null,
        }
    },

    actions: {
        async fetch() {
            this.isLoading = true;

            await api(true, true).get(endpointUrl)
                .then((resp: UserResponseSuccess) => this.me = resp.data.data)

            this.isLoading = false;
        },
    },
});
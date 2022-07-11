import axios, {AxiosError, AxiosResponse} from 'axios';
import axiosRetry from 'axios-retry';
import router from "@/router";
import type {Snackbar} from "@/stores/SnackbarStore";
import {useSnackbarStore} from "@/stores/SnackbarStore";
import t from '@/utility/i18n';

export interface JsonApiError {
    status: number;
    title: string;
    detail?: string;
    source?: {
        pointer?: string;
        parameter?: string;
    },
    meta?: any;
}

export interface ErrorResponse extends AxiosResponse {
    data: {
        errors: JsonApiError[];
    }
}

function errorHandler(error: any) {
    console.log(error);

    if (error && axios.isAxiosError(error) && error.response?.status === 401) {
        useSnackbarStore().show({
            id: error.response?.status.toString() ?? 'unknown',
            type: 'error',
            shortDesc: t('General.Error.unauthenticated'),
            vanishAfter: 5000,
        } as Snackbar);

        router.push('/login');
        return Promise.reject(error);
    }

    if (!axios.isAxiosError(error)) {
        useSnackbarStore().show({
            id: error.toString(),
            type: 'error',
            shortDesc: t('General.Error.unspecificTitle'),
            longDesc: t('General.Error.blameTheGoblins'),
            vanishAfter: 10000,
        } as Snackbar);
        return Promise.reject(error);
    }

    const axiosError = (error as AxiosError);

    if (axiosError.response?.status === 422) {
        // 422 is a validation error
        return Promise.reject(error);
    }

    let msg: Snackbar;
    if (axiosError.response?.status === 500) {
        if (axiosError.response.data) {
            console.log(axiosError.response.data);
        }

        msg = {
            id: axiosError.request.url,
            type: 'error',
            shortDesc: t('General.Error.blameTheDevTitle'),
            longDesc: t('General.Error.blameTheDev',
                {email: atob(collectme.encodedAdminEmail)}
            ),
            vanishAfter: 30000,
        } as Snackbar
    } else {
        msg = {
            id: axiosError.request.url,
            type: 'error',
            shortDesc: t('General.Error.unspecificTitle'),
            longDesc: t('General.Error.blameTheGoblins'),
            actionLabel: t('General.Error.tryAgain'),
            action: () => useApi(true).request(axiosError.config),
        } as Snackbar
    }

    useSnackbarStore().show(msg);

    return Promise.reject(error);
}

export default function useApi(handleErrors: boolean = false) {
    const instance = axios.create({
        baseURL: collectme.apiBaseUrl,
        headers: {
            'X-WP-Nonce': collectme.nonce,
        },
        xsrfHeaderName: 'X-WP-Nonce',
    });

    axiosRetry(instance);

    if (handleErrors) {
        instance.interceptors.response.use(
            (response: any) => response,
            (error: any) => errorHandler(error),
        );
    }

    return instance;
}
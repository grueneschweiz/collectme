import axios from 'axios';

export default () => {
    return axios.create({
        baseURL: collectme.apiBaseUrl,
        headers: {
            'X-WP-Nonce': collectme.nonce,
        },
        xsrfHeaderName: 'X-WP-Nonce',
    });
}

export function handleUnexpectedError(error: any) {
    console.log(error);
    alert('Unexpected Error. Please try again later.');
}
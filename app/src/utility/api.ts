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
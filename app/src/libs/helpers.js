import axios from '@/libs/axios';
import { ref } from 'vue';

/**
 * Makes an API request and returns reactive data states
 * @param {Object} options - Request configuration
 * @param {string} options.url - The endpoint URL
 * @param {string} options.method - The HTTP method (default: 'get') Options: 'get', 'post', 'put', 'patch', 'delete'
 * @param {Object} options.params - URL parameters for GET/DELETE requests
 * @param {Object} options.requestData - Request body for POST/PUT/PATCH requests
 * @param {Object} options.headers - Custom request headers
 * @returns {Object} - Reactive references for data, error, and loading states
 */
export const makeApiRequest = async ({ url, method = 'get', params = {}, requestData = {}, headers = { 'Content-Type': 'application/json' } }) => {
    const data = ref(null);
    const error = ref(null);
    const loading = ref(true);

    try {
        const isGetOrDelete = ['get', 'delete'].includes(method.toLowerCase());

        const config = {
            url,
            method,
            headers,
            ...(isGetOrDelete ? { params } : { data: requestData })
        };

        const response = await axios(config);
        data.value = response.data;
    } catch (err) {
        
        if (err.response) {
            error.value = err;
        } else {
            error.value = err;
        }
    } finally {
        loading.value = false;
    }

    return { data, error, loading };
};

import axios from 'axios';
const baseURL = import.meta.env.VITE_API_URL;

const instance = axios.create({
    baseURL,
    timeout: 60000
});

instance.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('eor__token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

instance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            localStorage.clear();

            if (window.location.pathname !== '/auth/login') {
                window.location.href = '/auth/login';
            }
        }
        return Promise.reject(error);
    }
);

export default instance;

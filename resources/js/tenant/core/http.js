import axios from 'axios';
import { toast } from './toast.js';

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
    withCredentials: true,
});

http.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

function loginUrl() {
    return document.querySelector('meta[name="tenant-login-url"]')?.content || '/admin/login';
}

function flashRedirect(message, redirect) {
    try {
        sessionStorage.setItem('tenant:flash', JSON.stringify({ message, type: 'success' }));
    } catch {
        /* storage unavailable */
    }
    location.assign(redirect);
}

http.interceptors.response.use(
    (response) => {
        const config = response.config;
        const method = (config.method || 'get').toLowerCase();
        const mutating = ['post', 'put', 'patch', 'delete'].includes(method);
        const data = response.data ?? {};
        const wantToast = config.toast !== false;

        if (mutating && wantToast && data.message) {
            toast.success(data.message);
        }

        if (data.redirect) {
            flashRedirect(data.message, data.redirect);
        }

        return data;
    },
    (error) => {
        const config = error.config || {};
        const silent = config.silent === true;
        const response = error.response;
        const status = response?.status;
        const data = response?.data ?? {};
        const toastType = data.toast_type || 'error';

        if (!silent) {
            switch (status) {
                case 401:
                    location.assign(loginUrl());
                    break;
                case 403:
                    toast[toastType](data.message || 'You do not have permission to perform this action.');
                    if (data.redirect) {
                        setTimeout(() => location.assign(data.redirect), 1200);
                    }
                    break;
                case 404:
                    toast[toastType]('The requested record no longer exists.');
                    break;
                case 409:
                    toast[toastType](data.message);
                    if (data.redirect) {
                        setTimeout(() => location.assign(data.redirect), 1200);
                    }
                    break;
                case 419:
                    toast.error('Your session expired. Reloading…');
                    setTimeout(() => location.reload(), 1200);
                    break;
                case 422:
                    if (config.isFormAction !== true) {
                        toast[toastType](data.message);
                    }
                    break;
                case 429:
                    toast[toastType]('Too many requests. Please wait a moment.');
                    break;
                default:
                    if (!status || status >= 500) {
                        toast[toastType]('Something went wrong. Please try again.');
                    } else if (data.toast_type) {
                        toast[toastType](data.message);
                    }
                    break;
            }
        }

        return Promise.reject({
            status,
            message: data.message,
            errors: data.errors,
            data,
        });
    },
);

export function request(method, url, data = null, options = {}) {
    const { toast: wantToast = true, silent = false, signal, ...rest } = options;
    const config = { method, url, signal, toast: wantToast, silent, ...rest };

    if (data instanceof FormData) {
        config.data = data;
        config.headers = { ...(config.headers || {}), 'Content-Type': 'multipart/form-data' };
    } else if (data !== null) {
        config.data = data;
    }

    return http.request(config);
}

export const get = (url, params = {}, options = {}) => request('get', url, null, { ...options, params });
export const post = (url, data = null, options = {}) => request('post', url, data, options);
export const put = (url, data = null, options = {}) => {
    if (data instanceof FormData) {
        data.append('_method', 'PUT');
        return request('post', url, data, options);
    }
    return request('put', url, data, options);
};
export const patch = (url, data = null, options = {}) => {
    if (data instanceof FormData) {
        data.append('_method', 'PATCH');
        return request('post', url, data, options);
    }
    return request('patch', url, data, options);
};
export const del = (url, data = null, options = {}) => {
    if (data instanceof FormData) {
        data.append('_method', 'DELETE');
        return request('post', url, data, options);
    }
    return request('delete', url, data, options);
};

export function upload(method, url, formData, onProgress, options = {}) {
    return request(method, url, formData, {
        ...options,
        onUploadProgress: (evt) => {
            if (onProgress && evt.total) {
                onProgress(Math.round((evt.loaded / evt.total) * 100));
            }
        },
    });
}

export default http;

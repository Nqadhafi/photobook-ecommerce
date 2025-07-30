// services/api.js
import axios from 'axios';

// Callback untuk logout (dikirim dari app.js)
let logoutCallback = null;

export const setLogoutCallback = (callback) => {
  logoutCallback = callback;
};

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
});

// Request interceptor
api.interceptors.request.use(
  config => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
      config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
  },
  error => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 419) {
      console.warn('CSRF token mismatch. Refresh the page.');
    }
    if (error.response?.status === 401 && logoutCallback) {
      logoutCallback();
    }
    return Promise.reject(error);
  }
);

export default api;
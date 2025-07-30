// app.js
require('./bootstrap');

import Vue from 'vue';
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue';

import App from './App.vue';
import router from './router';
import store from './store';

import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';

import { setLogoutCallback } from './services/api';

// Global components
Vue.component('app-layout', require('./components/layouts/AppLayout.vue').default);
Vue.component('auth-layout', require('./components/layouts/AuthLayout.vue').default);
Vue.component('navbar-component', require('./components/shared/Navbar.vue').default);
Vue.component('footer-component', require('./components/shared/Footer.vue').default);
Vue.component('loading-spinner', require('./components/shared/LoadingSpinner.vue').default);
Vue.component('notification-bell', require('./components/shared/NotificationBell.vue').default);

Vue.use(BootstrapVue);
Vue.use(IconsPlugin);

Vue.config.productionTip = false;

// Set logout callback untuk redirect konsisten
setLogoutCallback(() => {
  router.push('/login');
});

// Ambil CSRF cookie dan cek user
(async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    console.log('CSRF cookie loaded');
  } catch (error) {
    console.warn('Failed to load CSRF cookie', error);
  }

  try {
    await store.dispatch('auth/fetchUser');
    console.log('User fetched');
  } catch (error) {
    console.log('User not authenticated', error);
  } finally {
    new Vue({
      router,
      store,
      render: h => h(App)
    }).$mount('#app');
  }
})();
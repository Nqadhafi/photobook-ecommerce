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

// Set logout callback
setLogoutCallback(() => {
  router.push('/login');
});

// Hanya ambil CSRF cookie
axios.get('/sanctum/csrf-cookie')
  // .then(() => console.log('CSRF cookie loaded'))
  // .catch(err => console.warn('Failed to load CSRF cookie', err));

// Mount Vue tanpa menunggu fetchUser
new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app');
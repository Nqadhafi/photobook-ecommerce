require('./bootstrap');
import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import App from './App.vue';
import router from './router';
import store from './store';

// Bootstrap CSS & JS
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';

Vue.use(BootstrapVue);

// Global components registration
Vue.component('app-layout', require('./components/layouts/AppLayout.vue').default);
Vue.component('auth-layout', require('./components/layouts/AuthLayout.vue').default);
Vue.component('navbar-component', require('./components/shared/Navbar.vue').default);
Vue.component('footer-component', require('./components/shared/Footer.vue').default);
Vue.component('loading-spinner', require('./components/shared/LoadingSpinner.vue').default);
Vue.component('notification-bell', require('./components/shared/NotificationBell.vue').default);

// Global configuration
Vue.config.productionTip = false;

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

new Vue({
  router,
  store,
  render: h => h(App),
}).$mount('#app');
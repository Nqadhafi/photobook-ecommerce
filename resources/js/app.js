require('./bootstrap');

import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import App from './App.vue';
import router from './router';
import store from './store';

import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';

import axios from 'axios';

// ⚙️ Penting: Aktifkan withCredentials agar cookie dikirim
axios.defaults.withCredentials = true;

// Ambil CSRF cookie dan cek status login
(async () => {
  try {
    // 1. Dapatkan CSRF cookie
    await axios.get('/sanctum/csrf-cookie');
    console.log('CSRF cookie loaded');
  } catch (error) {
    console.warn('Gagal ambil CSRF cookie', error);
  }

  try {
    // 2. Cek apakah user sudah login
    await store.dispatch('auth/fetchUser');
    console.log('User session loaded');
  } catch (error) {
    // Tidak masalah jika gagal — artinya user belum login
    console.log('User not authenticated (optional)', error);
  } finally {
    // 3. Jalankan Vue setelah semua pengecekan selesai
    Vue.use(BootstrapVue);

    // Global components
    Vue.component('app-layout', require('./components/layouts/AppLayout.vue').default);
    Vue.component('auth-layout', require('./components/layouts/AuthLayout.vue').default);
    Vue.component('navbar-component', require('./components/shared/Navbar.vue').default);
    Vue.component('footer-component', require('./components/shared/Footer.vue').default);
    Vue.component('loading-spinner', require('./components/shared/LoadingSpinner.vue').default);
    Vue.component('notification-bell', require('./components/shared/NotificationBell.vue').default);

    Vue.config.productionTip = false;

    new Vue({
      router,
      store,
      render: h => h(App),
    }).$mount('#app');
  }
})();
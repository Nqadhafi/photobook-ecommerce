import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store';

Vue.use(VueRouter);

const routes = [
{ 
  path: '/', 
  name: 'Home', 
  component: () => import('../views/pages/Home.vue'),
  meta: { requiresAuth: false } 
},
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/dashboard/Dashboard.vue'), meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: () => import('../views/auth/Login.vue'), meta: { guest: true, requiresAuth: false } },
  { path: '/register', name: 'Register', component: () => import('../views/auth/Register.vue'), meta: { guest: true, requiresAuth: false } },
  { path: '/profile', name: 'Profile', component: () => import('../views/auth/Profile.vue'), meta: { requiresAuth: true } },
  { path: '/products', name: 'Products', component: () => import('../views/products/ProductList.vue'), meta: { requiresAuth: false } },
  { path: '/products/:id', name: 'ProductDetail', component: () => import('../views/products/ProductDetail.vue'), meta: { requiresAuth: false } },
  { path: '/cart', name: 'Cart', component: () => import('../views/cart/Cart.vue'), meta: { requiresAuth: true } },
  { path: '/checkout', name: 'Checkout', component: () => import('../views/checkout/Checkout.vue'), meta: { requiresAuth: true } },
  { path: '/orders', name: 'Orders', component: () => import('../views/orders/OrderList.vue'), meta: { requiresAuth: true } },
  { path: '/orders/:id', name: 'OrderDetail', component: () => import('../views/orders/OrderDetail.vue'), meta: { requiresAuth: true } },
  { path: '/orders/:id/upload', name: 'FileUpload', component: () => import('../views/upload/FileUpload.vue'), meta: { requiresAuth: true } },
  { path: '*', name: 'NotFound', component: () => import('../views/pages/NotFound.vue') }
];

const router = new VueRouter({
  mode: 'history',
  base: '/',
  routes
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const isAuthenticated = store.getters['auth/isAuthenticated'];
  const userFetched = store.state.auth.userFetched;

  // Tunggu user di-fetch jika belum
  if (!userFetched) {
    await store.dispatch('auth/fetchUser').catch(() => {
      // Gagal = belum login, tidak masalah
    });
  }

  const nowAuthenticated = store.getters['auth/isAuthenticated'];

  // 1. Cek jika route butuh login
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!nowAuthenticated) {
      return next('/login');
    }
    return next();
  }

  // 2. Cek jika route hanya untuk guest (belum login)
  if (to.matched.some(record => record.meta.guest)) {
    if (nowAuthenticated) {
      return next('/dashboard'); // 🚫 Sudah login? Jangan ke login/register
    }
    return next();
  }

  // 3. Izinkan akses
  next();
});

export default router;
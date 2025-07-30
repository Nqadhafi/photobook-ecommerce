// router.js
import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store';

Vue.use(VueRouter);

const routes = [
  { path: '/', name: 'Home', component: () => import('../views/pages/Home.vue') },
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/dashboard/Dashboard.vue'), meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: () => import('../views/auth/Login.vue'), meta: { guest: true } },
  { path: '/register', name: 'Register', component: () => import('../views/auth/Register.vue'), meta: { guest: true } },
  { path: '/profile', name: 'Profile', component: () => import('../views/auth/Profile.vue'), meta: { requiresAuth: true } },
  { path: '/products', name: 'Products', component: () => import('../views/products/ProductList.vue') },
  { path: '/products/:id', name: 'ProductDetail', component: () => import('../views/products/ProductDetail.vue') },
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

  if (!userFetched && !isAuthenticated) {
    await store.dispatch('auth/fetchUser').catch(() => {
      // Biarkan gagal — artinya memang belum login
    });
  }

  const nowAuthenticated = store.getters['auth/isAuthenticated'];

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!nowAuthenticated) {
      return next('/login');
    }
    return next();
  }

  if (to.matched.some(record => record.meta.guest)) {
    if (nowAuthenticated) {
      return next('/dashboard');
    }
    return next();
  }

  next();
});

export default router;
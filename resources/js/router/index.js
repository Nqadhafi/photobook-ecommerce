import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '../store';
import AdminLayout from '../components/layouts/AdminLayout.vue';

Vue.use(VueRouter);

const routes = [
  // Public & Guest
  { path: '/', name: 'Home', component: () => import('../views/pages/Home.vue') },
  { path: '/login', name: 'Login', component: () => import('../views/auth/Login.vue'), meta: { guest: true } },
  { path: '/register', name: 'Register', component: () => import('../views/auth/Register.vue'), meta: { guest: true } },

  // Authenticated User (role: user)
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/dashboard/Dashboard.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/profile', name: 'Profile', component: () => import('../views/auth/Profile.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/cart', name: 'Cart', component: () => import('../views/cart/Cart.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/checkout', name: 'Checkout', component: () => import('../views/checkout/Checkout.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/orders', name: 'Orders', component: () => import('../views/orders/OrderList.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/orders/:id', name: 'OrderDetail', component: () => import('../views/orders/OrderDetail.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/orders/:id/upload', name: 'FileUpload', component: () => import('../views/upload/FileUpload.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },
  { path: '/orders/:id/timeline', name: 'OrderTimeline', component: () => import('../views/orders/OrderTimeline.vue'), meta: { requiresAuth: true, allowedRoles: ['user'] } },

  // Product detail bisa diakses semua
  { path: '/products', name: 'Products', component: () => import('../views/products/ProductList.vue') },
  { path: '/products/:id', name: 'ProductDetail', component: () => import('../views/products/ProductDetail.vue') },

  // Admin Routes
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresRole: 'admin' },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: () => import('../views/admin/Dashboard.vue')
      },
      {
        path: 'logout',
        name: 'AdminLogout',
        component: () => import('../views/admin/AdminLogout.vue') // Tambahkan file ini jika belum ada
      },
      {
        path: 'orders',
        name: 'AdminOrders',
        component: () => import('../views/admin/orders/Index.vue')
      },
      {
        path: 'orders/:id',
        name: 'AdminOrderDetail', // <-- Nama ini digunakan di Index.vue dan Back button
        component: () => import('../views/admin/orders/Show.vue'),
        props: true
      },
      {
    path: 'products',
    name: 'AdminProducts', // <-- Untuk link di sidebar AdminLayout.vue
    component: () => import('../views/admin/products/Index.vue')
      },
      {
          path: 'products/create',
          name: 'AdminProductCreate',
          component: () => import('../views/admin/products/Create.vue')
      },
      {
          path: 'products/:id/edit',
          name: 'AdminProductEdit',
          component: () => import('../views/admin/products/Edit.vue'),
          props: true
      },
    ]
  },

  // 404 fallback (harus di akhir)
  { path: '*', name: 'NotFound', component: () => import('../views/pages/NotFound.vue') }
];

const router = new VueRouter({
  mode: 'history',
  base: '/',
  routes
});

// --- Navigation Guards ---
router.beforeEach(async (to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const guestOnly = to.matched.some(record => record.meta.guest);
  const requiredRole = to.matched.find(record => record.meta.requiresRole)?.meta.requiresRole;
  const allowedRoles = to.matched.find(record => record.meta.allowedRoles)?.meta.allowedRoles;

  let isAuthenticated = store.getters['auth/isAuthenticated'];
  const userFetched = store.state.auth.userFetched;

  // Fetch user jika belum
  if (!userFetched) {
    try {
      await store.dispatch('auth/fetchUser');
      isAuthenticated = store.getters['auth/isAuthenticated'];
    } catch (error) {
      console.warn('Failed to fetch user:', error);
      isAuthenticated = false;
    }
  }

  const userRole = store.getters['auth/user']?.role;

  // --- Auth required ---
  if (requiresAuth && !isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } });
  }

  // --- Guest only ---
  if (guestOnly && isAuthenticated) {
    if (userRole === 'admin' || userRole === 'super_admin') {
      return next({ name: 'AdminDashboard' });
    }
    return next({ name: 'Dashboard' });
  }

  // --- Role-based access ---
  if (requiredRole) {
    const roleAllowed =
      (requiredRole === 'admin' && (userRole === 'admin' || userRole === 'super_admin')) ||
      (requiredRole === 'super_admin' && userRole === 'super_admin');

    if (!roleAllowed) {
      console.warn(`Access denied to ${to.path}. Required role: ${requiredRole}, User role: ${userRole}`);
      return next({ name: 'Dashboard' }); // atau halaman forbidden jika ada
    }
  }

  // --- AllowedRoles check (user-only routes) ---
  if (allowedRoles && !allowedRoles.includes(userRole)) {
    console.warn(`Access denied to ${to.path}. Allowed roles: ${allowedRoles}, User role: ${userRole}`);
    return next({ name: userRole === 'admin' || userRole === 'super_admin' ? 'AdminDashboard' : 'Dashboard' });
  }

  next(); // izinkan akses
});

export default router;

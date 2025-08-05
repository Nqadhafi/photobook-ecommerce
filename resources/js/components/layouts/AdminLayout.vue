<!-- resources/js/layouts/AdminLayout.vue -->
<template>
  <div id="admin-layout" class="d-flex flex-column min-vh-100">
    <!-- Navbar Admin -->
    <b-navbar toggleable="lg" type="dark" variant="dark" class="flex-md-nowrap shadow">
      <b-navbar-brand href="#">Admin Panel</b-navbar-brand>

      <b-navbar-toggle target="admin-navbar-collapse"></b-navbar-toggle>

      <b-collapse id="admin-navbar-collapse" is-nav>
        <b-navbar-nav class="ml-auto">
          <!-- Dropdown User Menu -->
          <b-nav-item-dropdown right>
            <!-- Using 'button-content' slot -->
            <template #button-content>
              <em>{{ currentUser ? currentUser.name : 'User' }}</em>
            </template>
            <b-dropdown-item :to="{ name: 'Profile' }">
              <b-icon icon="person-circle" class="mr-2"></b-icon>Profile
            </b-dropdown-item>
            <b-dropdown-item @click="handleLogout"> 
            <b-icon icon="box-arrow-right" class="mr-2"></b-icon>Sign Out
            </b-dropdown-item>
          </b-nav-item-dropdown>
        </b-navbar-nav>
      </b-collapse>
    </b-navbar>

    <div class="container-fluid flex-grow-1">
      <div class="row h-100">
        <!-- Sidebar Admin -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
          <div class="sidebar-sticky pt-3">
            <b-nav vertical>
              <b-nav-item :to="{ name: 'AdminDashboard' }" active-class="active">
                <b-icon icon="speedometer2" class="mr-2"></b-icon>Dashboard
              </b-nav-item>
              
              <!-- Menu untuk Admin dan Super Admin -->
              <b-nav-item :to="{ name: 'AdminOrders' }" active-class="active">
                <b-icon icon="list" class="mr-2"></b-icon>Orders
              </b-nav-item>

              <!-- Menu khusus Super Admin -->
              <b-nav-item v-if="isSuperAdmin" class="mt-4">
                <strong><b-icon icon="gear" class="mr-2"></b-icon>Management</strong>
              </b-nav-item>
              <b-nav-item v-if="isSuperAdmin" :to="{ name: 'AdminUsers' }" active-class="active">
                <b-icon icon="people" class="mr-2"></b-icon>Admin Users
              </b-nav-item>
              <b-nav-item v-if="isSuperAdmin" :to="{ name: 'AdminProducts' }" active-class="active">
                <b-icon icon="box" class="mr-2"></b-icon>Products
              </b-nav-item>
              <b-nav-item v-if="isSuperAdmin" :to="{ name: 'AdminTemplates' }" active-class="active">
                <b-icon icon="file-earmark-image" class="mr-2"></b-icon>Templates
              </b-nav-item>
              <b-nav-item v-if="isSuperAdmin" :to="{ name: 'AdminCoupons' }" active-class="active">
                <b-icon icon="ticket" class="mr-2"></b-icon>Coupons
              </b-nav-item>
              <b-nav-item v-if="isSuperAdmin" :to="{ name: 'AdminDeskprints' }" active-class="active">
                <b-icon icon="printer" class="mr-2"></b-icon>Deskprints
              </b-nav-item>
            </b-nav>
          </div>
        </nav>

        <!-- Main Content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
          <router-view />
        </main>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';

export default {
  name: 'AdminLayout',
  computed: {
    ...mapGetters('auth', ['user']),
    currentUser() {
      return this.user;
    },
    isAdmin() {
      return this.user && (this.user.role === 'admin' || this.user.role === 'super_admin');
    },
    isSuperAdmin() {
      return this.user && this.user.role === 'super_admin';
    }
  },
  methods: {
    // Tidak perlu mapActions jika menggunakan dispatch langsung
    async handleLogout() { // <-- Ganti nama method lokal
      try {
        await this.$store.dispatch('auth/logout'); // <-- Panggil action secara eksplisit
        // Redirect ke halaman login setelah logout berhasil
        this.$router.push({ name: 'Login' });
      } catch (error) {
        console.error('Logout failed:', error);
        // Tetap redirect ke login meskipun API gagal
        this.$router.push({ name: 'Login' });
      }
    }
  }
};
</script>

<style scoped>
/* Sidebar */
.sidebar {
  height: calc(100vh - 56px); /* Tinggi penuh dikurangi tinggi navbar */
  position: fixed;
  top: 56px; /* Sesuaikan dengan tinggi navbar */
  bottom: 0;
  left: 0;
  z-index: 100; /* Di belakang navbar */
  padding: 0;
  box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
  overflow-y: auto; /* Tambahkan scroll jika konten sidebar panjang */
}

.sidebar-sticky {
  position: relative;
  top: 0;
  height: calc(100vh - 56px); /* Sesuaikan dengan tinggi navbar */
  padding-top: .5rem;
  overflow-x: hidden;
  overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
}

.sidebar .nav-link {
  font-weight: 500;
  color: #333;
  border-radius: 0;
}

.sidebar .nav-link:hover {
  color: #007bff;
  background-color: rgba(0, 123, 255, .1);
}

.sidebar .nav-link.active {
  color: #007bff;
  background-color: rgba(0, 123, 255, .1);
}

/* Main content */
main {
  height: calc(100vh - 56px); /* Tinggi penuh dikurangi tinggi navbar */
  overflow-y: auto; /* Tambahkan scroll untuk konten utama jika perlu */
}
</style>

<template>
  <div class="navbar-wrapper">
    <!-- ===== TOPBAR (hanya 3 item) ===== -->
    <div class="topbar d-none d-md-block">
      <div class="topbar-inner">
        <div class="left">
          <b-link :to="{ name: 'Home' }" class="link">Main Website</b-link>
          <span class="divider">|</span>
          <span class="link">Ikuti kami</span>
          <a href="https://instagram.com" target="_blank" rel="noopener" class="icon-link" aria-label="Instagram">
            <b-icon icon="instagram"></b-icon>
          </a>
          <a href="https://tiktok.com" target="_blank" rel="noopener" class="icon-link" aria-label="TikTok">
            <b-icon icon="music-note"></b-icon>
          </a>
          <span class="divider">|</span>
          <b-link :to="{ name: 'Help' }" class="link">Bantuan</b-link>
        </div>

        <!-- Auth Section di Topbar -->
        <div class="right">
          <template v-if="isAuthenticated">
            <b-dropdown right no-caret variant="link" class="user-dropdown-topbar" toggle-class="topbar-dropdown-toggle">
              <template #button-content>
                <b-icon icon="person-circle" font-scale="1.2" class="me-1"></b-icon>
                <span class="text-white">{{ user && user.name ? user.name : 'Akun' }}</span>
                <b-icon icon="chevron-down" font-scale="0.8" class="ms-1"></b-icon>
              </template>
              <b-dropdown-item :to="{ name: 'Profile' }" class="topbar-dropdown-item">
                <b-icon icon="gear" class="me-2 text-primary"></b-icon> Profil Saya
              </b-dropdown-item>
              <b-dropdown-item :to="{ name: 'Orders' }" class="topbar-dropdown-item">
                <b-icon icon="list" class="me-2 text-success"></b-icon> Riwayat Pesanan
              </b-dropdown-item>
              <b-dropdown-divider></b-dropdown-divider>
              <b-dropdown-item @click="handleLogout" class="text-danger topbar-dropdown-item">
                <b-icon icon="box-arrow-right" class="me-2"></b-icon> Keluar
              </b-dropdown-item>
            </b-dropdown>
          </template>

          <template v-else>
            <div class="auth-links">
              <b-link :to="{ name: 'Login' }" class="link auth-link">Masuk</b-link>
              <span class="divider">|</span>
              <b-link :to="{ name: 'Register' }" class="link auth-link auth-register">Daftar</b-link>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ===== NAVBAR INTI ===== -->
    <b-navbar
      toggleable="lg"
      type="dark"
      variant="white"
      class="mainbar position-relative px-3 py-2 border-bottom border-light shadow-sm"
      fixed="top"
    >
      <div class="navbar-gradient"></div>

      <div class="container-fluid d-flex align-items-center justify-content-between px-2 px-lg-3 topbar-inner">
        <!-- Brand -->
        <b-navbar-brand :to="{ name: 'Home' }" class="d-flex align-items-center z-2">
          <img src="../assets/logo.svg" alt="Photobook Studio" height="36" />
        </b-navbar-brand>

        <!-- Search (desktop) -->
        <b-navbar-nav class="mx-auto d-none d-lg-flex justify-content-center" style="flex:1; max-width:45rem;">
          <b-form @submit.prevent="handleSearch" class="w-100">
            <b-input-group class="rounded-pill shadow-sm">
              <b-form-input
                v-model="searchQuery"
                placeholder="Cari photobook, ukuran, atau tema…"
                class="form-control-lg px-4 py-2 border-0"
              />
              <b-input-group-append>
                <b-button type="submit" variant="primary" class="px-3 ">
                  <b-icon icon="search" font-scale="1.1"></b-icon>
                </b-button>
              </b-input-group-append>
            </b-input-group>
          </b-form>
        </b-navbar-nav>

        <!-- Right: Cart Only -->
      <b-navbar-nav class="d-flex align-items-center gap-3 z-2 ms-auto">
          <!-- Cart (Desktop) -->
        <b-nav-item
          :to="{ name: 'Cart' }"
          class="position-relative pr-2 mr-5 text-white hover-white d-none d-lg-block"
          active-class="text-light"
        >
          <b-icon icon="cart" font-scale="1.3" class="transition-scale"></b-icon>
          <b-badge
            v-if="cartItemCount > 0"
            variant="danger"
            pill
            class="position-absolute top-0 start-100 translate-middle px-2"
            style="font-size:.7rem; min-width:18px;"
          >
            {{ cartItemCount }}
          </b-badge>
        </b-nav-item>

          <!-- Mobile Auth (hanya untuk mobile karena topbar hidden di mobile) -->
          <div class="d-md-none">
            <!-- ====== AUTHENTICATED (MOBILE) ====== -->
            <template v-if="isAuthenticated">
              <b-nav-item-dropdown
                right
                no-caret
                class="user-dropdown-topbar"
                toggle-class="p-1 topbar-dropdown-toggle"
                menu-class="dropdown-menu--overlay"
                boundary="viewport"
                :popper-opts="{ positionFixed: true }"
                offset="0,8"
              >
                <template #button-content>
                  <b-icon icon="person-circle" font-scale="1.4" class="text-white"></b-icon>
                </template>
                <b-dropdown-item :to="{ name: 'Profile' }">
                  <b-icon icon="gear" class="me-2 text-primary"></b-icon> Profil Saya
                </b-dropdown-item>
                <b-dropdown-item :to="{ name: 'Orders' }">
                  <b-icon icon="list" class="me-2 text-success"></b-icon> Riwayat Pesanan
                </b-dropdown-item>
                <b-dropdown-divider></b-dropdown-divider>
                <b-dropdown-item @click="handleLogout" class="text-danger">
                  <b-icon icon="box-arrow-right" class="me-2"></b-icon> Keluar
                </b-dropdown-item>
              </b-nav-item-dropdown>
            </template>

            <!-- ====== GUEST (MOBILE) ====== -->
            <template v-else>
              <b-nav-item-dropdown
                right
                no-caret
                toggle-class="px-2 py-1"
                menu-class="dropdown-menu--overlay"
                boundary="viewport"
                :popper-opts="{ positionFixed: true }"
                offset="0,8"
              >
                <template #button-content>
                  <b-icon icon="person" font-scale="1.2" class="text-white"></b-icon>
                </template>
                <b-dropdown-item :to="{ name: 'Login' }">Masuk</b-dropdown-item>
                <b-dropdown-item :to="{ name: 'Register' }" class="fw-medium text-white">Daftar</b-dropdown-item>
              </b-nav-item-dropdown>
            </template>
          </div>
        </b-navbar-nav>
      </div>

      <!-- Search & Cart (mobile) -->
      <div class="w-100 d-lg-none px-3 pb-2">
        <div class="d-flex align-items-center gap-3">
          <b-form @submit.prevent="handleSearch" class="flex-grow-1 mb-0">
            <b-input-group class="rounded-pill shadow-sm">
              <b-form-input v-model="searchQuery" placeholder="Cari photobook…" class="px-4 py-2 border-0" />
              <b-input-group-append>
                <b-button type="submit" variant="primary" class="px-3">
                  <b-icon icon="search"></b-icon>
                </b-button>
              </b-input-group-append>
            </b-input-group>
          </b-form>
          <!-- Cart (Mobile) -->
          <b-link :to="{ name: 'Cart' }" class="position-relative text-white hover-white d-inline-block mobile-cart">
            <b-icon icon="cart" font-scale="1.3" class="transition-scale"></b-icon>
            <b-badge
              v-if="cartItemCount > 0"
              variant="danger"
              pill
              class="position-absolute top-0 start-100 translate-middle px-2"
              style="font-size:.7rem; min-width:18px;"
            >{{ cartItemCount }}</b-badge>
          </b-link>
        </div>
      </div>
    </b-navbar>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'Navbar',
  data() {
    return { searchQuery: '' };
  },
  computed: {
    ...mapGetters('auth', ['user', 'isAuthenticated']),
    ...mapGetters('cart', ['cartItemCount'])
  },
  async created() {
    if (this.isAuthenticated) {
      try {
        await this.$store.dispatch('cart/loadCart');
      } catch (e) {
        console.warn('Gagal memuat keranjang');
      }
    }
  },
  methods: {
    ...mapActions('auth', ['logout']),
    handleSearch() {
      const q = this.searchQuery.trim();
      if (q) {
        this.$router.push({ name: 'Products', query: { search: q, page: 1 } });
      } else {
        this.$router.push({ name: 'Products' });
      }
    },
    async handleLogout() {
      try {
        await this.logout();
        this.$router.push({ name: 'Login' });
        this.$store.dispatch('showNotification', {
          title: 'Berhasil',
          message: 'Anda telah keluar.',
          type: 'success'
        });
      } catch (e) {
        console.error('Logout error:', e);
        this.$router.push({ name: 'Login' });
      }
    }
  }
};
</script>

<!-- ====== CSS GLOBAL (TIDAK SCOPED) untuk memastikan overlay di mobile) ====== -->
<style>
:root {
  /* Sesuaikan tinggi navbar jika berbeda */
  --navbar-height: 60px;
}

/* Overlay dropdown khusus mobile agar MENINDIH elemen di bawahnya */
@media (max-width: 767.98px) {
.dropdown-menu--overlay {
  position: fixed !important;
  top: calc(var(--navbar-height) + 8px) !important;
  left: auto !important;
  right: 12px !important;

  width: auto !important;        /* tidak penuh layar */
  min-width: 180px !important;   /* seperti desktop */
  max-width: 260px !important;   /* batas wajar biar tidak kepanjangan */

  max-height: calc(100vh - var(--navbar-height) - 24px);
  overflow-y: auto;

  border-radius: 0.5rem;
  border: 1px solid rgba(0,0,0,.06);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
  background: #fff;
  z-index: 2005 !important;
}


  /* Pastikan parent tidak memotong dropdown */
  .navbar-wrapper,
  .mainbar,
  .container-fluid {
    overflow: visible !important;
  }

  /* Guardrails z-index */
  .mobile-cart { z-index: 1040 !important; }
}
</style>

<!-- ====== CSS ASLI KAMU (tetap SCOPED) ====== -->
<style scoped>
/* ========================
   TOPBAR (desktop)
   ======================== */
.topbar {
  background: linear-gradient(90deg, #007bff, #17a2b8);
  color: #fff;
  font-size: .875rem;
  z-index: 1035;
}
.topbar-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: .4rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.topbar-inner .left,
.topbar-inner .right {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.topbar .link {
  color: #fff;
  text-decoration: none;
  opacity: .95;
  transition: opacity .2s ease;
}
.topbar .link:hover { opacity: 1; }
.icon-link {
  color: #fff;
  text-decoration: none;
  opacity: .95;
  display: inline-flex;
  align-items: center;
  transition: opacity .2s ease;
}
.icon-link:hover { opacity: 1; }
.divider { opacity: .6; }

/* Auth Links di Topbar */
.auth-links {
  display: flex;
  align-items: center;
  gap: .5rem;
}
.auth-link {
  font-weight: 500;
  padding: .25rem .5rem;
  border-radius: .25rem;
  transition: all .2s ease;
}
.auth-register {
  background: rgba(255, 255, 255, .15);
  border: 1px solid rgba(255, 255, 255, .3);
}
.auth-register:hover {
  background: rgba(255, 255, 255, .25);
  border-color: rgba(255, 255, 255, .5);
}

/* User Dropdown di Topbar */
.user-dropdown-topbar { position: relative; z-index: 1040; }
.user-dropdown-topbar .topbar-dropdown-toggle {
  color: #fff !important;
  text-decoration: none;
  opacity: .95;
  padding: .25rem .5rem;
  border-radius: .25rem;
  transition: all .2s ease;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  position: static;
}
.user-dropdown-topbar .topbar-dropdown-toggle:hover {
  opacity: 1;
  background: rgba(255, 255, 255, .15);
}
.user-dropdown-topbar .topbar-dropdown-toggle:focus { box-shadow: none; }
.topbar-dropdown-item { font-size: .875rem; }

/* Pastikan dropdown profil muncul di atas semua elemen */
.user-dropdown-topbar .dropdown-menu { z-index: 2001 !important; }

/* ========================
   NAVBAR (mainbar)
   ======================== */
.mainbar {
  z-index: 1030;
  background: linear-gradient(90deg, #007bff, #17a2b8);
  border-bottom: none;
}
.navbar-gradient {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, #007bff, #17a2b8);
  z-index: -1;
  pointer-events: none;
}
.container-fluid { max-width: 1200px; }
@media (max-width: 991.98px) { .container-fluid { padding: 0 1rem; } }

/* Search input sizes */
.d-lg-flex > .form-control,
.form-control-lg { font-size: .95rem; }

/* White text hover effect */
.hover-white:hover { color: #f8f9fa !important; opacity: 0.9; }
.text-white { color: #fff !important; }

/* Buttons & effects */
.transition-scale { transition: transform .2s ease; }
.hover-primary:hover .transition-scale { transform: scale(1.15); }
.bg-gradient-primary { background: linear-gradient(90deg, #0d6efd, #6f42c1); }
.hover-lift:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 110, 253, .3); }
.transition-fast { transition: all .2s ease; }

/* Spacer: ganti hot keywords */
.hotkey-spacer {
  padding-bottom: .75rem;
  background: linear-gradient(90deg, #007bff, #17a2b8);
}

/* ========================
   DROPDOWN ELEVATION (SEMUA MODE)
   (Boleh dipakai untuk desktop jika mau)
   ======================== */
:deep(.elevated-dropdown),
.elevated-dropdown {
  position: fixed !important;
  z-index: 2000 !important;
  inset: auto !important;
  margin: 0 !important;
  transform: translate3d(0, 0, 0) !important;
  will-change: transform;
  border: none;
  border-radius: 0.5rem;
  padding: 0.5rem 0;
  background: #fff;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
  border: 1px solid rgba(0, 0, 0, .05);
  overflow-y: auto;
}
:deep(.elevated-dropdown.show),
.elevated-dropdown.show { z-index: 2000 !important; }

/* ========================
   MOBILE TUNING - POSISI DROPDOWN
   (Sebagian fungsi overlay dipindah ke CSS global non-scoped)
   ======================== */
@media (max-width: 767.98px) {
  .navbar-wrapper,
  .mainbar,
  .container-fluid { overflow: visible !important; }

  .mobile-cart {
    margin-left: 0;
    z-index: 1040;
    position: relative;
  }
  .d-lg-none .d-flex { gap: 1rem !important; justify-content: space-between !important; }
  .d-lg-none .flex-grow-1 { flex-grow: 1 !important; flex-shrink: 1 !important; min-width: 0 !important; }
  .d-lg-none .form-control { padding: 0.5rem 1rem !important; font-size: 0.95rem !important; }
  .d-lg-none .btn { padding: 0.5rem 1rem !important; }
}

/* ========================
   Z-INDEX GUARDRAILS
   ======================== */
.user-dropdown-topbar .dropdown-menu,
:deep(.user-dropdown-topbar .dropdown-menu) { z-index: 2000 !important; }
.topbar { z-index: 1035; }

/* HINDARI CLIPPING DARI PARENT */
.navbar-wrapper { overflow: visible !important; position: relative; z-index: 1030; }
:deep(.dropdown-menu) { transform: translate3d(0, 0, 0) !important; }

/* Memastikan dropdown profil muncul di atas elemen lain */
.user-dropdown { position: relative; z-index: 1040; }
:deep(.user-dropdown .dropdown-menu) { z-index: 1040 !important; position: absolute !important; }

@media (max-width: 767.98px) {
  .user-dropdown-topbar { z-index: 2002 !important; }
  :deep(.user-dropdown-topbar .dropdown-menu) { z-index: 2002 !important; }
  .mobile-cart { z-index: 1040 !important; }
}
</style>

<template>
  <div class="auth-layout d-flex align-items-center min-vh-100">
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="7" lg="5">
          <b-card class="auth-card shadow-soft">
            <b-card-header class="text-center bg-white border-0 pb-0">
              <h3 class="mb-2 brand-title">
                <b-icon icon="book" variant="primary" class="mr-1"></b-icon>
                Shabat Printing Photobook.
              </h3>
              <p class="text-muted small mb-0">Masuk untuk melanjutkan</p>
            </b-card-header>

            <b-card-body>
              <slot />
            </b-card-body>

            <b-card-footer class="text-center bg-white border-0 pt-0">
              <small class="text-muted">
                &copy; {{ new Date().getFullYear() }} Shabat Printing Photobook.
              </small>
            </b-card-footer>
          </b-card>
        </b-col>
      </b-row>

      <!-- Notifications -->
      <div class="notification-container">
        <b-alert
          v-for="notification in $store.getters.notifications"
          :key="notification.id"
          :variant="notification.type || 'info'"
          :show="true"
          dismissible
          @dismissed="$store.commit('REMOVE_NOTIFICATION', notification.id)"
          class="position-fixed"
          style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;"
        >
          <strong>{{ notification.title }}</strong>
          <p class="mb-0">{{ notification.message }}</p>
        </b-alert>
      </div>
    </b-container>
  </div>
</template>

<script>
export default {
  name: 'AuthLayout'
}
</script>

<style scoped>
/* Background gradient + subtle pattern agar prestise tapi adem */
.auth-layout {
  background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
  position: relative;
  padding: 2rem 0;
}
.auth-layout::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: .10;
  background-image: radial-gradient(#ffffff 1px, transparent 1px);
  background-size: 12px 12px;
  pointer-events: none;
}

/* Kartu clean & elegan */
.auth-card {
  border: 1px solid rgba(0,0,0,.05);
  border-radius: 1rem;
  overflow: hidden;
  background: #fff;
}
.shadow-soft {
  box-shadow: 0 16px 40px rgba(2,132,199,.18);
}
.brand-title {
  letter-spacing: .3px;
  font-weight: 800;
}
</style>

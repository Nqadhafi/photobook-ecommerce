<!-- resources/js/components/layouts/AuthLayout.vue -->
<template>
  <div class="auth-layout d-flex align-items-center min-vh-100 bg-light">
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="6" lg="5">
          <b-card class="shadow-sm">
            <b-card-header class="text-center bg-white border-0">
              <h3 class="mb-0">
                <b-icon icon="book" variant="primary"></b-icon>
                Photobook Studio
              </h3>
            </b-card-header>
            <b-card-body>
              <slot />
            </b-card-body>
            <b-card-footer class="text-center bg-white border-0">
              <small class="text-muted">
                &copy; {{ new Date().getFullYear() }} Photobook Studio
              </small>
            </b-card-footer>
          </b-card>
        </b-col>
      </b-row>
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
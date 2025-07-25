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
    </b-container>
  </div>
</template>

<script>
export default {
  name: 'AuthLayout'
}
</script>
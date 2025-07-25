<!-- resources/js/views/pages/NotFound.vue -->
<template>
  <app-layout>
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="8">
          <b-card class="text-center">
            <b-card-body>
              <b-icon icon="exclamation-triangle" variant="warning" font-scale="3"></b-icon>
              <h1 class="mt-3">404 - Page Not Found</h1>
              <p class="lead">Oops! The page you're looking for doesn't exist.</p>
              <b-button variant="primary" :to="{ name: 'Dashboard' }" class="mt-3">
                <b-icon icon="house"></b-icon> Back to Home
              </b-button>
            </b-card-body>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>
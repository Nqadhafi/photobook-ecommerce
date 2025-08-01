<!-- resources/js/components/layouts/AppLayout.vue -->
<template>
  <div class="app-layout">
    <navbar-component />
    
    <main class="main-content">
      <b-container fluid class="py-4">
        <loading-spinner v-if="$store.getters.isLoading" />
        <slot />
      </b-container>
    </main>
    
    <footer-component />
    
    <!-- Global Notifications -->
    <div class="notification-container">
      <b-alert 
        v-for="notification in $store.getters.notifications"
        :key="notification.id"
        :variant="notification.type || 'info'"
        :show="3"
        fade
        dismissible
        @dismissed="$store.commit('REMOVE_NOTIFICATION', notification.id)"
        class="position-fixed"
        style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;"
      >
        <strong>{{ notification.title }}</strong>
        <p class="mb-0">{{ notification.message }}</p>
      </b-alert>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppLayout'
}
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}
</style>
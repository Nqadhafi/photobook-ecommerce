<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'App',
  computed: {
    ...mapGetters('auth', ['isAuthenticated'])
  },
  async created() {
    // Auto-fetch user data saat app load
    if (this.$store.getters['auth/isAuthenticated'] === null) {
      try {
        await this.$store.dispatch('auth/fetchUser');
      } catch (error) {
        // User tidak authenticated, biarkan tetap null
        console.log('No authenticated user found');
      }
    }
    
    // Auto-load cart jika user logged in
    if (this.$store.getters['auth/isAuthenticated']) {
      try {
        await this.$store.dispatch('cart/loadCart');
      } catch (error) {
        console.log('Failed to load cart data');
      }
    }
  }
}
</script>

<style>
/* Global styles */
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}
</style>
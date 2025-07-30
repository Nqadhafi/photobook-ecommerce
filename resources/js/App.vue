<template>
  <div id="app">
    <router-view />
  </div>
</template>

<!-- App.vue -->
<script>
export default {
  async created() {
    // ✅ Cek hanya jika belum diambil
    if (!this.$store.state.auth.userFetched) {
      try {
        await this.$store.dispatch('auth/fetchUser');
      } catch (error) {
        // 401 itu normal → user belum login
        console.log('Guest user detected');
      }
    }

    if (this.$store.getters['auth/isAuthenticated']) {
      try {
        await this.$store.dispatch('cart/loadCart');
      } catch (error) {
        console.log('Failed to load cart');
      }
    }
  }
}
</script>

<style>
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}
</style>
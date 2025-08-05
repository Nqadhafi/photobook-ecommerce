<!-- views/admin/AdminLogout.vue -->
<template>
  <div class="text-center py-5">
    <p>Logging out...</p>
  </div>
</template>

<script>
export default {
  async created() {
    await this.$store.dispatch('auth/logout');
    this.$router.replace({ name: 'Login' });
  }
};
</script>

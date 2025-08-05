<!-- resources/js/views/admin/Dashboard.vue -->
<template>
  <div>
    <h1>Admin Dashboard</h1>
    <p>Welcome, {{ currentUser ? currentUser.name : 'Admin' }}!</p>
    <p v-if="isSuperAdmin"><b>You have Super Admin privileges.</b></p>
    <p v-else><b>You have Admin privileges.</b></p>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'AdminDashboard',
  computed: {
    ...mapGetters('auth', ['user']),
    currentUser() {
      return this.user;
    },
    isSuperAdmin() {
      return this.user && this.user.role === 'super_admin';
    }
  }
};
</script>
<template>
  <b-navbar toggleable="lg" type="dark" variant="primary" class="shadow-sm">
    <b-navbar-brand :to="{ name: 'Home' }">
      <b-icon icon="book" class="mr-1"></b-icon>
      Photobook Studio
    </b-navbar-brand>

    <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

    <b-collapse id="nav-collapse" is-nav>
      <b-navbar-nav>
        <b-nav-item :to="{ name: 'Home' }">
          <b-icon icon="house"></b-icon> Home
        </b-nav-item>
        <b-nav-item :to="{ name: 'Products' }">
          <b-icon icon="images"></b-icon> Products
        </b-nav-item>
      </b-navbar-nav>

      <b-navbar-nav class="ml-auto">
        <template v-if="$store.getters['auth/isAuthenticated']">
          <!-- Cart - selalu tampil untuk user logged in -->
          <b-nav-item :to="{ name: 'Cart' }">
            <b-icon icon="cart"></b-icon> Cart
            <b-badge 
              v-if="cartItemCount > 0" 
              variant="light" 
              class="ml-1"
            >
              {{ cartItemCount }}
            </b-badge>
          </b-nav-item>
          
          <b-nav-item :to="{ name: 'Orders' }">
            <b-icon icon="list"></b-icon> Orders
          </b-nav-item>
          
          <b-nav-item-dropdown right>
            <template #button-content>
              <b-icon icon="person-circle"></b-icon>
              {{ userName }}
            </template>
            <b-dropdown-item :to="{ name: 'Profile' }">
              <b-icon icon="gear"></b-icon> Profile
            </b-dropdown-item>
            <b-dropdown-divider></b-dropdown-divider>
            <b-dropdown-item @click="handleLogout">
              <b-icon icon="box-arrow-right"></b-icon> Logout
            </b-dropdown-item>
          </b-nav-item-dropdown>
        </template>
        
        <template v-else>
          <!-- Menu untuk guest users -->
          <b-nav-item :to="{ name: 'Products' }">
            <b-icon icon="images"></b-icon> Browse Products
          </b-nav-item>
          <b-nav-item :to="{ name: 'Login' }">
            <b-icon icon="box-arrow-in-right"></b-icon> Login
          </b-nav-item>
          <b-nav-item :to="{ name: 'Register' }">
            <b-icon icon="person-plus"></b-icon> Register
          </b-nav-item>
        </template>
      </b-navbar-nav>
    </b-collapse>
  </b-navbar>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'Navbar',
  computed: {
    ...mapGetters('auth', ['user', 'isAuthenticated']),
    ...mapGetters('cart', ['cartItemCount']),
    userName() {
      return this.user ? this.user.name : 'Guest';
    }
  },
  async created() {
    // Load cart data jika user logged in
    if (this.$store.getters['auth/isAuthenticated']) {
      try {
        await this.$store.dispatch('cart/loadCart');
      } catch (error) {
        console.log('Failed to load cart data');
      }
    }
  },
  methods: {
    ...mapActions('auth', ['logout']),
    
    async handleLogout() {
      try {
        await this.logout();
        // Redirect ke login setelah logout
        this.$router.push({ name: 'Login' });
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'You have been logged out',
          type: 'success'
        });
      } catch (error) {
        console.error('Logout error:', error);
        // Tetap redirect ke login meski error
        this.$router.push({ name: 'Login' });
      }
    }
  }
};
</script>
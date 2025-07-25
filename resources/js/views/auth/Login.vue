<!-- resources/js/views/auth/Login.vue -->
<template>
  <auth-layout>
    <div class="login-form">
      <h4 class="text-center mb-4">Sign In</h4>
      
      <b-alert v-if="error" variant="danger" show>
        {{ error }}
      </b-alert>

      <b-form @submit.prevent="handleLogin">
        <b-form-group label="Email" label-for="email">
          <b-form-input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="Enter your email"
            :state="!errors.email"
          ></b-form-input>
          <b-form-invalid-feedback v-if="errors.email">
            {{ errors.email[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Password" label-for="password">
          <b-form-input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="Enter your password"
            :state="!errors.password"
          ></b-form-input>
          <b-form-invalid-feedback v-if="errors.password">
            {{ errors.password[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-button
          type="submit"
          variant="primary"
          block
          :disabled="loading"
        >
          <b-spinner small v-if="loading"></b-spinner>
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </b-button>
      </b-form>

      <div class="text-center mt-3">
        <p class="mb-0">
          Don't have an account? 
          <b-link :to="{ name: 'Register' }">Sign up</b-link>
        </p>
      </div>
    </div>
  </auth-layout>
</template>

<script>
import { mapActions } from 'vuex';

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      loading: false,
      error: '',
      errors: {}
    };
  },
  methods: {
    ...mapActions('auth', ['login']),
    
    async handleLogin() {
      this.loading = true;
      this.error = '';
      this.errors = {};

      try {
        await this.login(this.form);
        this.$router.push({ name: 'Dashboard' });
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Welcome back!',
          type: 'success'
        });
      } catch (error) {
        if (error.errors) {
          this.errors = error.errors;
        } else if (error.message) {
          this.error = error.message;
        } else {
          this.error = 'Invalid email or password';
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
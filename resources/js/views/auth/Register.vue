<!-- resources/js/views/auth/Register.vue -->
<template>
  <auth-layout>
    <div class="register-form">
      <h4 class="text-center mb-4">Create Account</h4>
      
      <b-alert v-if="error" variant="danger" show>
        {{ error }}
      </b-alert>

      <b-form @submit.prevent="handleRegister">
        <b-form-group label="Full Name" label-for="name">
          <b-form-input
            id="name"
            v-model="form.name"
            type="text"
            required
            placeholder="Enter your full name"
            :state="!errors.name"
          ></b-form-input>
          <b-form-invalid-feedback v-if="errors.name">
            {{ errors.name[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

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
            placeholder="Create a password"
            :state="!errors.password"
          ></b-form-input>
          <b-form-invalid-feedback v-if="errors.password">
            {{ errors.password[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Confirm Password" label-for="password_confirmation">
          <b-form-input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            placeholder="Confirm your password"
            :state="!errors.password_confirmation"
          ></b-form-input>
          <b-form-invalid-feedback v-if="errors.password_confirmation">
            {{ errors.password_confirmation[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-button
          type="submit"
          variant="primary"
          block
          :disabled="loading"
        >
          <b-spinner small v-if="loading"></b-spinner>
          {{ loading ? 'Creating account...' : 'Create Account' }}
        </b-button>
      </b-form>

      <div class="text-center mt-3">
        <p class="mb-0">
          Already have an account? 
          <b-link :to="{ name: 'Login' }">Sign in</b-link>
        </p>
      </div>
    </div>
  </auth-layout>
</template>

<script>
import { mapActions } from 'vuex';

export default {
  name: 'Register',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      loading: false,
      error: '',
      errors: {}
    };
  },
  methods: {
    ...mapActions('auth', ['register']),
    
    async handleRegister() {
      this.loading = true;
      this.error = '';
      this.errors = {};

      try {
        await this.register(this.form);
        this.$router.push({ name: 'Login' });
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Account created successfully!',
          type: 'success'
        });
      } catch (error) {
        if (error.errors) {
          this.errors = error.errors;
        } else if (error.error) {
          this.error = error.error;
        } else {
          this.error = 'Registration failed. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
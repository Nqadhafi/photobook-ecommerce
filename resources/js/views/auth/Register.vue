<template>
  <auth-layout>
    <div class="register-form">
      <div class="text-center mb-3">
        <h4 class="mb-1">Create Account</h4>
        <p class="text-muted small mb-0">Daftar untuk mulai membuat photobook</p>
      </div>

      <b-alert v-if="error" variant="danger" show class="mb-3">
        {{ error }}
      </b-alert>

      <b-form @submit.prevent="handleRegister" novalidate>
        <!-- Full Name -->
        <b-form-group label="Full Name" label-for="name" label-size="sm">
          <b-form-input
            id="name"
            v-model.trim="form.name"
            type="text"
            required
            placeholder="Nama lengkap"
            :state="nameState"
            @blur="touched.name = true"
            @input="touched.name = true"
          ></b-form-input>
          <b-form-invalid-feedback v-if="nameState === false">
            {{ nameErrorMessage }}
          </b-form-invalid-feedback>
          <b-form-invalid-feedback v-else-if="errors.name">
            {{ errors.name[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Email -->
        <b-form-group label="Email" label-for="email" label-size="sm">
          <b-form-input
            id="email"
            v-model.trim="form.email"
            type="email"
            required
            placeholder="you@example.com"
            :state="emailState"
            @blur="touched.email = true"
            @input="touched.email = true"
          ></b-form-input>
          <b-form-invalid-feedback v-if="emailState === false">
            {{ emailErrorMessage }}
          </b-form-invalid-feedback>
          <b-form-invalid-feedback v-else-if="errors.email">
            {{ errors.email[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Password -->
        <b-form-group label="Password" label-for="password" label-size="sm">
          <b-input-group>
            <b-form-input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="form.password"
              required
              placeholder="Min. 8 karakter"
              :state="passwordState"
              @blur="touched.password = true"
              @input="touched.password = true"
            ></b-form-input>
            <b-input-group-append>
              <b-button variant="outline-secondary" @click="showPassword = !showPassword">
                <b-icon :icon="showPassword ? 'eye-slash' : 'eye'"></b-icon>
              </b-button>
            </b-input-group-append>
          </b-input-group>
          <b-form-invalid-feedback v-if="passwordState === false">
            {{ passwordErrorMessage }}
          </b-form-invalid-feedback>
          <b-form-invalid-feedback v-else-if="errors.password">
            {{ errors.password[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Confirm Password -->
        <b-form-group label="Confirm Password" label-for="password_confirmation" label-size="sm">
          <b-input-group>
            <b-form-input
              id="password_confirmation"
              :type="showConfirm ? 'text' : 'password'"
              v-model="form.password_confirmation"
              required
              placeholder="Ulangi password"
              :state="passwordConfirmState"
              @blur="touched.password_confirmation = true"
              @input="touched.password_confirmation = true"
            ></b-form-input>
            <b-input-group-append>
              <b-button variant="outline-secondary" @click="showConfirm = !showConfirm">
                <b-icon :icon="showConfirm ? 'eye-slash' : 'eye'"></b-icon>
              </b-button>
            </b-input-group-append>
          </b-input-group>
          <b-form-invalid-feedback v-if="passwordConfirmState === false">
            {{ passwordConfirmErrorMessage }}
          </b-form-invalid-feedback>
          <b-form-invalid-feedback v-else-if="errors.password_confirmation">
            {{ errors.password_confirmation[0] }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-button
          type="submit"
          variant="primary"
          block
          :disabled="!canSubmit || loading"
        >
          <b-spinner small v-if="loading" class="mr-1"></b-spinner>
          {{ loading ? 'Creating account...' : 'Create Account' }}
        </b-button>
      </b-form>

      <div class="text-center mt-3">
        <p class="mb-0 small">
          Sudah punya akun?
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
      errors: {}, // server-side errors
      showPassword: false,
      showConfirm: false,
      touched: {
        name: false,
        email: false,
        password: false,
        password_confirmation: false
      }
    };
  },
  computed: {
    // ===== VALIDATION STATES =====
    nameState() {
      if (!this.touched.name) return null;
      if (!this.form.name) return false;
      return this.form.name.length >= 2;
    },
    emailState() {
      if (!this.touched.email) return null;
      if (!this.form.email) return false;
      return this.isValidEmail(this.form.email);
    },
    passwordState() {
      if (!this.touched.password) return null;
      if (!this.form.password) return false;
      return this.form.password.length >= 8;
    },
    passwordConfirmState() {
      if (!this.touched.password_confirmation) return null;
      if (!this.form.password_confirmation) return false;
      return this.form.password_confirmation === this.form.password;
    },

    // ===== ERROR MESSAGES =====
    nameErrorMessage() {
      if (!this.form.name) return 'Nama tidak boleh kosong.';
      return 'Nama minimal 2 karakter.';
    },
    emailErrorMessage() {
      if (!this.form.email) return 'Email tidak boleh kosong.';
      return 'Format email tidak valid.';
    },
    passwordErrorMessage() {
      if (!this.form.password) return 'Password tidak boleh kosong.';
      return 'Password minimal 8 karakter.';
    },
    passwordConfirmErrorMessage() {
      if (!this.form.password_confirmation) return 'Konfirmasi password tidak boleh kosong.';
      return 'Konfirmasi password harus sama.';
    },

    // ===== SUBMIT GUARD =====
    canSubmit() {
      return (
        this.form.name.length >= 2 &&
        this.isValidEmail(this.form.email) &&
        this.form.password.length >= 8 &&
        this.form.password_confirmation === this.form.password
      );
    }
  },
  methods: {
    ...mapActions('auth', ['register']),

    isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
      return re.test(String(email).toLowerCase());
    },

    async handleRegister() {
      // Paksa touched agar feedback muncul bila invalid
      this.touched.name = true;
      this.touched.email = true;
      this.touched.password = true;
      this.touched.password_confirmation = true;

      if (!this.canSubmit) {
        this.error = 'Periksa kembali data yang Anda isi.';
        return;
      }

      this.loading = true;
      this.error = '';
      this.errors = {};

      try {
        await this.register(this.form);

        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Account created successfully!',
          type: 'success'
        });

        this.$router.push({ name: 'Login' });
      } catch (error) {
        if (error && error.errors) {
          this.errors = error.errors;
        } else if (error && (error.error || error.message)) {
          this.error = error.error || error.message;
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

<style scoped>
.register-form .form-control { border-radius: .6rem; }
.input-group > .form-control,
.input-group > .input-group-append > .btn { border-radius: .6rem; }
.btn { border-radius: .6rem; }

.is-invalid,
.was-validated .form-control:invalid { box-shadow: none !important; }
</style>

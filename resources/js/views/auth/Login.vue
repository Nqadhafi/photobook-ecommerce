<template>
  <auth-layout>
    <div class="login-form">
      <b-alert v-if="error" variant="danger" show class="mb-3">
        {{ error }}
      </b-alert>

      <b-form @submit.prevent="handleLogin" novalidate>
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

        <b-button
          type="submit"
          variant="primary"
          block
          :disabled="!canSubmit || loading"
        >
          <b-spinner small v-if="loading" class="mr-1"></b-spinner>
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </b-button>
      </b-form>

      <div class="text-center mt-3">
        <p class="mb-0 small">
          Belum punya akun?
          <b-link :to="{ name: 'Register' }">Daftar</b-link>
        </p>
      </div>
    </div>
  </auth-layout>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'Login',
  data() {
    return {
      form: { email: '', password: '' },
      loading: false,
      error: '',
      errors: {}, // server-side errors
      showPassword: false,
      touched: { email: false, password: false }
    };
  },
  computed: {
    ...mapGetters('auth', ['user']),

    // ===== VALIDATION STATES (benar-benar boolean / null) =====
    emailState() {
      if (!this.touched.email) return null;                 // belum disentuh -> netral
      if (!this.form.email) return false;                    // kosong -> invalid
      return this.isValidEmail(this.form.email);             // valid?
    },
    passwordState() {
      if (!this.touched.password) return null;
      if (!this.form.password) return false;
      return this.form.password.length >= 8;
    },

    // ===== ERROR MESSAGES =====
    emailErrorMessage() {
      if (!this.form.email) return 'Email tidak boleh kosong.';
      return 'Format email tidak valid.';
    },
    passwordErrorMessage() {
      if (!this.form.password) return 'Password tidak boleh kosong.';
      return 'Password minimal 8 karakter.';
    },

    // ===== SUBMIT GUARD =====
    canSubmit() {
      return (
        this.isValidEmail(this.form.email) &&
        this.form.password.length >= 8
      );
    }
  },
  methods: {
    ...mapActions('auth', ['login']),

    isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
      return re.test(String(email).toLowerCase());
    },

    async handleLogin() {
      this.touched.email = true;
      this.touched.password = true;

      if (!this.canSubmit) {
        this.error = 'Periksa kembali email & password Anda.';
        return;
      }

      this.loading = true;
      this.error = '';
      this.errors = {};

      try {
        await this.login(this.form);

        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Welcome back!',
          type: 'success'
        });

        const redirect = this.$route.query.redirect;
        const role = this.user && this.user.role;

        if (redirect) {
          this.$router.push(redirect);
        } else if (role === 'admin' || role === 'super_admin') {
          this.$router.push({ name: 'AdminDashboard' });
        } else {
          this.$router.push({ name: 'Dashboard' });
        }
      } catch (error) {
        if (error && error.errors) {
          this.errors = error.errors;
        } else if (error && error.message) {
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

<style scoped>
.login-form .form-control { border-radius: .6rem; }
.input-group > .form-control,
.input-group > .input-group-append > .btn { border-radius: .6rem; }
.btn { border-radius: .6rem; }

.is-invalid,
.was-validated .form-control:invalid { box-shadow: none !important; }
</style>

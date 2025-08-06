<template>
  <div v-if="user">
    <b-row class="mb-3">
      <b-col>
        <h2>Edit Admin User: {{ user.name }}</h2>
      </b-col>
    </b-row>

    <b-card>
      <b-form @submit.prevent="submitForm">
        <b-form-group label="Name:" label-for="user-name">
          <b-form-input
            id="user-name"
            v-model="form.name"
            required
            placeholder="Enter full name"
            :state="getValidationState('name')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('name')">
            {{ errors.name }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Email:" label-for="user-email">
          <b-form-input
            id="user-email"
            v-model="form.email"
            type="email"
            required
            placeholder="Enter email address"
            :state="getValidationState('email')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('email')">
            {{ errors.email }}
          </b-form-invalid-feedback>
        </b-form-group>

        <hr>

        <h5>Change Password (Leave blank to keep current password)</h5>

        <b-form-group label="New Password:" label-for="user-password">
          <b-form-input
            id="user-password"
            v-model="form.password"
            type="password"
            placeholder="Enter new password"
            :state="getValidationState('password')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('password')">
            {{ errors.password }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Confirm New Password:" label-for="user-password-confirmation">
          <b-form-input
            id="user-password-confirmation"
            v-model="form.password_confirmation"
            type="password"
            placeholder="Confirm new password"
            :state="getValidationState('password_confirmation')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('password_confirmation')">
            {{ errors.password_confirmation }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Updating...' : 'Update Admin User' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminUserIndex' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>

  <div v-else-if="loading">
    <b-spinner variant="primary" label="Loading..."></b-spinner> Loading user details...
  </div>

  <div v-else>
    <b-alert variant="danger" show>
      <h4>Error loading user</h4>
      <p v-if="errorMessage">{{ errorMessage }}</p>
      <p v-else>User not found or an error occurred.</p>
      <b-button variant="secondary" :to="{ name: 'AdminUserIndex' }">Back to List</b-button>
    </b-alert>
  </div>
</template>

<script>
import userService from '../../../services/userService';

export default {
  name: 'AdminUserEdit',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: false,
      user: null,
      errorMessage: '',
      isSubmitting: false,
      form: {
        name: '',
        email: '',
        password: '', // Kosongkan saat edit
        password_confirmation: '', // Kosongkan saat edit
      },
      errors: {},
    };
  },
  created() {
    this.fetchUser();
  },
  methods: {
    async fetchUser() {
      this.loading = true;
      this.errorMessage = '';
      try {
        const userId = this.id;
        const userData = await userService.getAdminUser(userId);
        this.user = userData;

        // Isi form dengan data user
        this.form.name = userData.name;
        this.form.email = userData.email;
        // Jangan isi password & password_confirmation

      } catch (error) {
        console.error('Failed to fetch admin user details:', error);
        this.errorMessage = error.error || 'Failed to load admin user details. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    getValidationState(field) {
      return this.errors[field] === undefined ? null : !this.errors[field];
    },

    async submitForm() {
      this.isSubmitting = true;
      this.errors = {};

      try {
        // Siapkan data untuk dikirim
        // Hanya kirim field yang diisi/diubah.
        // Jika password kosong, backend tidak akan mengubahnya.
        const userData = { ...this.form };

        const response = await userService.updateAdminUser(this.id, userData);

        this.$bvToast.toast(`Admin user '${response.name}' updated successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        // Opsional: Refresh data lokal atau redirect
        // this.user = response;
        this.$router.push({ name: 'AdminUserIndex' });

      } catch (error) {
        console.error('Failed to update admin user:', error);
        const errorMsg = error.error || 'Failed to update admin user. Please try again.';

        if (error.errors) {
          this.errors = error.errors;
          if (errorMsg && Object.keys(error.errors).length === 0) {
             this.$bvToast.toast(errorMsg, {
              title: 'Validation Error',
              variant: 'danger',
              solid: true
            });
          }
        } else {
           this.$bvToast.toast(errorMsg, {
            title: 'Error',
            variant: 'danger',
            solid: true
          });
        }
      } finally {
        this.isSubmitting = false;
      }
    }
  }
};
</script>

<style scoped>
/* Tambahkan style khusus jika diperlukan */
</style>
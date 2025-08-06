<template>
  <div>
    <b-row class="mb-3">
      <b-col>
        <h2>Create New Admin User</h2>
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

        <b-form-group label="Password:" label-for="user-password">
          <b-form-input
            id="user-password"
            v-model="form.password"
            type="password"
            required
            placeholder="Enter password"
            :state="getValidationState('password')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('password')">
            {{ errors.password }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Confirm Password:" label-for="user-password-confirmation">
          <b-form-input
            id="user-password-confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            placeholder="Confirm password"
            :state="getValidationState('password_confirmation')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('password_confirmation')">
            {{ errors.password_confirmation }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Creating...' : 'Create Admin User' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminUserIndex' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>

<script>
import userService from '../../../services/userService';

export default {
  name: 'AdminUserCreate',
  data() {
    return {
      isSubmitting: false,
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
      },
      errors: {},
    };
  },
  methods: {
    getValidationState(field) {
      return this.errors[field] === undefined ? null : !this.errors[field];
    },

    async submitForm() {
      this.isSubmitting = true;
      this.errors = {};

      try {
        // Siapkan data untuk dikirim (tanpa password_confirmation jika tidak diperlukan oleh API)
        // Tapi biasanya Laravel memerlukannya untuk validasi 'confirmed'
        const userData = { ...this.form };

        const response = await userService.createAdminUser(userData);

        this.$bvToast.toast(`Admin user '${response.name}' created successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        this.$router.push({ name: 'AdminUserIndex' });

      } catch (error) {
        console.error('Failed to create admin user:', error);
        const errorMsg = error.error || 'Failed to create admin user. Please try again.';

        if (error.errors) {
          this.errors = error.errors;
          if (errorMsg) {
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
<template>
  <div>
    <b-row class="mb-3">
      <b-col>
        <h2>Create New Deskprint</h2>
      </b-col>
    </b-row>

    <b-card>
      <b-form @submit.prevent="submitForm">
        <b-form-group label="Name:" label-for="deskprint-name">
          <b-form-input
            id="deskprint-name"
            v-model="form.name"
            required
            placeholder="Enter deskprint name"
            :state="getValidationState('name')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('name')">
            {{ errors.name }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Location:" label-for="deskprint-location">
          <b-form-input
            id="deskprint-location"
            v-model="form.location"
            placeholder="Enter location"
          ></b-form-input>
        </b-form-group>

        <b-form-group label="Contact Number:" label-for="deskprint-contact-number">
          <b-form-input
            id="deskprint-contact-number"
            v-model="form.contact_number"
            required
            placeholder="Enter contact number"
            :state="getValidationState('contact_number')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('contact_number')">
            {{ errors.contact_number }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Description:" label-for="deskprint-description">
          <b-form-textarea
            id="deskprint-description"
            v-model="form.description"
            rows="3"
            placeholder="Enter description"
          ></b-form-textarea>
        </b-form-group>

        <b-form-group>
          <b-form-checkbox v-model="form.is_active" switch>
            Active
          </b-form-checkbox>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Creating...' : 'Create Deskprint' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminDeskprints' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>

<script>
import deskprintService from '../../../services/deskprintService';

export default {
  name: 'AdminDeskprintCreate',
  data() {
    return {
      isSubmitting: false,
      form: {
        name: '',
        location: '',
        contact_number: '',
        description: '',
        is_active: true,
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
        const deskprintData = { ...this.form };

        const response = await deskprintService.createAdminDeskprint(deskprintData);

        this.$bvToast.toast(`Deskprint '${response.name}' created successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        this.$router.push({ name: 'AdminDeskprints' });

      } catch (error) {
        console.error('Failed to create deskprint:', error);
        const errorMsg = error.error || 'Failed to create deskprint. Please try again.';

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
<template>
  <app-layout>
    <b-container>
      <b-row>
        <b-col>
          <h1>
            <b-icon icon="person-circle"></b-icon> User Profile
          </h1>
          <p class="text-muted">Manage your account information</p>
        </b-col>
      </b-row>

      <b-row class="mt-4">
        <b-col lg="8">
          <b-card>
            <b-card-title>Profile Information</b-card-title>
            <b-alert v-if="updateError" variant="danger" dismissible @dismissed="updateError = ''">
              {{ updateError }}
            </b-alert>
            <b-alert v-if="updateSuccess" variant="success" dismissible @dismissed="updateSuccess = false">
              Profile updated successfully!
            </b-alert>

            <b-form @submit.prevent="updateProfile">
              <b-row>
                <b-col md="6">
                  <b-form-group label="Full Name *" label-for="profile-name">
                    <b-form-input
                      id="profile-name"
                      v-model="form.name"
                      type="text"
                      required
                      :disabled="updating"
                      placeholder="Enter your full name"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col md="6">
                  <b-form-group label="Email *" label-for="profile-email">
                    <b-form-input
                      id="profile-email"
                      v-model="form.email"
                      type="email"
                      required
                      :disabled="updating"
                      placeholder="Enter your email"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>

              <b-row>
                <b-col md="6">
                  <b-form-group label="Phone Number" label-for="profile-phone">
                    <b-form-input
                      id="profile-phone"
                      v-model="form.photobook_profile.phone_number"
                      type="text"
                      :disabled="updating"
                      placeholder="Enter your phone number"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>

              <b-form-group label="Address" label-for="profile-address">
                <b-form-input
                  id="profile-address"
                  v-model="form.photobook_profile.address"
                  type="text"
                  :disabled="updating"
                  placeholder="Enter your street address"
                ></b-form-input>
              </b-form-group>

              <b-row>
                <b-col md="4">
                  <b-form-group label="City" label-for="profile-city">
                    <b-form-input
                      id="profile-city"
                      v-model="form.photobook_profile.city"
                      type="text"
                      :disabled="updating"
                      placeholder="Enter your city"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col md="4">
                  <b-form-group label="Postal Code" label-for="profile-postal-code">
                    <b-form-input
                      id="profile-postal-code"
                      v-model="form.photobook_profile.postal_code"
                      type="text"
                      :disabled="updating"
                      placeholder="Enter postal code"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col md="4">
                  <b-form-group label="Province" label-for="profile-province">
                    <b-form-input
                      id="profile-province"
                      v-model="form.photobook_profile.province"
                      type="text"
                      :disabled="updating"
                      placeholder="Enter your province"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>

              <b-form-group label="Country" label-for="profile-country">
                <b-form-input
                  id="profile-country"
                  v-model="form.photobook_profile.country"
                  type="text"
                  :disabled="updating"
                  placeholder="Enter your country"
                ></b-form-input>
              </b-form-group>

              <b-button
                type="submit"
                variant="primary"
                :disabled="updating"
              >
                <b-spinner v-if="updating" small></b-spinner>
                {{ updating ? 'Updating...' : 'Update Profile' }}
              </b-button>
            </b-form>
          </b-card>
        </b-col>

        <b-col lg="4">
          <b-card>
            <b-card-title>Account Summary</b-card-title>
            <b-list-group flush>
              <b-list-group-item>
                <strong>Name:</strong> {{ user.name }}
              </b-list-group-item>
              <b-list-group-item>
                <strong>Email:</strong> {{ user.email }}
              </b-list-group-item>
              <b-list-group-item>
                <strong>Member Since:</strong> {{ formatDate(user.created_at) }}
              </b-list-group-item>
              <b-list-group-item v-if="user.photobook_profile">
                <strong>Phone:</strong> {{ user.photobook_profile.phone_number || 'Not provided' }}
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Placeholder for future features like Change Password -->
          <!--
          <b-card class="mt-4">
            <b-card-title>Security</b-card-title>
            <p>Manage your password and account security.</p>
            <b-button variant="outline-secondary" disabled>
              Change Password
            </b-button>
          </b-card>
          -->
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import authService from '../../services/authService';

export default {
  name: 'Profile',
  data() {
    return {
      form: {
        name: '',
        email: '',
        photobook_profile: {
          phone_number: '',
          address: '',
          city: '',
          postal_code: '',
          province: '',
          country: ''
        }
      },
      updating: false,
      updateError: '',
      updateSuccess: false
    };
  },
  computed: {
    ...mapGetters('auth', ['user'])
  },
  created() {
    this.populateForm();
  },
  methods: {
    ...mapActions('auth', ['fetchUser']), // Untuk merefresh data user setelah update

    populateForm() {
      if (this.user) {
        this.form.name = this.user.name || '';
        this.form.email = this.user.email || '';
        // Safely populate photobook_profile data
        if (this.user.photobook_profile) {
          this.form.photobook_profile.phone_number = this.user.photobook_profile.phone_number || '';
          this.form.photobook_profile.address = this.user.photobook_profile.address || '';
          this.form.photobook_profile.city = this.user.photobook_profile.city || '';
          this.form.photobook_profile.postal_code = this.user.photobook_profile.postal_code || '';
          this.form.photobook_profile.province = this.user.photobook_profile.province || '';
          this.form.photobook_profile.country = this.user.photobook_profile.country || '';
        }
      }
    },

    async updateProfile() {
      this.updating = true;
      this.updateError = '';
      this.updateSuccess = false;

      try {
        // Prepare data to send - only send fields that have changed or are present
        // The backend handles 'sometimes' rules
        const profileData = { ...this.form };

        await authService.updateProfile(profileData);
        
        // Fetch updated user data from the server
        await this.$store.dispatch('auth/fetchUser');
        
        this.updateSuccess = true;
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Your profile has been updated successfully.',
          type: 'success'
        });
        
        // Re-populate form with fresh data
        this.populateForm(); 
      } catch (error) {
        console.error('Profile update error:', error);
        // Handle different types of errors from the backend
        if (error.errors) {
            // Validation errors
            let errorMsg = 'Please correct the following errors:\n';
            for (const [field, messages] of Object.entries(error.errors)) {
                errorMsg += `- ${field}: ${messages.join(', ')}\n`;
            }
            this.updateError = errorMsg;
        } else if (error.error) {
            // General error message from backend
            this.updateError = error.error;
        } else {
            // Network or unexpected error
            this.updateError = 'Failed to update profile. Please try again.';
        }
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: this.updateError,
          type: 'danger'
        });
      } finally {
        this.updating = false;
      }
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    }
  },
  // Optional: Watch for user data changes if it can be updated elsewhere
  watch: {
    user: {
      handler() {
        this.populateForm();
      },
      deep: true
    }
  }
};
</script>

<style scoped>
/* Tambahkan styling khusus jika diperlukan */
</style>
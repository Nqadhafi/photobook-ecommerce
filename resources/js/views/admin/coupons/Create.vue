<template>
  <div>
    <b-row class="mb-3">
      <b-col>
        <h2>Create New Coupon</h2>
      </b-col>
    </b-row>

    <b-card>
      <b-form @submit.prevent="submitForm">
        <b-form-group label="Code:" label-for="coupon-code">
          <b-form-input
            id="coupon-code"
            v-model="form.code"
            required
            placeholder="Enter unique coupon code"
            :state="getValidationState('code')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('code')">
            {{ errors.code }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-row>
          <b-col md="6">
            <b-form-group label="Discount Percent (%):" label-for="coupon-discount-percent">
              <b-form-input
                id="coupon-discount-percent"
                v-model.number="form.discount_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
                placeholder="e.g., 10.50"
                :state="getValidationState('discount_percent')"
              ></b-form-input>
              <b-form-invalid-feedback :state="getValidationState('discount_percent')">
                {{ errors.discount_percent }}
              </b-form-invalid-feedback>
            </b-form-group>
          </b-col>
          <!-- Jika menggunakan discount_amount, uncomment bagian ini -->
          <!--
          <b-col md="6">
            <b-form-group label="Discount Amount (Rp):" label-for="coupon-discount-amount">
              <b-form-input
                id="coupon-discount-amount"
                v-model.number="form.discount_amount"
                type="number"
                min="0"
                step="0.01"
                required
                placeholder="e.g., 50000.00"
                :state="getValidationState('discount_amount')"
              ></b-form-input>
              <b-form-invalid-feedback :state="getValidationState('discount_amount')">
                {{ errors.discount_amount }}
              </b-form-invalid-feedback>
            </b-form-group>
          </b-col>
          -->
        </b-row>

        <b-row>
          <b-col md="6">
            <b-form-group label="Max Uses (Total):" label-for="coupon-max-uses">
              <b-form-input
                id="coupon-max-uses"
                v-model.number="form.max_uses"
                type="number"
                min="0"
                placeholder="0 for unlimited"
              ></b-form-input>
              <b-form-text variant="secondary">
                <small>0 or leave blank for unlimited uses.</small>
              </b-form-text>
            </b-form-group>
          </b-col>
          <b-col md="6">
            <b-form-group label="Max Uses Per User:" label-for="coupon-max-uses-per-user">
              <b-form-input
                id="coupon-max-uses-per-user"
                v-model.number="form.max_uses_per_user"
                type="number"
                min="1"
                placeholder="e.g., 1"
              ></b-form-input>
            </b-form-group>
          </b-col>
        </b-row>

        <b-row>
          <b-col md="6">
            <b-form-group label="Starts At:" label-for="coupon-starts-at">
              <b-form-datepicker
                id="coupon-starts-at"
                v-model="form.starts_at"
                :min="minDate"
                placeholder="Select start date"
              ></b-form-datepicker>
              <b-form-text variant="secondary">
                <small>Leave blank if the coupon is active immediately.</small>
              </b-form-text>
            </b-form-group>
          </b-col>
          <b-col md="6">
            <b-form-group label="Expires At:" label-for="coupon-expires-at">
              <b-form-datepicker
                id="coupon-expires-at"
                v-model="form.expires_at"
                :min="form.starts_at || minDate"
                placeholder="Select expiration date"
              ></b-form-datepicker>
              <b-form-text variant="secondary">
                <small>Leave blank if the coupon never expires.</small>
              </b-form-text>
            </b-form-group>
          </b-col>
        </b-row>

        <b-form-group label="Description:" label-for="coupon-description">
          <b-form-textarea
            id="coupon-description"
            v-model="form.description"
            rows="3"
            placeholder="Enter coupon description"
          ></b-form-textarea>
        </b-form-group>

        <b-form-group>
          <b-form-checkbox v-model="form.is_active" switch>
            Active
          </b-form-checkbox>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Creating...' : 'Create Coupon' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminCoupons' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>

<script>
import couponService from '../../../services/couponService';

export default {
  name: 'AdminCouponCreate',
  data() {
    return {
      isSubmitting: false,
      minDate: new Date().toISOString().split('T')[0], // Tanggal hari ini dalam format YYYY-MM-DD
      form: {
        code: '',
        discount_percent: null, // Gunakan ini atau discount_amount
        // discount_amount: null, // Uncomment jika menggunakan ini
        max_uses: null, // 0 atau null untuk unlimited
        max_uses_per_user: null,
        starts_at: '', // Format YYYY-MM-DD
        expires_at: '', // Format YYYY-MM-DD
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
        // Siapkan data untuk dikirim
        // Pastikan field kosong dikirim sebagai null jika diperlukan oleh backend
        const couponData = { ...this.form };
        
        // Jika menggunakan discount_amount, pastikan discount_percent null
        // if (this.form.discount_amount !== null) {
        //   couponData.discount_percent = null;
        // }

        const response = await couponService.createAdminCoupon(couponData);

        this.$bvToast.toast(`Coupon '${response.code}' created successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        this.$router.push({ name: 'AdminCoupons' });

      } catch (error) {
        console.error('Failed to create coupon:', error);
        const errorMsg = error.error || 'Failed to create coupon. Please try again.';

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
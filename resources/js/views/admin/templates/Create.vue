<template>
  <div>
    <b-row class="mb-3">
      <b-col>
        <h2>Create New Template</h2>
      </b-col>
    </b-row>

    <b-card>
      <b-form @submit.prevent="submitForm">
        <b-form-group label="Name:" label-for="template-name">
          <b-form-input
            id="template-name"
            v-model="form.name"
            required
            placeholder="Enter template name"
            :state="getValidationState('name')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('name')">
            {{ errors.name }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Product:" label-for="template-product">
          <b-form-select
            id="template-product"
            v-model="form.product_id"
            :options="productOptions"
            value-field="id"
            text-field="name"
            required
            :state="getValidationState('product_id')"
            :disabled="isProductsLoading"
          >
            <template #first>
              <b-form-select-option :value="null" disabled>
                {{ isProductsLoading ? 'Loading products...' : '-- Please select a product --' }}
              </b-form-select-option>
            </template>
          </b-form-select>
          <b-form-invalid-feedback :state="getValidationState('product_id')">
            {{ errors.product_id }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Sample Image Upload -->
        <b-form-group label="Sample Image:" label-for="template-sample-image">
          <b-form-file
            id="template-sample-image"
            v-model="form.sample_image"
            accept="image/*"
            placeholder="Choose a file or drop it here..."
            drop-placeholder="Drop file here..."
            :state="getValidationState('sample_image')"
          ></b-form-file>
          <b-form-invalid-feedback :state="getValidationState('sample_image')">
            {{ errors.sample_image }}
          </b-form-invalid-feedback>
          <b-form-text variant="secondary">
            <small>Allowed types: jpeg, png, jpg. Max size: 2MB.</small>
          </b-form-text>
          <!-- Preview Sample Image -->
          <div v-if="form.sample_image" class="mt-2">
            <p><strong>Sample Image Preview:</strong></p>
            <b-img
              :src="previewSampleImageUrl"
              thumbnail
              fluid
              alt="Sample image preview"
              style="max-height: 150px;"
            ></b-img>
          </div>
        </b-form-group>

        <hr>

        <h5>Layout Data</h5>

        <b-form-group label="Layout Type:" label-for="layout-type">
          <b-form-input
            id="layout-type"
            v-model="form.layout_data.layout_type"
            required
            placeholder="e.g., Classic, Modern"
            :state="getValidationState('layout_data.layout_type')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('layout_data.layout_type')">
            {{ getNestedError('layout_data.layout_type') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Dimensions:" label-for="layout-dimensions">
          <b-form-input
            id="layout-dimensions"
            v-model="form.layout_data.dimensions"
            required
            placeholder="e.g., 20x20 cm"
            :state="getValidationState('layout_data.dimensions')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('layout_data.dimensions')">
            {{ getNestedError('layout_data.dimensions') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Number of Pages:" label-for="layout-pages">
          <b-form-input
            id="layout-pages"
            v-model.number="form.layout_data.pages"
            type="number"
            min="1"
            required
            placeholder="e.g., 20"
            :state="getValidationState('layout_data.pages')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('layout_data.pages')">
            {{ getNestedError('layout_data.pages') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Photo Slots:" label-for="layout-photo-slots">
          <b-form-input
            id="layout-photo-slots"
            v-model.number="form.layout_data.photo_slots"
            type="number"
            min="0"
            required
            placeholder="e.g., 30"
            :state="getValidationState('layout_data.photo_slots')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('layout_data.photo_slots')">
            {{ getNestedError('layout_data.photo_slots') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group>
          <b-form-checkbox v-model="form.is_active" switch>
            Active
          </b-form-checkbox>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Creating...' : 'Create Template' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminTemplates' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>

<script>
import templateService from '../../../services/templateService';
import productService from '../../../services/productService';

export default {
  name: 'AdminTemplateCreate',
  data() {
    return {
      isSubmitting: false,
      isProductsLoading: false,
      productOptions: [], // Akan diisi dari API
      form: {
        name: '',
        product_id: null,
        sample_image: null, // Field untuk sample image
        layout_data: {
          layout_type: '',
          dimensions: '',
          pages: null,
          photo_slots: null,
        },
        is_active: true,
      },
      errors: {},
    };
  },
  computed: {
    previewSampleImageUrl() {
      if (this.form.sample_image) {
        return URL.createObjectURL(this.form.sample_image);
      }
      return null;
    }
  },
  async created() {
    await this.fetchProducts(); // Ambil daftar produk saat komponen dibuat
  },
  methods: {
    async fetchProducts() {
      this.isProductsLoading = true;
      try {
        // Ambil semua produk aktif untuk dropdown
        const params = { per_page: 100 };
        const response = await productService.getAdminProducts(params);
        this.productOptions = response.data;
      } catch (error) {
        console.error('Failed to fetch products for dropdown:', error);
        this.$bvToast.toast('Failed to load product options. Please try again.', {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.productOptions = [];
      } finally {
        this.isProductsLoading = false;
      }
    },

    getValidationState(field) {
      if (field.includes('.')) {
         return this.errors[field] === undefined ? null : !this.errors[field];
      }
      return this.errors[field] === undefined ? null : !this.errors[field];
    },
    
    getNestedError(fieldPath) {
      const errorArray = this.errors[fieldPath];
      if (errorArray && Array.isArray(errorArray) && errorArray.length > 0) {
          return errorArray[0];
      }
      return '';
    },

    async submitForm() {
      this.isSubmitting = true;
      this.errors = {};

      try {
        // Siapkan FormData untuk mengirim data, termasuk file
        const formData = new FormData();
        formData.append('product_id', this.form.product_id);
        formData.append('name', this.form.name);
        if (this.form.sample_image) {
          formData.append('sample_image', this.form.sample_image);
        }
        // Append layout_data fields individually or as JSON string
        // Cara 1: Append satu per satu (lebih fleksibel untuk validasi backend)
        formData.append('layout_data[layout_type]', this.form.layout_data.layout_type);
        formData.append('layout_data[dimensions]', this.form.layout_data.dimensions);
        formData.append('layout_data[pages]', this.form.layout_data.pages);
        formData.append('layout_data[photo_slots]', this.form.layout_data.photo_slots);
        // Cara 2: Append sebagai JSON string (perlu decoding di backend)
        // formData.append('layout_data', JSON.stringify(this.form.layout_data));
        
        formData.append('is_active', this.form.is_active);

        const response = await templateService.createAdminTemplate(formData);

        this.$bvToast.toast(`Template '${response.name}' created successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        this.$router.push({ name: 'AdminTemplates' });

      } catch (error) {
        console.error('Failed to create template:', error);
        const errorMsg = error.error || error.message || 'Failed to create template. Please try again.';
        const validationErrors = error.errors || {};

        if (Object.keys(validationErrors).length > 0) {
            this.errors = validationErrors;
            if (errorMsg && errorMsg !== 'Failed to create template. Please try again.') {
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
<template>
  <div v-if="template">
    <b-row class="mb-3">
      <b-col>
        <h2>Edit Template: {{ template.name }}</h2>
      </b-col>
    </b-row>
    <b-card>
      <b-form @submit.prevent="submitForm">
        <!-- Name -->
        <b-form-group label="Name:" label-for="template-name">
          <b-form-input
            id="template-name"
            v-model="form.name"
            required
            placeholder="Enter template name"
            :state="getValidationState('name')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('name')">
            {{ getError('name') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Product (read-only) -->
        <b-form-group label="Product:" label-for="template-product">
          <b-form-select
            id="template-product"
            v-model="form.product_id"
            :options="productOptions"
            value-field="id"
            text-field="name"
            required
            :disabled="true"
            :state="getValidationState('product_id')"
          >
            <template #first>
              <b-form-select-option :value="null" disabled>
                {{ isProductsLoading ? 'Loading products...' : '-- Please select a product --' }}
              </b-form-select-option>
            </template>
          </b-form-select>
          <b-form-text variant="secondary">
            <small>Product cannot be changed after creation.</small>
          </b-form-text>
          <b-form-invalid-feedback :state="getValidationState('product_id')">
            {{ getError('product_id') }}
          </b-form-invalid-feedback>
        </b-form-group>

        <!-- Sample Image -->
        <b-form-group label="Sample Image:" label-for="template-sample-image">
          <b-form-file
            id="template-sample-image"
            v-model="form.sample_image"
            accept="image/*"
            placeholder="Choose a new file..."
            :state="getValidationState('sample_image')"
          ></b-form-file>
          <b-form-invalid-feedback :state="getValidationState('sample_image')">
            {{ getError('sample_image') }}
          </b-form-invalid-feedback>
          <b-form-text variant="secondary">
            <small>Allowed types: jpeg, png, jpg. Max size: 2MB. Leave blank to keep current image.</small>
          </b-form-text>
          <!-- Current Image -->
          <div v-if="template.sample_image" class="mt-2">
            <p><strong>Current Sample Image:</strong></p>
            <b-img
              :src="getCurrentSampleImageUrl()"
              thumbnail
              fluid
              :alt="template.name + ' sample image'"
              style="max-height: 150px;"
            ></b-img>
          </div>
          <!-- Preview New Image -->
          <div v-if="form.sample_image" class="mt-2">
            <p><strong>New Sample Image Preview:</strong></p>
            <b-img
              :src="previewNewSampleImageUrl"
              thumbnail
              fluid
              alt="New sample image preview"
              style="max-height: 150px;"
            ></b-img>
          </div>
        </b-form-group>

        <hr>

        <!-- Layout Data -->
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
          {{ isSubmitting ? 'Updating...' : 'Update Template' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminTemplates' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>

  <div v-else-if="loading">
    <b-spinner variant="primary" label="Loading..."></b-spinner> Loading template details...
  </div>
  <div v-else>
    <b-alert variant="danger" show>
      <h4>Error loading template</h4>
      <p v-if="errorMessage">{{ errorMessage }}</p>
      <p v-else>Template not found or an error occurred.</p>
      <b-button variant="secondary" :to="{ name: 'AdminTemplates' }">Back to List</b-button>
    </b-alert>
  </div>
</template>

<script>
import templateService from '../../../services/templateService';
import productService from '../../../services/productService';

export default {
  name: 'AdminTemplateEdit',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: false,
      isSubmitting: false,
      isProductsLoading: false,
      template: null,
      errorMessage: '',
      productOptions: [],
      form: {
        name: '',
        product_id: null,
        sample_image: null,
        layout_data: {
          layout_type: '',
          dimensions: '',
          pages: null,
          photo_slots: null
        },
        is_active: true
      },
      errors: {}
    };
  },
  computed: {
    previewNewSampleImageUrl() {
      return this.form.sample_image ? URL.createObjectURL(this.form.sample_image) : null;
    }
  },
  async created() {
    await this.fetchTemplate();
    await this.fetchProducts();
  },
  methods: {
    async fetchTemplate() {
      this.loading = true;
      this.errorMessage = '';
      try {
        const templateData = await templateService.getAdminTemplate(this.id);
        this.template = templateData;

        this.form.name = templateData.name || '';
        this.form.product_id = templateData.product_id || null;
        this.form.layout_data = {
          layout_type: templateData.layout_data?.layout_type || '',
          dimensions: templateData.layout_data?.dimensions || '',
          pages: templateData.layout_data?.pages ?? null,
          photo_slots: templateData.layout_data?.photo_slots ?? null
        };
        this.form.is_active = templateData.is_active !== undefined ? Boolean(templateData.is_active) : true;

      } catch (error) {
        console.error('Failed to fetch template details:', error);
        this.errorMessage = error.error || 'Failed to load template details.';
      } finally {
        this.loading = false;
      }
    },
    async fetchProducts() {
      this.isProductsLoading = true;
      try {
        const response = await productService.getAdminProducts({ per_page: 100 });
        this.productOptions = response.data;
      } catch (error) {
        console.error('Failed to fetch products:', error);
        this.$bvToast.toast('Failed to load product options.', {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.productOptions = [];
      } finally {
        this.isProductsLoading = false;
      }
    },
    getCurrentSampleImageUrl() {
      return this.template?.sample_image ? `/storage/${this.template.sample_image}` : null;
    },
    getValidationState(field) {
      if (this.errors.hasOwnProperty(field)) {
        return !this.errors[field];
      }
      return null;
    },
    getError(field) {
      const error = this.errors[field];
      if (Array.isArray(error) && error.length > 0) {
        return error[0];
      } else if (typeof error === 'string') {
        return error;
      }
      return '';
    },
    getNestedError(fieldPath) {
      return this.getError(fieldPath);
    },
    async submitForm() {
      this.isSubmitting = true;
      this.errors = {};

      try {
        const formData = new FormData();
        formData.append('_method', 'PUT');

        formData.append('name', this.form.name);
        formData.append('product_id', this.form.product_id);
        formData.append('is_active', this.form.is_active ? '1' : '0');

        if (this.form.sample_image) {
          formData.append('sample_image', this.form.sample_image);
        }

        formData.append('layout_data', JSON.stringify(this.form.layout_data));

        const response = await templateService.updateAdminTemplate(this.id, formData);

        this.$bvToast.toast('Template updated successfully!', {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        this.$router.push({ name: 'AdminTemplates' });

      } catch (error) {
        console.error('Failed to update template:', error);

        if (error.errors) {
          this.errors = error.errors;
        } else {
          const errorMessage = error.error || 'Failed to update template.';
          this.$bvToast.toast(errorMessage, {
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
.ml-2 {
  margin-left: 0.5rem;
}
</style>
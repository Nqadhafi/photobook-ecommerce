<template>
  <div>
    <b-row class="mb-3">
      <b-col>
        <h2>Create New Product</h2>
      </b-col>
    </b-row>

    <b-card>
      <b-form @submit.prevent="submitForm">
        <b-form-group label="Name:" label-for="product-name">
          <b-form-input
            id="product-name"
            v-model="form.name"
            required
            placeholder="Enter product name"
            :state="getValidationState('name')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('name')">
            {{ errors.name }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Description:" label-for="product-description">
          <b-form-textarea
            id="product-description"
            v-model="form.description"
            rows="3"
            placeholder="Enter product description"
          ></b-form-textarea>
        </b-form-group>

        <b-form-group label="Price (Rp):" label-for="product-price">
          <b-form-input
            id="product-price"
            v-model.number="form.price"
            type="number"
            step="0.01"
            min="0"
            required
            placeholder="0.00"
            :state="getValidationState('price')"
          ></b-form-input>
          <b-form-invalid-feedback :state="getValidationState('price')">
            {{ errors.price }}
          </b-form-invalid-feedback>
        </b-form-group>

        <b-form-group label="Thumbnail:" label-for="product-thumbnail">
          <b-form-file
            id="product-thumbnail"
            v-model="form.thumbnail"
            accept="image/*"
            placeholder="Choose a file or drop it here..."
            drop-placeholder="Drop file here..."
            :state="getValidationState('thumbnail')"
          ></b-form-file>
          <b-form-invalid-feedback :state="getValidationState('thumbnail')">
            {{ errors.thumbnail }}
          </b-form-invalid-feedback>
          <b-form-text variant="secondary">
            <small>Allowed types: jpeg, png, jpg. Max size: 2MB.</small>
          </b-form-text>
          <!-- Preview Thumbnail -->
          <div v-if="form.thumbnail" class="mt-2">
            <p><strong>Thumbnail Preview:</strong></p>
            <b-img
              :src="previewThumbnailUrl"
              thumbnail
              fluid
              alt="Thumbnail preview"
              style="max-height: 150px;"
            ></b-img>
          </div>
        </b-form-group>

        <b-form-group>
          <b-form-checkbox v-model="form.is_active" switch>
            Active
          </b-form-checkbox>
        </b-form-group>

        <b-button type="submit" variant="primary" :disabled="isSubmitting">
          <b-spinner small v-if="isSubmitting"></b-spinner>
          {{ isSubmitting ? 'Creating...' : 'Create Product' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminProducts' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>
</template>

<script>
import productService from '../../../services/productService';

export default {
  name: 'AdminProductCreate',
  data() {
    return {
      isSubmitting: false,
      form: {
        name: '',
        description: '',
        price: null,
        thumbnail: null,
        is_active: true,
      },
      errors: {}, // Untuk menyimpan error validasi dari API
    };
  },
  computed: {
    previewThumbnailUrl() {
      if (this.form.thumbnail) {
        return URL.createObjectURL(this.form.thumbnail);
      }
      return null;
    }
  },
  methods: {
    getValidationState(field) {
      // Mengembalikan `null` jika field belum disentuh/touched
      // Mengembalikan `false` jika ada error
      // Mengembalikan `true` jika tidak ada error
      // Kita bisa tingkatkan dengan menandai field sebagai 'touched'
      return this.errors[field] === undefined ? null : !this.errors[field];
    },

    async submitForm() {
      this.isSubmitting = true;
      this.errors = {}; // Reset error sebelum submit

      try {
        // Siapkan FormData untuk mengirim file
        const formData = new FormData();
        formData.append('name', this.form.name);
        formData.append('description', this.form.description);
        formData.append('price', this.form.price);
        if (this.form.thumbnail) {
          formData.append('thumbnail', this.form.thumbnail);
        }
        formData.append('is_active', this.form.is_active);

        const response = await productService.createAdminProduct(formData);

        this.$bvToast.toast(`Product '${response.name}' created successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        // Arahkan kembali ke halaman list produk
        this.$router.push({ name: 'AdminProducts' });

      } catch (error) {
        console.error('Failed to create product:', error);
        const errorMsg = error.error || 'Failed to create product. Please try again.';

        // Tangani error validasi dari Laravel
        if (error.errors) {
          this.errors = error.errors; // { name: ["The name field is required."], ... }
          // Tampilkan pesan error umum juga jika ada
          if (errorMsg) {
             this.$bvToast.toast(errorMsg, {
              title: 'Validation Error',
              variant: 'danger',
              solid: true
            });
          }
        } else {
          // Tampilkan error umum
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
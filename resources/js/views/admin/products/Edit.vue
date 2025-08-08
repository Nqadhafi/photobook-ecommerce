<template>
  <div v-if="product">
    <b-row class="mb-3">
      <b-col>
        <h2>Edit Product: {{ product.name }}</h2>
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
            placeholder="Choose a new file..."
            :state="getValidationState('thumbnail')"
          ></b-form-file>
          <b-form-invalid-feedback :state="getValidationState('thumbnail')">
            {{ errors.thumbnail }}
          </b-form-invalid-feedback>
          <b-form-text variant="secondary">
            <small>Allowed types: jpeg, png, jpg. Max size: 2MB. Leave blank to keep current thumbnail.</small>
          </b-form-text>
          <!-- Thumbnail Saat Ini -->
          <div v-if="product.thumbnail" class="mt-2">
            <p><strong>Current Thumbnail:</strong></p>
            <b-img
              :src="getCurrentThumbnailUrl()"
              thumbnail
              fluid
              :alt="product.name"
              style="max-height: 150px;"
            ></b-img>
          </div>
          <!-- Preview Thumbnail Baru -->
          <div v-if="form.thumbnail" class="mt-2">
            <p><strong>New Thumbnail Preview:</strong></p>
            <b-img
              :src="previewThumbnailUrl"
              thumbnail
              fluid
              alt="New thumbnail preview"
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
          {{ isSubmitting ? 'Updating...' : 'Update Product' }}
        </b-button>
        <b-button variant="secondary" :to="{ name: 'AdminProducts' }" class="ml-2">
          Cancel
        </b-button>
      </b-form>
    </b-card>
  </div>

  <div v-else-if="loading">
    <b-spinner variant="primary" label="Loading..."></b-spinner> Loading product details...
  </div>

  <div v-else>
    <b-alert variant="danger" show>
      <h4>Error loading product</h4>
      <p v-if="errorMessage">{{ errorMessage }}</p>
      <p v-else>Product not found or an error occurred.</p>
      <b-button variant="secondary" :to="{ name: 'AdminProducts' }">Back to List</b-button>
    </b-alert>
  </div>
</template>

<script>
import productService from '../../../services/productService';

export default {
  name: 'AdminProductEdit',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: false,
      product: null,
      errorMessage: '',
      isSubmitting: false,
      form: {
        name: '',
        description: '',
        price: null,
        thumbnail: null, // File baru, jika ada
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
  created() {
    this.fetchProduct();
  },
  methods: {
    async fetchProduct() {
      this.loading = true;
      this.errorMessage = '';
      try {
        const productId = this.id;
        const productData = await productService.getAdminProduct(productId);
        this.product = productData;

        // Isi form dengan data produk
        this.form.name = productData.name;
        this.form.description = productData.description;
        this.form.price = parseFloat(productData.price);
        this.form.is_active = productData.is_active;
        // thumbnail tidak diisi, biarkan null. User pilih file baru jika ingin ganti.

      } catch (error) {
        console.error('Failed to fetch product details:', error);
        this.errorMessage = error.error || 'Failed to load product details. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    getCurrentThumbnailUrl() {
      if (this.product.thumbnail) {
        // Asumsi thumbnail disimpan di storage/app/public dan diakses via /storage
        return `/storage/${this.product.thumbnail}`;
      }
      return null; // Atau URL placeholder
    },

    getValidationState(field) {
      return this.errors[field] === undefined ? null : !this.errors[field];
    },

    async submitForm() {
      this.isSubmitting = true;
      this.errors = {}; // Reset error sebelum submit

      try {
        // Siapkan FormData untuk mengirim data, termasuk file jika ada
        const formData = new FormData();
        formData.append('name', this.form.name);
        formData.append('description', this.form.description);
        formData.append('price', this.form.price);
        // Jika tidak ada file baru, jangan kirim key 'thumbnail'
        // Jika ada file baru, kirim. Jika ingin hapus thumbnail, butuh logika backend khusus.
        if (this.form.thumbnail) {
            formData.append('thumbnail', this.form.thumbnail);
        }
        formData.append('is_active', this.form.is_active ? 1 : 0);
        formData.append('_method', 'PUT'); // Atau gunakan axios.put() jika backend mendukung
        // Laravel perlu tahu ini adalah request update, biasanya dengan method spoofing

        const response = await productService.updateAdminProduct(this.id, formData);

        this.$bvToast.toast(`Product '${response.name}' updated successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });

        // Opsional: Arahkan kembali ke halaman list produk, atau refresh data lokal
        // this.$router.push({ name: 'AdminProducts' });
        // Atau, refresh data produk yang ditampilkan
        this.product = response; // Update data produk lokal dengan response terbaru
        // Reset form.thumbnail agar preview hilang setelah update
        this.form.thumbnail = null;

      } catch (error) {
        console.error('Failed to update product:', error);
        const errorMsg = error.error || 'Failed to update product. Please try again.';

        if (error.errors) {
          this.errors = error.errors;
          if (errorMsg && Object.keys(error.errors).length === 0) {
             // Jika ada pesan error umum tapi tidak ada error field spesifik
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
<template>
  <app-layout>
    <b-container>
      <!-- Loading -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading product details...</p>
        </b-col>
      </b-row>

      <!-- Error -->
      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button variant="primary" :to="{ name: 'Products' }">
              <b-icon icon="arrow-left"></b-icon> Back to Products
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Content -->
      <b-row v-else-if="product">
        <!-- LEFT: Gallery (mobile on top) -->
        <b-col lg="6" class="mb-4">
          <b-card class="gallery-card shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small text-muted">
                <b-icon icon="images"></b-icon>
                <span class="ml-1">Galeri ({{ slides.length }})</span>
              </div>
              <b-button
                v-if="currentSlide !== 0"
                size="sm"
                variant="outline-secondary"
                @click="goToProductHead()"
              >
                <b-icon icon="arrow-counterclockwise"></b-icon> Kembali ke Foto Produk
              </b-button>
            </div>

            <!-- Stage with responsive aspect (1:1 on mobile, max height on desktop) -->
            <div class="gallery-stage">
              <b-carousel
                v-model="currentSlide"
                :interval="0"
                controls
                indicators
                background="#f8f9fa"
                img-width="1024"
                img-height="768"
                class="rounded h-100"
              >
                <b-carousel-slide
                  v-for="(src, idx) in slides"
                  :key="idx"
                >
                  <template #img>
                    <img
                      :src="src"
                      class="d-block mx-auto gallery-image img-fluid"
                      :alt="'Image ' + (idx + 1)"
                      @error="onImageError($event, idx)"
                    />
                  </template>
                </b-carousel-slide>
              </b-carousel>
            </div>

            <!-- Hint index -->
            <div class="text-center small text-muted mt-2">
              {{ currentSlide + 1 }} / {{ slides.length }}
            </div>
          </b-card>
        </b-col>

        <!-- RIGHT: Info & Actions -->
        <b-col lg="6">
          <b-card class="shadow-sm border-0 h-100">
            <div class="mb-2">
              <h4 class="mb-1 text-dark product-title clamp-2">{{ product.name }}</h4>
              <div class="text-muted small">
                {{ product.description }}
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between my-3">
              <div class="h3 text-primary mb-0">Rp {{ formatCurrency(product.price) }}</div>
              <div class="text-right">
                <b-badge v-if="product.is_active" variant="success">In Stock</b-badge>
                <b-badge v-else variant="danger">Out of Stock</b-badge>
                <b-badge v-if="(product.total_sold || 0) > 0" variant="info" class="ml-1">
                  Terjual {{ formatCurrency(product.total_sold) }}
                </b-badge>
              </div>
            </div>

            <!-- Template selector -->
            <b-row class="mb-3" v-if="product.templates && product.templates.length">
              <b-col cols="12">
                <template-selector
                  v-model="selectedTemplateId"
                  :templates="product.templates"
                  label="Pilih Template *"
                  description="Klik untuk melihat preview di slider."
                  :required="true"
                  @selected="onTemplateSelected"
                />
              </b-col>
            </b-row>
            <b-alert
              v-else-if="product.templates && product.templates.length === 0"
              variant="warning"
              show
              class="mb-3"
            >
              No templates available for this product.
            </b-alert>

            <!-- Add to cart form -->
            <b-form @submit.prevent="addToCart">
              <b-row class="mb-3">
                <b-col md="6">
                  <b-form-group label="Quantity">
                    <b-form-input
                      v-model.number="cartForm.quantity"
                      type="number"
                      min="1"
                      max="10"
                      required
                    />
                  </b-form-group>
                </b-col>
                <b-col md="6">
                  <b-form-group label="Design Options">
                    <b-form-checkbox v-model="cartForm.design_same" switch>
                      Same design for all copies
                    </b-form-checkbox>
                  </b-form-group>
                </b-col>
              </b-row>

              <b-button
                type="submit"
                variant="primary"
                size="lg"
                block
                :disabled="!product.is_active || addingToCart || !isTemplateSelected"
              >
                <b-spinner v-if="addingToCart" small></b-spinner>
                <b-icon v-else icon="cart-plus"></b-icon>
                {{ addingToCart ? 'Adding to Cart...' : 'Add to Cart' }}
              </b-button>

              <b-alert
                v-if="showSuccessMessage"
                variant="success"
                dismissible
                class="mt-3"
                @dismissed="showSuccessMessage = false"
              >
                <b-icon icon="check-circle"></b-icon>
                Product added to cart successfully!
                <b-link :to="{ name: 'Cart' }" class="alert-link ml-2">View Cart</b-link>
              </b-alert>
            </b-form>

            <div class="mt-3 d-flex flex-wrap gap-2">
              <b-button variant="outline-secondary" :to="{ name: 'Products' }" class="mr-2">
                <b-icon icon="arrow-left"></b-icon> Back to Products
              </b-button>
              <b-button
                v-if="!$store.getters['auth/isAuthenticated']"
                variant="warning"
                :to="{ name: 'Login' }"
              >
                <b-icon icon="box-arrow-in-right"></b-icon> Login to Order
              </b-button>
            </div>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import productService from '../../services/productService';
import cartService from '../../services/cartService';
import api from '../../services/api';
import { mapActions, mapGetters } from 'vuex';
import TemplateSelector from './TemplateSelector.vue';

export default {
  name: 'ProductDetail',
  components: { TemplateSelector },
  data() {
    return {
      product: null,
      loading: false,
      error: null,
      addingToCart: false,
      showSuccessMessage: false,

      // cart
      cartForm: { quantity: 1, design_same: true },
      selectedTemplateId: null,

      // carousel
      currentSlide: 0,
    };
  },
  computed: {
    ...mapGetters('cart', ['cartItems']),

    // Apakah pemilihan template diwajibkan?
    isTemplateSelected() {
      if (!this.product || !this.product.templates || this.product.templates.length === 0) return true;
      return this.selectedTemplateId !== null;
    },

    // Foto-foto produk (support beberapa nama field; fallback ke thumbnail/placeholder)
    productSlides() {
      if (!this.product) return [];
      const galleries = this.product.images || this.product.photos || this.product.gallery || [];
      const normalized = Array.isArray(galleries) ? galleries.slice() : [];

      // sertakan thumbnail jika belum ada
      const thumb = this.getProductImage(this.product);
      if (thumb && !normalized.some(src => src === thumb)) {
        normalized.unshift(thumb);
      }

      // fallback mutlak
      if (!normalized.length) {
        normalized.push(this.placeholder());
      }
      return normalized;
    },

    // Foto template berurutan sesuai daftar templates
    templateSlides() {
      if (!this.product || !Array.isArray(this.product.templates)) return [];
      return this.product.templates.map(t => this.getTemplateImage(t));
    },

    // Gabungan: produk → template
    slides() {
      return [...this.productSlides, ...this.templateSlides];
    },

    // Map: templateId -> index di slides
    templateIndexMap() {
      const map = {};
      const offset = this.productSlides.length; // posisi mulai untuk template
      if (this.product && Array.isArray(this.product.templates)) {
        this.product.templates.forEach((t, i) => {
          map[t.id] = offset + i;
        });
      }
      return map;
    }
  },
  async created() {
    await this.loadProduct();
  },
  watch: {
    // Kalau ganti produk via route
    '$route.params.id': {
      async handler() {
        await this.loadProduct();
      }
    }
  },
  methods: {
    ...mapActions('cart', ['loadCart', 'addItem']),

    async loadProduct() {
      this.loading = true;
      this.error = null;
      try {
        const productId = this.$route.params.id;
        const response = await productService.getProduct(productId);
        this.product = response.data;

        // Default pilih template pertama (kalau ada), tapi tetap tampilkan slide produk dulu
        if (this.product.templates && this.product.templates.length > 0 && this.selectedTemplateId === null) {
          this.selectedTemplateId = this.product.templates[0].id;
        }

        // Reset carousel ke awal setiap ganti produk
        this.currentSlide = 0;
      } catch (error) {
        console.error('Failed to load product:', error);
        this.error = error.message || 'Failed to load product details';
      } finally {
        this.loading = false;
      }
    },

    // Klik template: lompat ke indeks slide template
    onTemplateSelected(template) {
      if (!template) return;
      const idx = this.templateIndexMap[template.id];
      if (typeof idx === 'number') {
        this.currentSlide = idx;
      }
    },

    goToProductHead() {
      this.currentSlide = 0;
    },

    async addToCart() {
      if (!this.$store.getters['auth/isAuthenticated']) {
        this.$router.push({ name: 'Login' });
        return;
      }
      if (!this.isTemplateSelected) {
        this.$store.dispatch('showNotification', {
          title: 'Error', message: 'Please select a template.', type: 'warning'
        });
        return;
      }
      this.addingToCart = true;
      this.showSuccessMessage = false;
      try {
        const cartItem = {
          product_id: this.product.id,
          template_id: this.selectedTemplateId,
          quantity: this.cartForm.quantity,
          design_same: this.cartForm.design_same
        };
        const existingItem = this.findExistingCartItem(cartItem);
        if (existingItem) {
          const newQuantity = existingItem.quantity + cartItem.quantity;
          await this.updateExistingItem(existingItem.id, newQuantity);
        } else {
          const response = await cartService.addToCart(cartItem);
          await this.addItem(response.cart_item);
        }
        this.showSuccessMessage = true;
        setTimeout(() => { this.showSuccessMessage = false; }, 5000);
        await this.$store.dispatch('cart/loadCart');
        this.$store.dispatch('showNotification', {
          title: 'Success', message: 'Product added to cart!', type: 'success'
        });
      } catch (error) {
        console.error('Failed to add to cart:', error);
        this.$store.dispatch('showNotification', {
          title: 'Error', message: error.message || 'Failed to add product to cart', type: 'danger'
        });
      } finally {
        this.addingToCart = false;
      }
    },

    findExistingCartItem(newItem) {
      return this.cartItems.find(item =>
        item.product_id === newItem.product_id &&
        item.template_id === newItem.template_id &&
        item.design_same === newItem.design_same
      );
    },

    async updateExistingItem(cartItemId, newQuantity) {
      try {
        const response = await api.put(`/cart/${cartItemId}`, { quantity: newQuantity });
        await this.$store.dispatch('cart/loadCart');
        return response.data;
      } catch (error) {
        console.error('Failed to update cart item:', error);
        throw error;
      }
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID').format(amount || 0);
    },

    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        return '/storage/' + product.thumbnail;
      }
      return this.placeholder();
    },

    getTemplateImage(template) {
      if (template && template.sample_image) {
        if (template.sample_image.startsWith('http')) return template.sample_image;
        return '/storage/' + template.sample_image;
      }
      return this.placeholder();
    },

    placeholder() {
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },

    onImageError(event) {
      event.target.src = this.placeholder();
    }
  }
};
</script>

<style scoped>
.gallery-card { border-radius: .9rem; }

.gallery-stage {
  position: relative;
  width: 100%;
  padding-top: 100%;       /* square di mobile */
  overflow: hidden;
  border-radius: .5rem;
}

/* Biarkan item tidak absolute; cukup full height */
.gallery-stage .carousel,
.gallery-stage .carousel-inner {
  position: absolute;
  inset: 0;
  height: 100%;
}
.gallery-stage .carousel-item {
  height: 100%;
}

.gallery-image {
  display: block;
  width: 100%;
  height: 100% !important; /* override img-fluid */
  object-fit: contain;      /* aman: tidak melar/overlap */
  object-position: center;
  background: #f8fafc;
}

@media (min-width: 992px) {
  .gallery-stage { padding-top: 0; height: 420px; }
}

/* yang lain tetap */
.clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.badge + .badge { margin-left: .25rem; }
.carousel-control-prev-icon, .carousel-control-next-icon { background-color: rgba(0,0,0,0.5); border-radius: 50%; }
</style>


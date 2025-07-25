<template>
  <app-layout>
    <b-container>
      <!-- Loading State -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading product details...</p>
        </b-col>
      </b-row>

      <!-- Error State -->
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

      <!-- Product Detail -->
      <b-row v-else-if="product">
        <!-- Product Images -->
        <b-col lg="6" class="mb-4">
          <b-card>
            <b-carousel
              id="product-carousel"
              :interval="0"
              controls
              indicators
              background="#f8f9fa"
            >
              <b-carousel-slide v-if="product.thumbnail">
                <template #img>
                  <b-img
                    :src="getProductImage(product)"
                    fluid
                    alt="Product Image"
                    class="d-block mx-auto"
                    style="max-height: 400px; object-fit: contain;"
                    @error="onMainImageError"
                  ></b-img>
                </template>
              </b-carousel-slide>
              
              <b-carousel-slide v-if="sampleTemplates.length > 0">
                <template #img>
                  <div class="text-center">
                    <h5>Sample Templates</h5>
                    <b-img
                      :src="getTemplateImage(sampleTemplates[0])"
                      fluid
                      alt="Template Sample"
                      class="d-block mx-auto"
                      style="max-height: 300px; object-fit: contain;"
                      @error="onTemplateImageError"
                    ></b-img>
                    <p class="mt-2 text-muted">Template Preview</p>
                  </div>
                </template>
              </b-carousel-slide>
              
              <b-carousel-slide v-if="sampleTemplates.length > 1">
                <template #img>
                  <div class="text-center">
                    <h5>More Templates Available</h5>
                    <b-img
                      :src="getTemplateImage(sampleTemplates[1])"
                      fluid
                      alt="Template Sample"
                      class="d-block mx-auto"
                      style="max-height: 300px; object-fit: contain;"
                      @error="onTemplateImageError"
                    ></b-img>
                    <p class="mt-2 text-muted">Choose from {{ product.templates.length }} templates</p>
                  </div>
                </template>
              </b-carousel-slide>
            </b-carousel>
          </b-card>
        </b-col>

        <!-- Product Info -->
        <b-col lg="6">
          <b-card class="h-100">
            <b-card-title class="mb-3">{{ product.name }}</b-card-title>
            
            <b-card-text class="text-muted mb-4">
              {{ product.description }}
            </b-card-text>

            <b-row class="mb-4">
              <b-col md="6">
                <h4 class="text-primary">Rp {{ formatCurrency(product.price) }}</h4>
              </b-col>
              <b-col md="6" class="text-md-right">
                <b-badge v-if="product.is_active" variant="success">In Stock</b-badge>
                <b-badge v-else variant="danger">Out of Stock</b-badge>
              </b-col>
            </b-row>

            <!-- Template Info -->
            <b-row class="mb-4" v-if="product.templates && product.templates.length > 0">
              <b-col cols="12">
                <h6><b-icon icon="grid"></b-icon> Available Templates</h6>
                <p class="text-muted">
                  Choose from {{ product.templates.length }} beautiful templates designed for this product.
                </p>
                <div class="d-flex flex-wrap">
                  <b-badge 
                    v-for="template in sampleTemplates" 
                    :key="template.id" 
                    variant="info" 
                    class="mr-2 mb-2"
                  >
                    {{ template.name }}
                  </b-badge>
                  <b-badge v-if="product.templates.length > sampleTemplates.length" variant="secondary" class="mr-2 mb-2">
                    +{{ product.templates.length - sampleTemplates.length }} more
                  </b-badge>
                </div>
              </b-col>
            </b-row>

            <!-- Add to Cart Form -->
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
                    ></b-form-input>
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
                :disabled="!product.is_active || addingToCart"
              >
                <b-spinner v-if="addingToCart" small></b-spinner>
                <b-icon v-else icon="cart-plus"></b-icon>
                {{ addingToCart ? 'Adding to Cart...' : 'Add to Cart' }}
              </b-button>
            </b-form>

            <!-- Action Buttons -->
            <div class="mt-3">
              <b-button 
                variant="outline-secondary" 
                :to="{ name: 'Products' }"
                class="mr-2"
              >
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

        <!-- Template Gallery -->
        <b-col cols="12" class="mt-4" v-if="product.templates && product.templates.length > 0">
          <b-card>
            <b-card-title>
              <b-icon icon="images"></b-icon> Template Gallery
            </b-card-title>
            
            <b-row>
              <b-col 
                v-for="template in product.templates.slice(0, 6)" 
                :key="template.id" 
                md="4" 
                sm="6" 
                class="mb-3"
              >
                <b-card no-body class="h-100">
                  <b-card-img 
                    :src="getTemplateImage(template)" 
                    :alt="template.name"
                    class="template-thumbnail"
                    @error="onTemplateImageError"
                  ></b-card-img>
                  <b-card-body>
                    <b-card-title class="h6 mb-0">{{ template.name }}</b-card-title>
                  </b-card-body>
                </b-card>
              </b-col>
              
              <b-col v-if="product.templates.length > 6" cols="12" class="text-center">
                <p class="text-muted">
                  And {{ product.templates.length - 6 }} more templates available after purchase
                </p>
              </b-col>
            </b-row>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import productService from '../../services/productService';
import cartService from '../../services/cartService';
import { mapActions } from 'vuex';

export default {
  name: 'ProductDetail',
  data() {
    return {
      product: null,
      loading: false,
      error: null,
      addingToCart: false,
      cartForm: {
        quantity: 1,
        design_same: true
      }
    };
  },
  computed: {
    sampleTemplates() {
      return this.product?.templates?.slice(0, 3) || [];
    }
  },
  async created() {
    await this.loadProduct();
  },
  methods: {
    ...mapActions('cart', ['addItem']),
    
    async loadProduct() {
      this.loading = true;
      this.error = null;
      
      try {
        const productId = this.$route.params.id;
        const response = await productService.getProduct(productId);
        this.product = response.data;
      } catch (error) {
        console.error('Failed to load product:', error);
        this.error = error.message || 'Failed to load product details';
      } finally {
        this.loading = false;
      }
    },
    
    async addToCart() {
      if (!this.$store.getters['auth/isAuthenticated']) {
        this.$router.push({ name: 'Login' });
        return;
      }
      
      this.addingToCart = true;
      
      try {
        const cartItem = {
          product_id: this.product.id,
          template_id: this.product.templates[0]?.id || null, // Default to first template
          quantity: this.cartForm.quantity,
          design_same: this.cartForm.design_same
        };
        
        await cartService.addToCart(cartItem);
        
        // Update Vuex store
        await this.addItem(cartItem);
        
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Product added to cart!',
          type: 'success'
        });
        
        // Redirect to cart
        this.$router.push({ name: 'Cart' });
        
      } catch (error) {
        console.error('Failed to add to cart:', error);
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: error.message || 'Failed to add product to cart',
          type: 'danger'
        });
      } finally {
        this.addingToCart = false;
      }
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID').format(amount);
    },
    
    getProductImage(product) {
      if (product.thumbnail) {
        if (product.thumbnail.startsWith('http')) {
          return product.thumbnail;
        }
        return product.thumbnail;
      }
      return 'https://via.placeholder.com/500x400/3490dc/ffffff?text=Product+Image';
    },
    
    getTemplateImage(template) {
      if (template.sample_image) {
        if (template.sample_image.startsWith('http')) {
          return template.sample_image;
        }
        return template.sample_image;
      }
      return 'https://via.placeholder.com/300x200/6cb2eb/ffffff?text=Template';
    },
    
    onMainImageError(event) {
      event.target.src = 'https://via.placeholder.com/500x400/cccccc/ffffff?text=Product+Image+Not+Found';
    },
    
    onTemplateImageError(event) {
      event.target.src = 'https://via.placeholder.com/300x200/cccccc/ffffff?text=Template+Not+Found';
    }
  }
};
</script>

<style scoped>
.template-thumbnail {
  height: 150px;
  object-fit: cover;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
  background-color: rgba(0,0,0,0.5);
  border-radius: 50%;
}
</style>
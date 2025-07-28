<!-- resources/js/views/products/ProductDetail.vue -->
<template>
  <app-layout>
    <b-container>
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading product details...</p>
        </b-col>
      </b-row>
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
      <b-row v-else-if="product">
        <b-col lg="6" class="mb-4">
          <b-card>
            <b-carousel id="product-carousel" :interval="0" controls indicators background="#f8f9fa">
              <b-carousel-slide v-if="product.thumbnail">
                <template #img>
                  <b-img :src="getProductImage(product)" fluid alt="Product Image"
                    class="d-block mx-auto" style="max-height: 400px; object-fit: contain;"
                    @error="onMainImageError"></b-img>
                </template>
              </b-carousel-slide>
            </b-carousel>
          </b-card>
        </b-col>
        <b-col lg="6">
          <b-card class="h-100">
            <b-card-title class="mb-3">{{ product.name }}</b-card-title>
            <b-card-text class="text-muted mb-4"> {{ product.description }} </b-card-text>
            <b-row class="mb-4">
              <b-col md="6">
                <h4 class="text-primary">Rp {{ formatCurrency(product.price) }}</h4>
              </b-col>
              <b-col md="6" class="text-md-right">
                <b-badge v-if="product.is_active" variant="success">In Stock</b-badge>
                <b-badge v-else variant="danger">Out of Stock</b-badge>
              </b-col>
            </b-row>

            <b-row class="mb-4" v-if="product.templates && product.templates.length > 0">
              <b-col cols="12">
                <template-selector
                  v-model="selectedTemplateId"
                  :templates="product.templates"
                  label="Choose Your Template *"
                  description="Select a template for your photobook design."
                  :required="true"
                ></template-selector>
              </b-col>
            </b-row>
            <b-alert v-else-if="product.templates && product.templates.length === 0" variant="warning" show>
              No templates available for this product.
            </b-alert>

            <b-form @submit.prevent="addToCart">
              <b-row class="mb-3">
                <b-col md="6">
                  <b-form-group label="Quantity">
                    <b-form-input v-model.number="cartForm.quantity" type="number" min="1" max="10" required></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col md="6">
                  <b-form-group label="Design Options">
                    <b-form-checkbox v-model="cartForm.design_same" switch> Same design for all copies </b-form-checkbox>
                  </b-form-group>
                </b-col>
              </b-row>
              <b-button type="submit" variant="primary" size="lg" block
                :disabled="!product.is_active || addingToCart || !isTemplateSelected">
                <b-spinner v-if="addingToCart" small></b-spinner>
                <b-icon v-else icon="cart-plus"></b-icon>
                {{ addingToCart ? 'Adding to Cart...' : 'Add to Cart' }}
              </b-button>
              <b-alert v-if="showSuccessMessage" variant="success" dismissible class="mt-3"
                @dismissed="showSuccessMessage = false">
                <b-icon icon="check-circle"></b-icon>
                Product added to cart successfully!
                <b-link :to="{ name: 'Cart' }" class="alert-link ml-2"> View Cart </b-link>
              </b-alert>
            </b-form>
            <div class="mt-3">
              <b-button variant="outline-secondary" :to="{ name: 'Products' }" class="mr-2">
                <b-icon icon="arrow-left"></b-icon> Back to Products
              </b-button>
              <b-button v-if="!$store.getters['auth/isAuthenticated']" variant="warning" :to="{ name: 'Login' }">
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
      product: null, loading: false, error: null, addingToCart: false,
      showSuccessMessage: false,
      cartForm: { quantity: 1, design_same: true },
      selectedTemplateId: null
    };
  },
  computed: {
    ...mapGetters('cart', ['cartItems']),
    isTemplateSelected() {
       if (!this.product || !this.product.templates || this.product.templates.length === 0) return true;
       return this.selectedTemplateId !== null;
    }
  },
  async created() { await this.loadProduct(); },
  methods: {
    ...mapActions('cart', ['loadCart', 'addItem']),
    async loadProduct() {
      this.loading = true; this.error = null;
      try {
        const productId = this.$route.params.id;
        const response = await productService.getProduct(productId);
        this.product = response.data;
        if (this.product.templates && this.product.templates.length > 0 && this.selectedTemplateId === null) {
            this.selectedTemplateId = this.product.templates[0].id;
        }
      } catch (error) {
        console.error('Failed to load product:', error);
        this.error = error.message || 'Failed to load product details';
      } finally { this.loading = false; }
    },
    async addToCart() {
      if (!this.$store.getters['auth/isAuthenticated']) {
        this.$router.push({ name: 'Login' }); return;
      }
      if (!this.isTemplateSelected) {
          this.$store.dispatch('showNotification', {
              title: 'Error', message: 'Please select a template.', type: 'warning'
          });
          return;
      }
      this.addingToCart = true; this.showSuccessMessage = false;
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
      } finally { this.addingToCart = false; }
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
      } catch (error) { console.error('Failed to update cart item:', error); throw error; }
    },
    formatCurrency(amount) { return new Intl.NumberFormat('id-ID').format(amount); },
    getProductImage(product) {
      if (product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        return product.thumbnail;
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    onMainImageError(event) {
      event.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    }
  }
};
</script>

<style scoped>
.carousel-control-prev-icon,
.carousel-control-next-icon {
  background-color: rgba(0,0,0,0.5); border-radius: 50%;
}
</style>
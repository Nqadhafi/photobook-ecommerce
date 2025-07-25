<template>
  <app-layout>
    <b-container>
      <!-- Page Header -->
      <b-row class="mb-4">
        <b-col>
          <h1>
            <b-icon icon="cart"></b-icon> Shopping Cart
          </h1>
          <p class="text-muted">Review your items before checkout</p>
        </b-col>
      </b-row>

      <!-- Loading State -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading your cart...</p>
        </b-col>
      </b-row>

      <!-- Empty Cart -->
      <b-row v-else-if="isEmpty">
        <b-col cols="12">
          <b-card class="text-center py-5">
            <b-icon icon="cart-x" font-scale="3" variant="secondary"></b-icon>
            <h3 class="mt-3">Your cart is empty</h3>
            <p class="text-muted">Add some amazing photobooks to get started!</p>
            <b-button variant="primary" :to="{ name: 'Products' }" size="lg">
              <b-icon icon="images"></b-icon> Browse Products
            </b-button>
          </b-card>
        </b-col>
      </b-row>

      <!-- Cart Items -->
      <b-row v-else>
        <b-col lg="8">
          <!-- Cart Items List -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="list"></b-icon> Cart Items ({{ cartItemCount }} items)
            </b-card-title>
            
            <b-list-group flush>
              <b-list-group-item 
                v-for="item in cartItems" 
                :key="item.id" 
                class="py-4"
              >
                <b-row class="align-items-center">
                  <!-- Product Image -->
                  <b-col md="2" class="text-center mb-3 mb-md-0">
                    <b-img
                      :src="getProductImage(item.product)"
                      :alt="item.product.name"
                      rounded
                      fluid
                      class="product-thumbnail"
                      @error="onImageError"
                    ></b-img>
                  </b-col>

                  <!-- Product Info -->
                  <b-col md="5">
                    <h5 class="mb-1">{{ item.product.name }}</h5>
                    <p class="text-muted small mb-2">
                      Template: {{ item.template ? item.template.name : 'Default' }}
                    </p>
                    <p class="text-muted small mb-0">
                      {{ item.quantity }} {{ item.quantity > 1 ? 'copies' : 'copy' }}
                      <span v-if="item.design_same">(Same design)</span>
                      <span v-else>(Different designs)</span>
                    </p>
                  </b-col>

                  <!-- Price & Quantity -->
                  <b-col md="3" class="text-md-center">
                    <h5 class="text-primary mb-2">
                      Rp {{ formatCurrency(item.product.price) }}
                    </h5>
                    <div class="d-flex align-items-center justify-content-md-center">
                      <span class="mr-2">Qty:</span>
                      <b-form-input
                        :value="item.quantity"
                        type="number"
                        min="1"
                        max="10"
                        size="sm"
                        style="width: 70px;"
                        disabled
                      ></b-form-input>
                    </div>
                  </b-col>

                  <!-- Actions -->
                  <b-col md="2" class="text-md-right">
                    <h5 class="mb-2">Rp {{ formatCurrency(getItemTotal(item)) }}</h5>
                    <b-button
                      variant="outline-danger"
                      size="sm"
                      @click="removeItem(item.id)"
                      :disabled="removingItemId === item.id"
                    >
                      <template v-if="removingItemId === item.id">
                        <b-spinner small></b-spinner>
                      </template>
                      <template v-else>
                        <b-icon icon="trash"></b-icon> Remove
                      </template>
                    </b-button>
                  </b-col>
                </b-row>
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Continue Shopping -->
          <b-button variant="outline-primary" :to="{ name: 'Products' }">
            <b-icon icon="arrow-left"></b-icon> Continue Shopping
          </b-button>
        </b-col>

        <!-- Order Summary -->
        <b-col lg="4">
          <b-card class="sticky-top" style="top: 20px;">
            <b-card-title>
              <b-icon icon="receipt"></b-icon> Order Summary
            </b-card-title>

            <b-list-group flush>
              <b-list-group-item class="d-flex justify-content-between">
                <span>Subtotal</span>
                <strong>Rp {{ formatCurrency(cartTotal) }}</strong>
              </b-list-group-item>
              <b-list-group-item class="d-flex justify-content-between">
                <span>Shipping</span>
                <strong>FREE</strong>
              </b-list-group-item>
              <b-list-group-item class="d-flex justify-content-between">
                <span>Tax</span>
                <strong>Included</strong>
              </b-list-group-item>
              <b-list-group-item class="d-flex justify-content-between bg-light">
                <h5 class="mb-0">Total</h5>
                <h5 class="mb-0 text-primary">Rp {{ formatCurrency(cartTotal) }}</h5>
              </b-list-group-item>
            </b-list-group>

            <b-button
              variant="success"
              size="lg"
              block
              class="mt-4"
              :to="{ name: 'Checkout' }"
              :disabled="isEmpty"
            >
              <b-icon icon="credit-card"></b-icon> Proceed to Checkout
            </b-button>

            <b-button
              variant="outline-secondary"
              block
              class="mt-2"
              :to="{ name: 'Products' }"
            >
              <b-icon icon="images"></b-icon> Add More Items
            </b-button>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import cartService from '../../services/cartService';

export default {
  name: 'Cart',
  data() {
    return {
      loading: false,
      removingItemId: null
    };
  },
  computed: {
    ...mapGetters('cart', ['cartItems', 'cartItemCount', 'cartTotal', 'isEmpty'])
  },
  async created() {
    await this.loadCartData();
  },
  methods: {
    ...mapActions('cart', ['loadCart', 'removeItem']),
    
    async loadCartData() {
      this.loading = true;
      try {
        await this.$store.dispatch('cart/loadCart');
      } catch (error) {
        console.error('Failed to load cart:', error);
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: 'Failed to load cart items',
          type: 'danger'
        });
      } finally {
        this.loading = false;
      }
    },
    
    async removeItem(itemId) {
      this.removingItemId = itemId;
      try {
        await this.$store.dispatch('cart/removeItem', itemId);
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Item removed from cart',
          type: 'success'
        });
      } catch (error) {
        console.error('Failed to remove item:', error);
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: error.message || 'Failed to remove item',
          type: 'danger'
        });
      } finally {
        this.removingItemId = null;
      }
    },
    
    getItemTotal(item) {
      return item.quantity * item.product.price;
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
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    
    onImageError(event) {
      event.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    }
  }
};
</script>

<style scoped>
.product-thumbnail {
  max-height: 100px;
  object-fit: cover;
}

.sticky-top {
  z-index: 1000;
}

@media (max-width: 768px) {
  .text-md-right {
    text-align: right;
  }
  
  .text-md-center {
    text-align: center;
  }
}
</style>
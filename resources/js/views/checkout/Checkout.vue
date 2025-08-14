<template>
  <app-layout>
    <b-container>
      <!-- Page Header -->
      <b-row class="mb-4">
        <b-col>
          <h1>
            <b-icon icon="credit-card"></b-icon> Checkout
          </h1>
          <p class="text-muted">Complete your order</p>
        </b-col>
      </b-row>

      <!-- Loading State -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Preparing your checkout...</p>
        </b-col>
      </b-row>

      <!-- Empty Cart Check -->
      <b-row v-else-if="isEmpty">
        <b-col cols="12">
          <b-alert variant="warning" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Empty Cart</h4>
            <p>You don't have any items in your cart.</p>
            <b-button variant="primary" :to="{ name: 'Products' }">
              <b-icon icon="images"></b-icon> Browse Products
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Checkout Form -->
      <b-row v-else>
        <b-col lg="8">
          <!-- Order Summary -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="list"></b-icon> Order Summary
            </b-card-title>
            
            <b-list-group flush>
              <b-list-group-item 
                v-for="item in cartItems" 
                :key="item.id"
                class="py-3"
              >
                <b-row>
                  <b-col md="2" class="text-center mb-2 mb-md-0">
                    <b-img
                      :src="getProductImage(item.product)"
                      :alt="item.product.name"
                      rounded
                      fluid
                      class="product-thumbnail"
                      @error="onImageError"
                    ></b-img>
                  </b-col>
                  <b-col md="6">
                    <h6 class="mb-1">{{ item.product.name }}</h6>
                    <p class="text-muted small mb-0">
                      Qty: {{ item.quantity }} × Rp {{ formatCurrency(item.product.price) }}
                    </p>
                  </b-col>
                  <b-col md="4" class="text-md-right">
                    <strong>Rp {{ formatCurrency(getItemTotal(item)) }}</strong>
                  </b-col>
                </b-row>
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Coupon Section -->
          <b-card class="mb-4">
            <b-card-title><b-icon icon="tag"></b-icon> Coupon Code</b-card-title>
            <b-form @submit.prevent="applyCoupon">
              <b-row>
                <b-col md="8">
                  <b-form-group
                    label="Enter your coupon code"
                    label-for="coupon-code"
                    :state="couponError ? false : null"
                    :invalid-feedback="couponError"
                  >
                    <b-form-input
                      id="coupon-code"
                      v-model="couponCode"
                      type="text"
                      placeholder="Enter coupon code"
                      :disabled="couponLoading || !!appliedCoupon"
                      required
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col md="4">
                  <b-button
                    type="submit"
                    variant="outline-primary"
                    :disabled="couponLoading || !couponCode.trim() || !!appliedCoupon"
                    block
                  >
                    <template v-if="couponLoading">
                      <b-spinner small></b-spinner> Applying...
                    </template>
                    <template v-else-if="appliedCoupon">
                      Applied
                    </template>
                    <template v-else>
                      Apply Coupon
                    </template>
                  </b-button>
                </b-col>
              </b-row>
            </b-form>

            <b-alert v-if="appliedCoupon" variant="success" show class="mt-3 mb-0">
              <b-icon icon="check-circle"></b-icon>
              Coupon <strong>{{ appliedCoupon.code }}</strong> applied!
              You save <strong>Rp {{ formatCurrency(discountValue) }}</strong>.
              <b-button
                size="sm"
                variant="link"
                class="p-0 ml-2"
                @click="removeCoupon"
                :disabled="processing"
              >
                (Remove)
              </b-button>
            </b-alert>
          </b-card>

          <!-- Customer Information -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="person"></b-icon> Customer Information
            </b-card-title>
            
            <b-form @submit.prevent="processCheckout">
              <b-row>
                <b-col md="6">
                  <b-form-group label="Full Name *" label-for="customer-name">
                    <b-form-input
                      id="customer-name"
                      v-model="customerInfo.name"
                      type="text"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="6">
                  <b-form-group label="Email *" label-for="customer-email">
                    <b-form-input
                      id="customer-email"
                      v-model="customerInfo.email"
                      type="email"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="6">
                  <b-form-group label="Phone Number *" label-for="customer-phone">
                    <b-form-input
                      id="customer-phone"
                      v-model="customerInfo.phone"
                      type="tel"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="6">
                  <b-form-group label="Address *" label-for="customer-address">
                    <b-form-input
                      id="customer-address"
                      v-model="customerInfo.address"
                      type="text"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="4">
                  <b-form-group label="City *" label-for="customer-city">
                    <b-form-input
                      id="customer-city"
                      v-model="customerInfo.city"
                      type="text"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="4">
                  <b-form-group label="Postal Code *" label-for="customer-postal-code">
                    <b-form-input
                      id="customer-postal-code"
                      v-model="customerInfo.postal_code"
                      type="text"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                
                <b-col md="4">
                  <b-form-group label="Province *" label-for="customer-province">
                    <b-form-input
                      id="customer-province"
                      v-model="customerInfo.province"
                      type="text"
                      required
                      :disabled="processing"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>

              <!-- Notes -->
              <b-form-group label="Order Notes (Optional)" label-for="order-notes">
                <b-form-textarea
                  id="order-notes"
                  v-model="orderNotes"
                  rows="3"
                  max-rows="6"
                  :disabled="processing"
                ></b-form-textarea>
              </b-form-group>

              <!-- Terms -->
              <b-form-group>
                <b-form-checkbox 
                  v-model="agreeToTerms" 
                  required
                  :disabled="processing"
                >
                  I agree to the <b-link href="#" @click.prevent="showTerms">Terms and Conditions</b-link>
                </b-form-checkbox>
              </b-form-group>

              <!-- Submit Button -->
              <b-button
                type="submit"
                variant="success"
                size="lg"
                block
                :disabled="processing || !agreeToTerms"
              >
                <template v-if="processing">
                  <b-spinner small></b-spinner> Processing Order...
                </template>
                <template v-else>
                  <b-icon icon="check-circle"></b-icon> Place Order (Rp {{ formatCurrency(cartTotal) }})
                </template>
              </b-button>
            </b-form>
          </b-card>
        </b-col>

        <!-- Order Summary Sidebar -->
        <b-col lg="4">
          <b-card class="sticky-top" style="top: 20px;">
            <b-card-title>
              <b-icon icon="receipt"></b-icon> Order Summary
            </b-card-title>

            <b-list-group flush>
              <b-list-group-item class="d-flex justify-content-between">
                <span>Subtotal</span>
                <strong>Rp {{ formatCurrency(cartSubtotal) }}</strong>
              </b-list-group-item>
              <b-list-group-item v-if="appliedCoupon" class="d-flex justify-content-between text-success">
                <span>Discount ({{ appliedCoupon.code }})</span>
                <strong>- Rp {{ formatCurrency(discountValue) }}</strong>
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

            <b-alert v-if="error" variant="danger" class="mt-3" show>
              {{ error }}
            </b-alert>

            <b-button
              variant="outline-primary"
              block
              class="mt-3"
              :to="{ name: 'Cart' }"
              :disabled="processing"
            >
              <b-icon icon="arrow-left"></b-icon> Back to Cart
            </b-button>
          </b-card>
        </b-col>
      </b-row>
    </b-container>

    <!-- Terms Modal -->
    <b-modal 
      id="terms-modal" 
      title="Terms and Conditions" 
      size="lg"
      ok-only
      ok-title="Close"
    >
      <div class="terms-content">
        <h5>Photobook Studio Terms and Conditions</h5>
        <p><strong>Last Updated:</strong> {{ new Date().toLocaleDateString() }}</p>
        
        <h6>1. General</h6>
        <p>By placing an order with Photobook Studio, you agree to these terms and conditions.</p>
        
        <h6>2. Payment</h6>
        <p>All payments must be completed before order processing begins.</p>
        
        <h6>3. Delivery</h6>
        <p>We aim to deliver your photobook within 7-14 business days after payment confirmation.</p>
        
        <h6>4. Returns</h6>
        <p>Returns are accepted within 30 days for manufacturing defects only.</p>
        
        <h6>5. Contact</h6>
        <p>For questions, contact us at support@photobookstudio.com</p>
      </div>
    </b-modal>
  </app-layout>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import orderService from '../../services/orderService';

export default {
  name: 'Checkout',
  data() {
    return {
      loading: false,
      processing: false,
      error: null,
      customerInfo: {
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        postal_code: '',
        province: ''
      },
      orderNotes: '',
      agreeToTerms: false,
      // --- Tambahkan data untuk kupon ---
      couponCode: '',
      appliedCoupon: null,
      couponLoading: false,
      couponError: null
      // ---
    };
  },
  computed: {
    ...mapGetters('cart', ['cartItems', 'isEmpty']),
    // --- Tambahkan computed untuk subtotal, total, dan diskon ---
    cartSubtotal() {
        return this.cartItems.reduce((sum, item) => {
            return sum + (item.quantity * item.product.price);
        }, 0);
    },
    cartTotal() {
        if (this.appliedCoupon && this.appliedCoupon.discount_percent !== undefined) {
            const discount = (this.appliedCoupon.discount_percent / 100) * this.cartSubtotal;
            return Math.max(this.cartSubtotal - discount, 0);
        }
        return this.cartSubtotal;
    },
    discountValue() {
         if (this.appliedCoupon && this.appliedCoupon.discount_percent !== undefined) {
            return (this.appliedCoupon.discount_percent / 100) * this.cartSubtotal;
         }
         return 0;
    }
    // ---
  },
  async created() {
    await this.loadCheckoutData();
  },
  methods: {
    ...mapActions('cart', ['loadCart']),
    
    async loadCheckoutData() {
      this.loading = true;
      try {
        await this.$store.dispatch('cart/loadCart');
        
        const user = this.$store.getters['auth/user'];
        if (user) {
          this.customerInfo.name = user.name;
          this.customerInfo.email = user.email;
          
          const profile = user.photobook_profile;
          if (profile) {
            this.customerInfo.phone = profile.phone_number || '';
            this.customerInfo.address = profile.address || '';
            this.customerInfo.city = profile.city || '';
            this.customerInfo.postal_code = profile.postal_code || '';
            this.customerInfo.province = profile.province || '';
          }
        }
      } catch (error) {
        console.error('Failed to load checkout ', error);
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: 'Failed to load checkout data',
          type: 'danger'
        });
      } finally {
        this.loading = false;
      }
    },
    
    // --- Tambahkan methods untuk kupon ---
    async applyCoupon() {
        if (!this.couponCode.trim()) return;

        this.couponLoading = true;
        this.couponError = null;

        try {
            const response = await orderService.validateCoupon(this.couponCode);
            this.appliedCoupon = response.coupon;
            this.$store.dispatch('showNotification', {
                title: 'Coupon Applied',
                message: `Coupon ${this.appliedCoupon.code} applied successfully!`,
                type: 'success'
            });
        } catch (error) {
            console.error('Failed to apply coupon:', error);
            this.couponError = error.error || 'Invalid or expired coupon code.';
            this.$store.dispatch('showNotification', {
                title: 'Coupon Error',
                message: this.couponError,
                type: 'danger'
            });
        } finally {
            this.couponLoading = false;
        }
    },
    removeCoupon() {
        this.appliedCoupon = null;
        this.couponCode = '';
        this.couponError = null;
        this.$store.dispatch('showNotification', {
            title: 'Coupon Removed',
            message: 'Coupon has been removed.',
            type: 'info'
        });
    },
    // ---

    async processCheckout() {
      if (!this.agreeToTerms) {
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: 'Please agree to the terms and conditions',
          type: 'warning'
        });
        return;
      }
      
      this.processing = true;
      this.error = null;
      
      try {
        const orderData = {
            // customer_info: this.customerInfo, // Format lama, tidak digunakan
            // notes: this.orderNotes, // Format lama, tidak digunakan
            // --- Gunakan format data customer yang sesuai dengan validasi backend ---
            customer_name: this.customerInfo.name,
            customer_email: this.customerInfo.email,
            customer_phone: this.customerInfo.phone,
            customer_address: this.customerInfo.address,
            customer_city: this.customerInfo.city,
            customer_postal_code: this.customerInfo.postal_code,
            // customer_province: this.customerInfo.province, // Backend mungkin tidak butuh ini
            notes: this.orderNotes,
            // --- Tambahkan kode kupon ---
            coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null
            // ---
        };
        
        const response = await orderService.checkout(orderData);
        
        this.$store.dispatch('showNotification', {
          title: 'Success',
          message: 'Order placed successfully!',
          type: 'success'
        });
        
        await this.$store.dispatch('cart/loadCart');
        
        this.$router.push({
          name: 'OrderDetail',
          params: { id: response.order.id }
        });
        
      } catch (error) {
        console.error('Checkout failed:', error);
        // Tangani error spesifik kupon dari backend jika perlu
        if (error.error && error.error.includes('coupon')) {
             this.couponError = error.error;
             // Opsional: Hapus kupon yang gagal
             // this.removeCoupon();
        }
        this.error = error.error || 'Failed to process checkout. Please try again.';
        this.$store.dispatch('showNotification', {
          title: 'Error',
          message: this.error,
          type: 'danger'
        });
      } finally {
        this.processing = false;
      }
    },
    
    showTerms() {
      this.$bvModal.show('terms-modal');
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
  max-height: 80px;
  object-fit: cover;
}

.sticky-top {
  z-index: 1000;
}

.terms-content {
  max-height: 400px;
  overflow-y: auto;
}

.terms-content h5, .terms-content h6 {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.terms-content p {
  margin-bottom: 0.5rem;
}
</style>
<template>
  <app-layout>
    <b-container>
      <!-- Page Header -->
      <b-row class="mb-3">
        <b-col>
          <h1 class="page-title">
            <b-icon icon="credit-card"></b-icon> Checkout
          </h1>
          <p class="text-muted mb-0">Complete your order</p>
        </b-col>
      </b-row>

      <!-- Loading -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width:3rem;height:3rem;"></b-spinner>
          <p class="mt-3">Preparing your checkout...</p>
        </b-col>
      </b-row>

      <!-- Empty -->
      <b-row v-else-if="isEmpty">
        <b-col cols="12">
          <b-alert variant="warning" show>
            <h4 class="mb-2"><b-icon icon="exclamation-triangle"></b-icon> Empty Cart</h4>
            <p class="mb-3">You don't have any items in your cart.</p>
            <b-button variant="primary" :to="{ name:'Products' }">
              <b-icon icon="images"></b-icon> Browse Products
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Content -->
      <b-row v-else>
        <!-- LEFT -->
        <b-col lg="8">
          <!-- Order Items -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-3">
              <b-icon icon="list"></b-icon> Order Items ({{ cartItems.length }})
            </b-card-title>

            <div class="order-list">
              <div
                v-for="item in cartItems"
                :key="item.id"
                class="order-item-card"
              >
                <div class="order-item">
                  <!-- image -->
                  <div class="thumb-wrap">
                    <b-img
                      :src="getProductImage(item.product)"
                      :alt="item.product.name"
                      class="thumb"
                      rounded
                      @error="onImageError"
                    />
                  </div>

                  <!-- info -->
                  <div class="info-wrap">
                    <div class="title clamp-2">{{ item.product.name }}</div>

                    <div class="meta">
                      <span class="badge-template">
                        {{ item.template ? item.template.name : 'Default Template' }}
                      </span>
                      <span class="divider">•</span>
                      <span class="muted">
                        Qty: {{ item.quantity }} × Rp {{ formatCurrency(item.product.price) }}
                      </span>
                    </div>

                    <div class="line-total">
                      <span>Total</span>
                      <strong>Rp {{ formatCurrency(getItemTotal(item)) }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </b-card>

          <!-- Coupon -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-3">
              <b-icon icon="tag"></b-icon> Coupon Code
            </b-card-title>

            <b-form @submit.prevent="applyCoupon">
              <b-row>
                <b-col md="8">
                  <b-form-group
                    label="Enter your coupon code"
                    label-for="coupon-code"
                    :state="couponError ? false : null"
                    :invalid-feedback="couponError"
                    class="mb-2 mb-md-0"
                  >
                    <b-form-input
                      id="coupon-code"
                      v-model="couponCode"
                      type="text"
                      placeholder="Enter coupon code"
                      :disabled="couponLoading || !!appliedCoupon"
                      required
                    />
                  </b-form-group>
                </b-col>
                <b-col md="4" class="mt-2 mt-md-0">
                  <b-button
                    type="submit"
                    variant="outline-primary"
                    block
                    :disabled="couponLoading || !couponCode.trim() || !!appliedCoupon"
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

          <!-- Customer Info + Place Order -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-3">
              <b-icon icon="person"></b-icon> Customer Information
            </b-card-title>

            <b-form ref="checkoutForm" @submit.prevent="processCheckout">
              <b-row>
                <b-col md="6">
                  <b-form-group label="Full Name *" label-for="customer-name">
                    <b-form-input
                      id="customer-name"
                      v-model="customerInfo.name"
                      type="text"
                      required
                      :disabled="processing"
                    />
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
                    />
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
                    />
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
                    />
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
                    />
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
                    />
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
                    />
                  </b-form-group>
                </b-col>
              </b-row>

              <b-form-group label="Order Notes (Optional)" label-for="order-notes">
                <b-form-textarea
                  id="order-notes"
                  v-model="orderNotes"
                  rows="3"
                  max-rows="6"
                  :disabled="processing"
                />
              </b-form-group>

              <b-form-group>
                <b-form-checkbox
                  v-model="agreeToTerms"
                  required
                  :disabled="processing"
                >
                  I agree to the <b-link href="#" @click.prevent="showTerms">Terms and Conditions</b-link>
                </b-form-checkbox>
              </b-form-group>

              <!-- Desktop place order -->
              <b-button
                type="submit"
                variant="success"
                size="lg"
                block
                class="d-none d-lg-block"
                :disabled="processing || !agreeToTerms"
              >
                <template v-if="processing">
                  <b-spinner small></b-spinner> Processing Order...
                </template>
                <template v-else>
                  <b-icon icon="check-circle"></b-icon>
                  Place Order (Rp {{ formatCurrency(cartTotal) }})
                </template>
              </b-button>
            </b-form>
          </b-card>
        </b-col>

        <!-- RIGHT: Summary (desktop) -->
        <b-col lg="4" class="d-none d-lg-block">
          <b-card class="border-0 shadow-sm sticky-top" style="top:20px;">
            <b-card-title class="mb-3">
              <b-icon icon="receipt"></b-icon> Order Summary
            </b-card-title>

            <b-list-group flush class="summary-list">
              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Subtotal</span>
                <strong>Rp {{ formatCurrency(cartSubtotal) }}</strong>
              </b-list-group-item>

              <b-list-group-item
                v-if="appliedCoupon"
                class="d-flex justify-content-between align-items-center text-success"
              >
                <span>Discount ({{ appliedCoupon.code }})</span>
                <strong>- Rp {{ formatCurrency(discountValue) }}</strong>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Shipping</span>
                <strong class="text-success">FREE</strong>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Tax</span>
                <strong>Included</strong>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between align-items-center bg-light">
                <h5 class="mb-0">Total</h5>
                <h5 class="mb-0 text-primary">Rp {{ formatCurrency(cartTotal) }}</h5>
              </b-list-group-item>
            </b-list-group>

            <b-alert v-if="error" variant="danger" show class="mt-3">
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

    <!-- MOBILE: bottom bar summary -->
    <div class="mobile-summary d-lg-none" v-if="!loading && !isEmpty">
      <div class="mobile-summary-inner">
        <div class="left">
          <div class="label">Total</div>
          <div class="value">Rp {{ formatCurrency(cartTotal) }}</div>
          <div class="hint">Free shipping • Tax included</div>
        </div>
        <b-button
          variant="success"
          class="btn-checkout"
          :disabled="processing || !agreeToTerms"
          @click="processCheckout"
        >
          <template v-if="processing">
            <b-spinner small></b-spinner>
            <span class="ml-2">Processing...</span>
          </template>
          <template v-else>Place Order</template>
        </b-button>
      </div>
    </div>

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

      // coupon
      couponCode: '',
      appliedCoupon: null,
      couponLoading: false,
      couponError: null
    };
  },
  computed: {
    ...mapGetters('cart', ['cartItems', 'isEmpty']),

    cartSubtotal() {
      return this.cartItems.reduce((sum, item) => sum + (item.quantity * item.product.price), 0);
    },
    discountValue() {
      if (this.appliedCoupon && this.appliedCoupon.discount_percent !== undefined) {
        return (this.appliedCoupon.discount_percent / 100) * this.cartSubtotal;
      }
      return 0;
    },
    cartTotal() {
      return Math.max(this.cartSubtotal - this.discountValue, 0);
    }
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
          this.customerInfo.name = user.name || '';
          this.customerInfo.email = user.email || '';

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

    // coupon
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
          customer_name: this.customerInfo.name,
          customer_email: this.customerInfo.email,
          customer_phone: this.customerInfo.phone,
          customer_address: this.customerInfo.address,
          customer_city: this.customerInfo.city,
          customer_postal_code: this.customerInfo.postal_code,
          // customer_province: this.customerInfo.province, // aktifkan jika backend butuh
          notes: this.orderNotes,
          coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null
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

        if (error.error && String(error.error).toLowerCase().includes('coupon')) {
          this.couponError = error.error;
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
      return new Intl.NumberFormat('id-ID').format(amount || 0);
    },

    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        return product.thumbnail.startsWith('/storage/')
          ? product.thumbnail
          : '/storage/' + product.thumbnail;
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },

    onImageError(e) {
      e.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    }
  }
};
</script>

<style scoped>
.page-title { font-weight: 800; }

/* ORDER ITEMS (ikuti gaya cart) */
.order-item-card {
  border-radius: .9rem;
  border: 1px solid #f1f5f9;
  padding: .75rem;
  margin-bottom: .75rem;
}
.order-item {
  display: grid;
  grid-template-columns: 96px 1fr;
  gap: 12px;
  align-items: stretch;
}
.thumb-wrap {
  position: relative;
  width: 100%;
  padding-top: 100%;        /* 1:1 */
  overflow: hidden;
  border-radius: .6rem;
  background: #f8fafc;
}
.thumb {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.info-wrap { display:flex; flex-direction:column; }
.title { font-weight: 700; font-size: 1rem; margin-bottom: 6px; }
.clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.meta {
  font-size: .85rem; color: #6b7280; margin-bottom: 8px;
  display:flex; align-items:center; flex-wrap:wrap; gap:6px;
}
.badge-template {
  background:#e6f6ff; color:#0b74c7; border:1px solid rgba(14,165,233,.35);
  border-radius:999px; padding:.18rem .5rem; font-size:.78rem; white-space:nowrap;
}
.divider { opacity:.6; }
.muted { color:#6b7280; }
.line-total {
  display:flex; align-items:center; justify-content:space-between;
  font-size:.95rem; color:#111827;
}

/* SUMMARY */
.summary-list .list-group-item { border:none; }
.summary-list .list-group-item + .list-group-item { border-top:1px solid #f1f5f9; }

/* TERMS MODAL */
.terms-content { max-height: 400px; overflow-y: auto; }
.terms-content h5, .terms-content h6 { margin-top: 1rem; margin-bottom: .5rem; }
.terms-content p { margin-bottom: .5rem; }

/* MOBILE: bottom bar */
.mobile-summary {
  position: sticky;
  bottom: 0;
  left: 0;
  width: 100%;
  background: #ffffff;
  border-top: 1px solid #e5e7eb;
  box-shadow: 0 -6px 20px rgba(2,132,199,.08);
  z-index: 1040;
}
.mobile-summary-inner {
  max-width: 1140px;
  margin: 0 auto;
  padding: .75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.mobile-summary .left .label { font-size:.8rem; color:#6b7280; line-height:1.1; }
.mobile-summary .left .value { font-weight:800; font-size:1.05rem; color:#0ea5e9; line-height:1.2; }
.mobile-summary .left .hint { font-size:.75rem; color:#94a3b8; }
.btn-checkout { border-radius:.6rem; padding:.6rem 1rem; font-weight:700; }

/* RESPONSIVE */
@media (max-width: 991.98px) {
  .order-item { grid-template-columns: 88px 1fr; }
  .title { font-size: .98rem; }
}
</style>

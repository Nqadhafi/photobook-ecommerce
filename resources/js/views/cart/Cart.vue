<template>
  <app-layout>
    <b-container>
      <!-- Page Header -->
      <b-row class="mb-3">
        <b-col>
          <h1 class="page-title">
            <b-icon icon="cart"></b-icon> Keranjang Belanja
          </h1>
          <p class="text-muted mb-0">Rewiew keranjang anda.</p>
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
          <b-card class="text-center py-5 soft-card border-0">
            <b-icon icon="cart-x" font-scale="3" variant="secondary"></b-icon>
            <h3 class="mt-3 mb-1">Keranjang anda kosong</h3>
            <p class="text-muted">Add some amazing photobooks to get started!</p>
            <b-button variant="primary" :to="{ name: 'Products' }" size="lg">
              <b-icon icon="images"></b-icon> Browse Products
            </b-button>
          </b-card>
        </b-col>
      </b-row>

      <!-- Cart Items -->
      <b-row v-else>
        <!-- LEFT: Items -->
        <b-col lg="8">
          <!-- Items -->
          <div class="cart-list">
            <b-card
              v-for="item in cartItems"
              :key="item.id"
              class="cart-item-card border-0 shadow-sm mb-3"
            >
              <div class="cart-item">
                <!-- Image -->
                <div class="thumb-wrap">
                  <b-img
                    :src="getProductImage(item.product)"
                    :alt="item.product.name"
                    class="thumb"
                    rounded
                    @error="onImageError"
                  />
                </div>

                <!-- Info -->
                <div class="info-wrap">
                  <div class="title clamp-2">{{ item.product.name }}</div>
                  <div class="meta">
                    <span class="badge-template">
                      {{ item.template ? item.template.name : 'Default Template' }}
                    </span>
                    <span class="divider">•</span>
                    <span class="muted">
                      {{ item.quantity }} {{ item.quantity > 1 ? 'copies' : 'copy' }}
                      <span v-if="item.design_same">(same design)</span>
                      <span v-else>(mixed designs)</span>
                    </span>
                  </div>

                  <div class="price-row">
                    <div class="unit-price">
                      Rp {{ formatCurrency(item.product.price) }}
                      <span class="unit-label">/ item</span>
                    </div>
                    <div class="qty-box">
                      <span class="qty-label">Qty</span>
                      <b-form-input
                        :value="item.quantity"
                        type="number"
                        min="1"
                        max="10"
                        size="sm"
                        class="qty-input"
                        disabled
                      />
                    </div>
                  </div>

                  <div class="total-row">
                    <div class="total">Total: <strong>Rp {{ formatCurrency(getItemTotal(item)) }}</strong></div>
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
                        <b-icon icon="trash"></b-icon> Hapus
                      </template>
                    </b-button>
                  </div>
                </div>
              </div>
            </b-card>
          </div>

          <!-- Continue Shopping -->
          <div class="mt-3">
            <b-button variant="outline-primary" :to="{ name: 'Products' }">
              <b-icon icon="arrow-left"></b-icon> Lanjutkan Belanja
            </b-button>
          </div>
        </b-col>

        <!-- RIGHT: Order Summary (desktop) -->
        <b-col lg="4" class="d-none d-lg-block">
          <b-card class="border-0 shadow-sm sticky-top" style="top: 20px;">
            <b-card-title class="mb-3">
              <b-icon icon="receipt"></b-icon> Ringkasan Pesanan
            </b-card-title>

            <b-list-group flush class="summary-list">
              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Subtotal</span>
                <strong>Rp {{ formatCurrency(cartTotal) }}</strong>
              </b-list-group-item>
              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Pengiriman</span>
                <strong class="text-success">Ambil Di Tempat</strong>
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

            <b-button
              variant="success"
              size="lg"
              block
              class="mt-4"
              :to="{ name: 'Checkout' }"
              :disabled="isEmpty"
            >
              <b-icon icon="credit-card"></b-icon> Lanjutkan ke Pembayaran
            </b-button>

            <b-button
              variant="outline-secondary"
              block
              class="mt-2"
              :to="{ name: 'Products' }"
            >
              <b-icon icon="images"></b-icon> Tambah Produk Lain
            </b-button>
          </b-card>
        </b-col>
      </b-row>
    </b-container>

    <!-- MOBILE: Bottom bar summary -->
    <div class="mobile-summary d-lg-none" v-if="!loading && !isEmpty">
      <div class="mobile-summary-inner">
        <div class="left">
          <div class="label">Total</div>
          <div class="value">Rp {{ formatCurrency(cartTotal) }}</div>
          <div class="hint">Tax included</div>
        </div>
        <b-button
          variant="success"
          class="btn-checkout"
          :to="{ name: 'Checkout' }"
        >
          Checkout
        </b-button>
      </div>
    </div>
  </app-layout>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';

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
      return new Intl.NumberFormat('id-ID').format(amount || 0);
    },

    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        // jika dari storage lokal
        return product.thumbnail.startsWith('/storage/')
          ? product.thumbnail
          : '/storage/' + product.thumbnail;
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
.page-title { font-weight: 800; }

/* Item card */
.cart-item-card {
  border-radius: .9rem;
}

.cart-item {
  display: grid;
  grid-template-columns: 110px 1fr;
  gap: 14px;
  align-items: stretch;
}

.thumb-wrap {
  position: relative;
  width: 100%;
  padding-top: 100%;            /* 1:1 */
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

.info-wrap { display: flex; flex-direction: column; }

.title {
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 6px;
}

.clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.meta {
  font-size: .85rem;
  color: #6b7280;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}
.badge-template {
  background: #e6f6ff;
  color: #0b74c7;
  border: 1px solid rgba(14,165,233,.35);
  border-radius: 999px;
  padding: .18rem .5rem;
  font-size: .78rem;
  white-space: nowrap;
}
.divider { opacity: .6; }
.muted { color: #6b7280; }

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.unit-price {
  font-weight: 700;
  color: #0ea5e9;
  font-size: 1.05rem;
}
.unit-label {
  font-weight: 400;
  color: #6b7280;
  font-size: .85rem;
  margin-left: 4px;
}
.qty-box {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.qty-label { font-size: .85rem; color: #6b7280; }
.qty-input {
  width: 70px;
  text-align: center;
  border-radius: .5rem;
}

.total-row {
  margin-top: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.total { font-size: .95rem; color: #111827; }

/* Desktop summary list cosmetics */
.summary-list .list-group-item { border: none; }
.summary-list .list-group-item + .list-group-item { border-top: 1px solid #f1f5f9; }

/* Soft card for empty state */
.soft-card {
  border-radius: 1rem;
  box-shadow: 0 16px 40px rgba(2,132,199,.12);
}

/* MOBILE: bottom summary bar */
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
.mobile-summary .left .label {
  font-size: .8rem;
  color: #6b7280;
  line-height: 1.1;
}
.mobile-summary .left .value {
  font-weight: 800;
  font-size: 1.05rem;
  color: #0ea5e9;
  line-height: 1.2;
}
.mobile-summary .left .hint {
  font-size: .75rem;
  color: #94a3b8;
}
.btn-checkout {
  border-radius: .6rem;
  padding: .6rem 1rem;
  font-weight: 700;
}

/* RESPONSIVE */
@media (max-width: 991.98px) {
  .cart-item {
    grid-template-columns: 88px 1fr;   /* lebih kecil di mobile */
    gap: 12px;
  }
  .title { font-size: .98rem; }
  .unit-price { font-size: 1rem; }
}

@media (max-width: 575.98px) {
  .qty-input { width: 64px; }
}
</style>

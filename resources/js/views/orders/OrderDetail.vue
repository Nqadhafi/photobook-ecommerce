<!-- resources/js/views/orders/OrderDetail.vue -->
<template>
  <app-layout>
    <b-container>
      <!-- Loading -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width:3rem;height:3rem;"></b-spinner>
          <p class="mt-3">Loading order details...</p>
        </b-col>
      </b-row>

      <!-- Error -->
      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4 class="mb-2"><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p class="mb-3">{{ error }}</p>
            <b-button variant="primary" :to="{ name: 'Orders' }" size="sm">
              <b-icon icon="arrow-left"></b-icon> Back to Orders
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Content -->
      <b-row v-else-if="order">
        <b-col cols="12">
          <b-breadcrumb :items="breadcrumbItems"></b-breadcrumb>
        </b-col>

        <!-- LEFT -->
        <b-col lg="8">
          <!-- Progress -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-2">
              <b-icon icon="kanban"></b-icon> Order Progress
              <span class="muted ml-2">#{{ order.order_number }}</span>
            </b-card-title>
            <OrderTimeline />
          </b-card>

          <!-- Order Summary (items + breakdown) -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-2 d-flex align-items-center">
              <div>
                <b-icon icon="receipt"></b-icon> Order Summary
                <span class="muted ml-2">#{{ order.order_number }}</span>
              </div>
              <b-badge :variant="getStatusVariant(order.status)" class="ml-2">
                {{ formatStatus(order.status) }}
              </b-badge>
            </b-card-title>

            <b-row class="mb-2">
              <b-col md="6">
                <div class="small text-muted">Date</div>
                <div class="font-weight-600">{{ formatDate(order.created_at) }}</div>
              </b-col>
              <b-col md="6">
                <div class="small text-muted">Customer</div>
                <div class="font-weight-600">{{ order.customer_name }}</div>
                <div class="muted small">{{ order.customer_email }}</div>
              </b-col>
            </b-row>

            <hr>

            <!-- Items -->
            <div class="order-list">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="order-item-card"
              >
                <div class="order-item">
                  <!-- image -->
                  <div class="thumb-wrap">
                    <b-img
                      :src="getProductImage(item.product)"
                      rounded
                      class="thumb"
                      @error="onItemImageError"
                    />
                  </div>

                  <!-- info -->
                  <div class="info-wrap">
                    <div class="title clamp-2">
                      {{ item.product ? item.product.name : 'No Product' }}
                    </div>

                    <div class="meta">
                      <span class="badge-template">{{ item.template ? item.template.name : 'N/A' }}</span>
                      <span class="divider">•</span>
                      <span class="muted">
                        Qty: {{ item.quantity }}
                        <span class="divider">•</span>
                        {{ formatPrice(item.price) }} / item
                      </span>
                    </div>

                    <!-- photo slots info (jika ada) -->
                    <div class="slots" v-if="item.template && item.template.layout_data && item.template.layout_data.photo_slots !== undefined">
                      <b-icon icon="images" class="mr-1"></b-icon>
                      Required Photos:
                      <strong>
                        {{ getTotalPhotoSlotsForItem(item) }}
                        ({{ item.template.layout_data.photo_slots }} per book)
                      </strong>
                    </div>

                    <div class="line-total">
                      <span>Line Total</span>
                      <strong>{{ formatCurrency(item.quantity * item.price) }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Breakdown -->
            <b-list-group flush class="summary-list mt-2">
              <b-list-group-item class="d-flex justify-content-between align-items-center">
                <span>Subtotal</span>
                <strong>{{ formatCurrency(order.sub_total_amount) }}</strong>
              </b-list-group-item>

              <b-list-group-item
                v-if="order.discount_amount && order.discount_amount > 0"
                class="d-flex justify-content-between align-items-center text-success"
              >
                <span>
                  Discount
                  <span v-if="order.coupons && order.coupons.length">
                    ({{ order.coupons.map(c => c.code).join(', ') }})
                  </span>
                </span>
                <strong>- {{ formatCurrency(order.discount_amount) }}</strong>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between align-items-center bg-light">
                <h5 class="mb-0">Total</h5>
                <h5 class="mb-0 text-primary">{{ formatCurrency(order.total_amount) }}</h5>
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Shipping Address -->
          <b-card class="border-0 shadow-sm mb-3">
            <b-card-title class="mb-2">
              <b-icon icon="geo-alt"></b-icon> Shipping Address
            </b-card-title>
            <address class="mb-0">
              <strong>{{ order.customer_name }}</strong><br>
              {{ order.customer_address }}<br>
              {{ order.customer_city }}, {{ order.customer_postal_code }}
            </address>
          </b-card>
        </b-col>

        <!-- RIGHT: Payment & Actions (visible on ALL sizes) -->
        <b-col lg="4">
          <b-card class="border-0 shadow-sm sticky-lg mb-mobile-6">
            <b-card-title class="mb-2">
              <b-icon icon="credit-card"></b-icon> Payment & Actions
            </b-card-title>

            <b-alert
              v-if="paymentError"
              variant="danger"
              dismissible
              @dismissed="paymentError = ''"
              class="mb-3"
            >
              {{ paymentError }}
            </b-alert>

            <!-- Cancel -->
            <b-button
              v-if="order.status === 'pending'"
              variant="outline-danger"
              block
              size="sm"
              class="mb-2"
              @click="cancelOrder"
              :disabled="isCancelling"
            >
              <b-spinner v-if="isCancelling" small></b-spinner>
              <template v-else>Cancel Order</template>
            </b-button>

            <!-- Pending payment -->
            <div v-if="order.status === 'pending'">
              <p class="text-muted small mb-2">Complete your payment to proceed with your order.</p>
              <b-button
                variant="success"
                size="lg"
                block
                class="mb-2"
                @click="initiatePayment"
                :disabled="isPaying || !snapToken"
              >
                <b-spinner v-if="isPaying" small></b-spinner>
                <template v-else>
                  Pay Now ({{ formatCurrency(order.total_amount) }})
                </template>
              </b-button>
              <b-alert v-if="!snapToken" variant="warning" show class="mb-2">
                Payment information is not available. Please refresh or contact support.
              </b-alert>
              <p class="text-muted small mb-0">
                <b-icon icon="info-circle"></b-icon>
                You will be redirected to Midtrans secure payment page.
              </p>
            </div>

            <!-- Paid: waiting folder -->
            <div v-else-if="order.status === 'paid'">
              <b-alert variant="info" show class="mb-3">
                <b-icon icon="hourglass-split"></b-icon>
                <strong>Payment Successful!</strong><br>
                <small class="text-muted">Paid on {{ formatDate(order.paid_at) }}</small>
              </b-alert>
              <p class="mb-2">
                <b-icon icon="cloud-download"></b-icon>
                We are preparing your Google Drive folder for uploads. You will receive a notification/WhatsApp link soon.
              </p>
              <b-alert variant="warning" show class="small mb-0">
                <b-icon icon="info-circle"></b-icon>
                Please wait for the folder link. The in-app "Upload Design Files" is no longer used.
              </b-alert>
            </div>

            <!-- File Upload ready -->
            <div v-else-if="order.status === 'file_upload'">
              <b-alert variant="success" show class="mb-3">
                <b-icon icon="check-circle"></b-icon>
                <strong>Folder Ready!</strong><br>
                <small class="text-muted">Updated on {{ formatDate(order.updated_at) }}</small>
              </b-alert>
              <b-button
                v-if="order.google_drive_folder_url"
                :href="order.google_drive_folder_url"
                target="_blank"
                variant="primary"
                block
                class="mb-2"
              >
                <b-icon icon="box-arrow-up-right"></b-icon> Open Google Drive Folder
              </b-button>
              <b-alert v-else variant="danger" show class="small mb-2">
                <b-icon icon="exclamation-triangle"></b-icon> Folder link is missing. Please contact support.
              </b-alert>
              <p class="text-muted small mb-0">
                Upload your design files to the Drive folder. Our team will verify after you finish uploading.
              </p>
            </div>

            <!-- Cancelled -->
            <div v-else-if="order.status === 'cancelled'">
              <b-alert variant="danger" show class="mb-3">
                <b-icon icon="x-circle"></b-icon>
                <strong>Order Cancelled</strong><br>
                <small class="text-muted" v-if="order.cancelled_at">Cancelled on {{ formatDate(order.cancelled_at) }}</small>
              </b-alert>
              <p class="mb-0">If you have questions, please contact support.</p>
            </div>

            <!-- Other statuses -->
            <div v-else>
              <b-alert :variant="getStatusVariant(order.status)" show class="mb-3">
                <strong>{{ formatStatus(order.status) }}</strong>
              </b-alert>
              <p class="mb-0">
                Your order is currently in the <strong>{{ formatStatus(order.status) }}</strong> status.
              </p>
            </div>

            <hr>

            <!-- Back -->
            <b-button
              variant="outline-primary"
              block
              size="sm"
              :to="{ name: 'Orders' }"
              class="mb-2"
            >
              <b-icon icon="arrow-left"></b-icon> Back to Orders
            </b-button>
          </b-card>
        </b-col>
      </b-row>
    </b-container>

    <!-- MOBILE bottom bar (aksi cepat) -->
    <div class="mobile-summary d-lg-none" v-if="order && !loading">
      <div class="mobile-summary-inner">
        <div class="left">
          <div class="label" v-if="order.status==='pending'">Total</div>
          <div class="value" v-if="order.status==='pending'">{{ formatCurrency(order.total_amount) }}</div>
          <div class="label" v-else>Status</div>
          <div class="value" v-else>{{ formatStatus(order.status) }}</div>
          <div class="hint" v-if="order.status==='pending'">Free shipping • Tax included</div>
        </div>

        <!-- Pending: Pay Now -->
        <b-button
          v-if="order.status==='pending'"
          variant="success"
          class="btn-checkout"
          :disabled="isPaying || !snapToken"
          @click="initiatePayment"
        >
          <b-spinner v-if="isPaying" small></b-spinner>
          <template v-else>Pay Now</template>
        </b-button>

        <!-- Not pending: Back -->
        <b-button
          v-else
          variant="outline-primary"
          class="btn-checkout"
          :to="{ name: 'Orders' }"
        >
          Back
        </b-button>
      </div>
    </div>
  </app-layout>
</template>

<script>
import orderService from '../../services/orderService';
import { loadScript } from '../../utils/helpers';
import OrderTimeline from './OrderTimeline.vue';

export default {
  name: 'OrderDetail',
  components: { OrderTimeline },
  data() {
    return {
      order: null,
      loading: false,
      error: null,
      paymentError: null,
      isCancelling: false,
      isPaying: false,
      snapToken: null
    };
  },
  computed: {
    breadcrumbItems() {
      return [
        { text: 'Dashboard', to: { name: 'Dashboard' } },
        { text: 'Orders', to: { name: 'Orders' } },
        { text: `Order #${this.order ? this.order.order_number : '...'}`, active: true }
      ];
    }
  },
  async created() {
    await this.loadOrder();
  },
  methods: {
    async loadOrder() {
      this.loading = true;
      this.error = null;
      this.paymentError = null;
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrder(orderId);
        this.order = response.data;

        // butuh relasi: ->with(['items.product','items.template','payment','coupons'])
        if (this.order.snap_token) {
          this.snapToken = this.order.snap_token;
        } else if (this.order.payment && this.order.payment.snap_token) {
          this.snapToken = this.order.payment.snap_token;
        }
      } catch (e) {
        console.error('Failed to load order:', e);
        this.error = e.message || 'Failed to load order details. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async cancelOrder() {
      if (!this.order) return;
      const confirmed = window.confirm(`Are you sure you want to cancel order #${this.order.order_number}? This action cannot be undone.`);
      if (!confirmed) return;

      this.isCancelling = true;
      try {
        await orderService.cancelOrder(this.order.id);
        this.$store.dispatch('showNotification', {
          title: 'Order Cancelled',
          message: `Order #${this.order.order_number} has been cancelled.`,
          type: 'success'
        });
        await this.loadOrder();
      } catch (e) {
        console.error('Failed to cancel order:', e);
        this.$store.dispatch('showNotification', {
          title: 'Cancellation Failed',
          message: e.message || 'Failed to cancel order. Please try again.',
          type: 'danger'
        });
      } finally {
        this.isCancelling = false;
      }
    },

    async initiatePayment() {
      if (!this.snapToken) {
        this.paymentError = 'Payment token is missing. Please refresh or contact support.';
        return;
      }
      this.isPaying = true;
      this.paymentError = null;
      try {
        const clientKey = process.env.MIX_MIDTRANS_CLIENT_KEY;
        if (!clientKey) throw new Error('MIDTRANS_CLIENT_KEY is not configured in .env');

        await loadScript('https://app.sandbox.midtrans.com/snap/snap.js', 'data-client-key', clientKey);
        if (typeof window.snap === 'undefined') throw new Error('Failed to load Midtrans Snap.js.');

        window.snap.pay(this.snapToken, {
          onSuccess: (result) => { this.handlePaymentOutcome('success', result); },
          onPending: (result) => { this.handlePaymentOutcome('pending', result); },
          onError:   (result) => { this.handlePaymentOutcome('error', result); },
          onClose:   () => { this.isPaying = false; this.loadOrder(); }
        });
      } catch (e) {
        console.error('Snap init error:', e);
        this.paymentError = e.message || 'Failed to open payment page.';
        this.isPaying = false;
      }
    },

    handlePaymentOutcome(outcome, result) {
      this.isPaying = false;
      let notification = { title:'', message:'', type:'' };

      if (outcome === 'success') {
        notification = { title:'Payment Successful', message:'Your payment has been processed. Refreshing…', type:'success' };
        setTimeout(() => this.loadOrder(), 1200);
      } else if (outcome === 'pending') {
        notification = { title:'Payment Pending', message: result.status_message || 'Awaiting confirmation.', type:'warning' };
        setTimeout(() => this.loadOrder(), 1200);
      } else {
        notification = { title:'Payment Error', message: result.status_message || 'Payment failed. Try again.', type:'danger' };
      }
      if (notification.title) this.$store.dispatch('showNotification', notification);
    },

    getStatusVariant(status) {
      const map = {
        pending: 'warning',
        paid: 'info',
        file_upload: 'success',
        processing: 'primary',
        ready: 'success',
        completed: 'success',
        cancelled: 'danger'
      };
      return map[status] || 'secondary';
    },
    formatStatus(status) {
      const map = {
        pending: 'Pending Payment',
        paid: 'Payment Received',
        file_upload: 'Folder Ready for Upload',
        processing: 'In Production',
        ready: 'Ready for Pickup',
        completed: 'Completed',
        cancelled: 'Cancelled'
      };
      return map[status] || status;
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const opt = { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', opt);
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 }).format(amount || 0);
    },
    formatPrice(amount) {
      return new Intl.NumberFormat('id-ID', { minimumFractionDigits:0 }).format(amount || 0);
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
    onItemImageError(e) {
      e.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    getTotalPhotoSlotsForItem(item) {
      if (!item.template || !item.template.layout_data) return null;
      const perBook = item.template.layout_data.photo_slots;
      if (perBook === undefined || perBook === null) return null;
      const sets = item.design_same ? 1 : item.quantity;
      return perBook * sets;
    }
  }
};
</script>

<style scoped>
.muted { color:#64748b; }
.font-weight-600 { font-weight:600; }

/* Item list */
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
  padding-top: 100%; /* 1:1 */
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
.title { font-weight:700; font-size:1rem; margin-bottom:6px; }
.clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.meta {
  font-size:.85rem; color:#6b7280; margin-bottom:8px;
  display:flex; align-items:center; flex-wrap:wrap; gap:6px;
}
.badge-template {
  background:#e6f6ff; color:#0b74c7; border:1px solid rgba(14,165,233,.35);
  border-radius:999px; padding:.18rem .5rem; font-size:.78rem; white-space:nowrap;
}
.divider { opacity:.6; }
.slots { font-size:.85rem; color:#334155; margin-bottom:6px; }
.line-total {
  display:flex; align-items:center; justify-content:space-between;
  font-size:.95rem; color:#111827;
}

/* Summary list */
.summary-list .list-group-item { border:none; }
.summary-list .list-group-item + .list-group-item { border-top:1px solid #f1f5f9; }

/* Sticky only on LG+ */
.sticky-lg { position: static; }
@media (min-width: 992px) {
  .sticky-lg { position: sticky; top: 20px; }
}

/* Extra bottom space on mobile so card tidak ketutup bar */
.mb-mobile-6 { }
@media (max-width: 991.98px) {
  .mb-mobile-6 { margin-bottom: 84px; }
}

/* Mobile bottom bar */
.mobile-summary {
  position: sticky;
  bottom: 0; left: 0;
  width: 100%;
  background: #fff;
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

/* Responsive tweak */
@media (max-width: 991.98px) {
  .order-item { grid-template-columns: 88px 1fr; }
  .title { font-size:.98rem; }
}
</style>

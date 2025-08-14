<!-- resources/js/views/orders/OrderDetail.vue -->
<template>
  <app-layout>
    <b-container>
      <!-- Loading State -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading order details...</p>
        </b-col>
      </b-row>
      <!-- Error State -->
      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button variant="primary" :to="{ name: 'Orders' }" size="sm">
              <b-icon icon="arrow-left"></b-icon> Back to Orders
            </b-button>
          </b-alert>
        </b-col>
      </b-row>
      <!-- Order Detail -->
      <b-row v-else-if="order">
        <b-col cols="12">
          <b-breadcrumb :items="breadcrumbItems"></b-breadcrumb>
        </b-col>
        <b-col lg="8">
                    <!-- Order Timeline Component -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="kanban"></b-icon> Order Progress<span v-if="order">#{{ order.order_number }}</span>
            </b-card-title>
            <OrderTimeline/>
          </b-card>
          <!-- Order Summary Card -->
          <!-- --- PERUBAHAN: Tampilkan breakdown biaya di card ini --- -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="receipt"></b-icon> Order Summary #{{ order.order_number }}
              <b-badge :variant="getStatusVariant(order.status)" class="ml-2">
                {{ formatStatus(order.status) }}
              </b-badge>
            </b-card-title>
            <b-row>
              <b-col md="6">
                <p class="mb-1"><strong>Date:</strong> {{ formatDate(order.created_at) }}</p>
                <!-- <p class="mb-1"><strong>Total Amount:</strong> <strong>{{ formatCurrency(order.total_amount) }}</strong></p> -->
              </b-col>
              <b-col md="6">
                <p class="mb-1"><strong>Name:</strong> {{ order.customer_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ order.customer_email }}</p>
                <!-- <p class="mb-1"><strong>Phone:</strong> {{ order.customer_phone }}</p> -->
              </b-col>
            </b-row>
            <!-- --- Tambahkan breakdown biaya --- -->
             <hr>
            <b-list-group flush>
                          <b-list-group flush>
              <b-list-group-item v-for="item in order.items" :key="item.id">
                <b-row class="align-items-center">
                  <b-col md="2" class="text-center mb-2 mb-md-0">
                    <b-img
                      :src="getProductImage(item.product)"
                      rounded
                      fluid
                      class="product-thumbnail"
                      @error="onItemImageError"
                    ></b-img>
                  </b-col>
                  <b-col md="6">
                    <h6 class="mb-1">{{ item.product ? item.product.name : 'No Product' }}</h6>
                    <!-- --- PERUBAHAN: Tampilkan informasi slot foto --- -->
                    <p class="text-muted small mb-0">
                      Template: {{ item.template ? item.template.name : 'N/A' }}<br>
                      Qty: {{ item.quantity }} &times; {{ formatCurrency(item.price) }}
                      <span v-if="!item.design_same">(Different designs)</span><br>
                      <span v-if="item.template && item.template.layout_data && item.template.layout_data.photo_slots !== undefined">
                        <b-icon icon="images" class="mr-1"></b-icon>
                        Required Photos:
                        <strong>
                          {{ getTotalPhotoSlotsForItem(item) }}
                          ({{ item.template.layout_data.photo_slots }} per book)
                        </strong>
                      </span>
                      <span v-else-if="item.template">
                        <b-icon icon="images" class="mr-1"></b-icon>
                        Required Photos: <strong>N/A</strong>
                      </span>
                    </p>
                     <!-- --- AKHIR PERUBAHAN --- -->
                  </b-col>
                  <b-col md="4" class="text-md-right">
                    <strong> {{ formatCurrency(item.quantity * item.price) }}</strong>
                  </b-col>
                </b-row>
              </b-list-group-item>
            </b-list-group>
              <b-list-group-item class="d-flex justify-content-between px-0">
                <span>Subtotal</span>
                <strong> {{ formatCurrency(order.sub_total_amount) }}</strong>
              </b-list-group-item>
              <b-list-group-item v-if="order.discount_amount && order.discount_amount > 0" class="d-flex justify-content-between px-0 text-success">
                <span>
                    Discount
                    <span v-if="order.coupons && order.coupons.length > 0">
                        ({{ order.coupons.map(c => c.code).join(', ') }})
                    </span>
                </span>
                <strong>-  {{ formatCurrency(order.discount_amount) }}</strong>
              </b-list-group-item>
              <b-list-group-item class="d-flex justify-content-between px-0 bg-light">
                <h5 class="mb-0">Total</h5>
                <h5 class="mb-0 text-primary"> {{ formatCurrency(order.total_amount) }}</h5>
              </b-list-group-item>
            </b-list-group>
            <!-- --- Akhir breakdown biaya --- -->
          </b-card>
          <!-- --- AKHIR PERUBAHAN --- -->


          <!-- Customer Address -->
          <b-card class="mb-4">
            <b-card-title><b-icon icon="geo-alt"></b-icon> Shipping Address</b-card-title>
            <address class="mb-0">
              <strong>{{ order.customer_name }}</strong><br>
              {{ order.customer_address }}<br>
              {{ order.customer_city }}, {{ order.customer_postal_code }}<br>
              <!-- <strong>Phone:</strong> {{ order.customer_phone }} -->
            </address>
          </b-card>
        </b-col>
        <b-col lg="4">
          <!-- Payment & Actions Card -->
          <b-card class="sticky-top" style="top: 20px;">
            <b-card-title><b-icon icon="credit-card"></b-icon> Payment & Actions</b-card-title>
            <b-alert v-if="paymentError" variant="danger" dismissible @dismissed="paymentError = ''" class="mb-3">
              {{ paymentError }}
            </b-alert>
              <!-- --- Tombol Cancel Order --- -->
  <hr>
<b-button
  v-if="order && order.status === 'pending'"
  variant="outline-danger"
  block
  size="sm"
  @click="cancelOrder"
  :disabled="isCancelling"
  class="mb-2"
>
 <b-spinner v-if="isCancelling" small></b-spinner>
 {{ isCancelling ? 'Cancelling...' : 'Cancel Order' }}
</b-button>
  <!-- -------------------------- -->
            <!-- Payment Instructions/Info for Pending Orders -->
            <div v-if="order.status === 'pending' && snapToken">
              <p class="text-muted small mb-2">Complete your payment to proceed with your order.</p>
              <b-button
                variant="success"
                size="lg"
                block
                @click="initiatePayment"
                :disabled="isPaying"
                class="mb-2"
              >
                <b-spinner v-if="isPaying" small></b-spinner>
                {{ isPaying ? 'Opening Payment...' : 'Pay Now ( ' + formatCurrency(order.total_amount) + ')' }}
              </b-button>
              <p class="text-muted small mb-0"><b-icon icon="info-circle"></b-icon> You will be redirected to Midtrans secure payment page.</p>
            </div>
            <div v-else-if="order.status === 'pending' && !snapToken">
              <b-alert variant="warning" show class="mb-3">
                Payment information is not available. Please contact support or try refreshing the page.
              </b-alert>
            </div>
            <!-- --- PERUBAHAN: Pesan untuk status 'paid' (menunggu folder) --- -->
            <div v-else-if="order.status === 'paid'">
              <b-alert variant="info" show class="mb-3">
                <b-icon icon="hourglass-split"></b-icon> <strong>Payment Successful!</strong><br>
                <small class="text-muted">Paid on {{ formatDate(order.paid_at) }}</small>
              </b-alert>
              <p class="mb-2">
                <b-icon icon="cloud-download"></b-icon>
                Thank you for your payment. We are preparing your Google Drive folder for file uploads.
                You will receive a notification and a WhatsApp message with the link once it's ready.
              </p>
              <b-alert variant="warning" show class="small mb-0">
                <b-icon icon="info-circle"></b-icon>
                Please wait for the folder link. The "Upload Design Files" feature within the app is no longer used.
              </b-alert>
            </div>
            <!-- --- AKHIR PERUBAHAN --- -->
            <!-- --- PERUBAHAN: Pesan dan tombol untuk status 'file_upload' (folder siap) --- -->
            <div v-else-if="order.status === 'file_upload'">
              <b-alert variant="success" show class="mb-3">
                <b-icon icon="check-circle"></b-icon> <strong>Folder Ready!</strong><br>
                <small class="text-muted">Status updated on {{ formatDate(order.updated_at) }} <!-- Asumsi ada updated_at --> </small>
              </b-alert>
              <p class="mb-2">
                <b-icon icon="folder"></b-icon>
                Your Google Drive folder has been created successfully.
              </p>
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
                Please upload your design files to the Google Drive folder. Our team will verify the files after you finish uploading.
              </p>
            </div>
            <!-- --- AKHIR PERUBAHAN --- -->
            <!-- Message for Cancelled Orders -->
            <div v-else-if="order.status === 'cancelled'">
              <b-alert variant="danger" show class="mb-3">
                <b-icon icon="x-circle"></b-icon> <strong>Order Cancelled</strong><br>
                <small class="text-muted" v-if="order.cancelled_at">Cancelled on {{ formatDate(order.cancelled_at) }}</small>
              </b-alert>
              <p class="mb-2">Your order has been cancelled. If you have any questions, please contact support.</p>
            </div>
            <!-- Message for Other Statuses (e.g., processing, ready, completed) -->
            <div v-else>
              <b-alert :variant="getStatusVariant(order.status)" show class="mb-3">
                <strong>{{ formatStatus(order.status) }}</strong>
              </b-alert>
              <p class="mb-2">Your order is currently in the <strong>{{ formatStatus(order.status) }}</strong> status.</p>
              <!-- Tambahkan aksi atau informasi spesifik untuk status lain jika diperlukan -->
            </div>
            <hr>
            <!-- General Actions -->
            <b-button
              variant="outline-primary"
              block
              size="sm"
              :to="{ name: 'Orders' }"
              class="mb-2"
            >
              <b-icon icon="arrow-left"></b-icon> Back to Orders
            </b-button>
            <!-- --- HAPUS: Tombol Upload Design Files lama --- -->
            <!-- <b-button ...>Upload Design Files</b-button> -->
            <!-- --- AKHIR HAPUS --- -->

          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>
<script>
// ... (imports tetap sama) ...
import orderService from '../../services/orderService';
import { loadScript } from '../../utils/helpers';
import OrderTimeline from './OrderTimeline.vue';

export default {
  name: 'OrderDetail',
  components: {
    OrderTimeline
  },
  data() {
    return {
      order: null,
      loading: false,
      error: null,
      paymentError: null,
      isCancelling: false,
      cancelError: null,
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
    },
    arePhotoSlotsDefinedForAllItems() {
      if (!this.order || !this.order.items || this.order.items.length === 0) {
        return false;
      }

      return this.order.items.every(item => {
        return item.template &&
               item.template.layout_data &&
               item.template.layout_data.photo_slots !== undefined &&
               item.template.layout_data.photo_slots !== null;
      });
    }
  },
  async created() {
    await this.loadOrder();
  },
  methods: {
    async cancelOrder() {
      if (!this.order) return;
      const confirmed = window.confirm(`Are you sure you want to cancel order #${this.order.order_number}? This action cannot be undone.`);
      if (!confirmed) return;
      this.isCancelling = true;
      this.cancelError = null;
      try {
         await orderService.cancelOrder(this.order.id);
         this.$store.dispatch('showNotification', {
            title: 'Order Cancelled',
            message: `Order #${this.order.order_number} has been cancelled.`,
            type: 'success'
         });
         await this.loadOrder();
      } catch (error) {
         console.error('Failed to cancel order:', error);
         this.cancelError = error.message || 'Failed to cancel order. Please try again.';
         this.$store.dispatch('showNotification', {
            title: 'Cancellation Failed',
            message: this.cancelError,
            type: 'danger'
         });
      } finally {
         this.isCancelling = false;
      }
    },
    async loadOrder() {
      this.loading = true;
      this.error = null;
      this.paymentError = null;
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrder(orderId);
        this.order = response.data;
        // --- Pastikan data kupon di-load ---
        // Backend perlu mengirim relasi 'coupons' saat getOrder
        // Di controller getOrder, tambahkan: ->with(['items.product', 'items.template', 'payment', 'coupons'])
        // ---
        if (this.order.snap_token) {
            this.snapToken = this.order.snap_token;
        }
        else if (this.order.payment && this.order.payment.snap_token) {
            this.snapToken = this.order.payment.snap_token;
        }
      } catch (error) {
        console.error('Failed to load order:', error);
        this.error = error.message || 'Failed to load order details. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    async initiatePayment() {
      if (!this.snapToken) {
        this.paymentError = 'Payment token is missing. Please try refreshing the page or contact support.';
        console.error('Snap token is missing for order:', this.order?.id);
        return;
      }
      this.isPaying = true;
      this.paymentError = null;
      try {
        const clientKey = process.env.MIX_MIDTRANS_CLIENT_KEY;
        if (!clientKey) {
            throw new Error('MIDTRANS_CLIENT_KEY is not configured in .env file.');
        }
        await loadScript('https://app.sandbox.midtrans.com/snap/snap.js', 'data-client-key', clientKey);
        if (typeof window.snap === 'undefined') {
            throw new Error('Failed to load Midtrans Snap.js. Please check your internet connection and try again.');
        }
        window.snap.pay(this.snapToken, {
            onSuccess: (result) => {
                console.log('Payment Success:', result);
                this.handlePaymentOutcome('success', result);
            },
            onPending: (result) => {
                console.log('Payment Pending:', result);
                this.handlePaymentOutcome('pending', result);
            },
            onError: (result) => {
                console.error('Payment Error:', result);
                this.handlePaymentOutcome('error', result);
            },
            onClose: () => {
                console.log('Payment Popup Closed');
                this.isPaying = false;
                 this.loadOrder();
            }
        });
      } catch (error) {
        console.error('Failed to initiate Snap payment:', error);
        this.paymentError = error.message || 'Failed to open payment page. Please try again.';
        this.isPaying = false;
      }
    },
    handlePaymentOutcome(outcome, result) {
        this.isPaying = false;
        let notification = { title: '', message: '', type: '' };
        if (outcome === 'success') {
            notification = {
                title: 'Payment Successful',
                message: 'Your payment has been processed successfully. Redirecting...',
                type: 'success'
            };
            setTimeout(async () => {
                await this.loadOrder();
            }, 2000);
        } else if (outcome === 'pending') {
             notification = {
                title: 'Payment Pending',
                message: result.status_message || 'Your payment is being processed. We will notify you once it is confirmed.',
                type: 'warning'
            };
            setTimeout(async () => {
                await this.loadOrder();
            }, 2000);
        } else if (outcome === 'error') {
             notification = {
                title: 'Payment Error',
                message: result.status_message || 'An error occurred during payment. Please try again or check your transaction history.',
                type: 'danger'
            };
        }
        if (notification.title) {
            this.$store.dispatch('showNotification', notification);
        }
    },
    getStatusVariant(status) {
      const statusMap = {
        'pending': 'warning',
        'paid': 'info',
        'file_upload': 'success',
        'processing': 'primary',
        'ready': 'success',
        'completed': 'success',
        'cancelled': 'danger'
      };
      return statusMap[status] || 'secondary';
    },
    formatStatus(status) {
       const statusLabels = {
        'pending': 'Pending Payment',
        'paid': 'Payment Received (Preparing Folder)',
        'file_upload': 'Folder Ready for Upload',
        'processing': 'In Production',
        'ready': 'Ready for Pickup',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
      };
      return statusLabels[status] || status;
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    formatCurrency(amount) {
      // --- PERUBAHAN: Gunakan Intl.NumberFormat untuk format IDR yang konsisten ---
      return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
      // ---
    },
    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) {
          return product.thumbnail;
        }
        return product.thumbnail;
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    onItemImageError(event) {
      event.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    getTotalPhotoSlotsForItem(item) {
      if (!item.template || !item.template.layout_data) {
        return null;
      }

      const photoSlotsPerBook = item.template.layout_data.photo_slots;
      if (photoSlotsPerBook === undefined || photoSlotsPerBook === null) {
        return null;
      }

      const numberOfSets = item.design_same ? 1 : item.quantity;

      return photoSlotsPerBook * numberOfSets;
    },
  }
};
</script>
<style scoped>
/* ... (styles tetap sama) ... */
.product-thumbnail {
  max-height: 80px;
  object-fit: cover;
}
.sticky-top {
  z-index: 1000;
}
</style>
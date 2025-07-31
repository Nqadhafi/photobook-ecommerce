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
          <!-- Order Summary Card -->
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
                <p class="mb-1"><strong>Total Amount:</strong> <strong>{{ formatCurrency(order.total_amount) }}</strong></p>
              </b-col>
              <b-col md="6">
                <p class="mb-1"><strong>Name:</strong> {{ order.customer_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ order.customer_email }}</p>
                <!-- <p class="mb-1"><strong>Phone:</strong> {{ order.customer_phone }}</p> -->
              </b-col>
            </b-row>
          </b-card>

          <!-- Order Items -->
          <b-card class="mb-4">
            <b-card-title><b-icon icon="list"></b-icon> Order Items</b-card-title>
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
                    <p class="text-muted small mb-0">
                      Template: {{ item.template ? item.template.name : 'N/A' }}<br>
                      Qty: {{ item.quantity }} &times; {{ formatCurrency(item.price) }}
                      <span v-if="!item.design_same">(Different designs)</span>
                    </p>
                  </b-col>
                  <b-col md="4" class="text-md-right">
                    <strong> {{ formatCurrency(item.quantity * item.price) }}</strong>
                  </b-col>
                </b-row>
              </b-list-group-item>
            </b-list-group>
          </b-card>

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
            
            <!-- Success Message for Paid Orders -->
            <div v-else-if="order.status === 'paid'">
              <b-alert variant="success" show class="mb-3">
                <b-icon icon="check-circle"></b-icon> <strong>Payment Successful!</strong><br>
                <small class="text-muted">Paid on {{ formatDate(order.paid_at) }}</small>
              </b-alert>
              <p class="mb-2">Thank you for your payment. You can now upload your design files.</p>
              <b-button 
                variant="primary" 
                :to="{ name: 'FileUpload', params: { id: order.id } }"
                block
                class="mb-2"
              >
                <b-icon icon="cloud-upload"></b-icon> Upload Design Files
              </b-button>
            </div>
            
            <!-- Message for Cancelled Orders -->
            <div v-else-if="order.status === 'cancelled'">
              <b-alert variant="danger" show class="mb-3">
                <b-icon icon="x-circle"></b-icon> <strong>Order Cancelled</strong><br>
                <small class="text-muted" v-if="order.cancelled_at">Cancelled on {{ formatDate(order.cancelled_at) }}</small>
              </b-alert>
              <p class="mb-2">Your order has been cancelled. If you have any questions, please contact support.</p>
            </div>
            
            <!-- Message for Other Statuses (e.g., file_upload, processing) -->
             <div v-else>
              <b-alert :variant="getStatusVariant(order.status)" show class="mb-3">
                <strong>{{ formatStatus(order.status) }}</strong>
              </b-alert>
              <p class="mb-2">Your order is currently in the <strong>{{ formatStatus(order.status) }}</strong> status.</p>
              <!-- Tambahkan aksi atau informasi spesifik untuk status lain jika diperlukan -->
              <div v-if="order.status === 'file_upload'">
                 <b-button 
                    variant="primary" 
                    :to="{ name: 'FileUpload', params: { id: order.id } }"
                    block
                    class="mb-2"
                  >
                    <b-icon icon="cloud-upload"></b-icon> Upload Design Files
                  </b-button>
              </div>
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
            
            <b-button
              variant="outline-info"
              block
              size="sm"
              :to="{ name: 'OrderTimeline', params: { id: order.id } }"
            >
              <b-icon icon="clock-history"></b-icon> View Order Timeline
            </b-button>
          </b-card>

        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import orderService from '../../services/orderService';
import { loadScript } from '../../utils/helpers'; // Pastikan file helpers.js ada

export default {
  name: 'OrderDetail',
  data() {
    return {
      order: null,
      loading: false,
      error: null,
      paymentError: null,
      isCancelling: false,
      cancelError: null,
      isPaying: false,
      snapToken: null // Akan diambil dari data order
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


    async cancelOrder() {
      if (!this.order) return;

      // Konfirmasi dari pengguna (opsional tapi disarankan)
      const confirmed = window.confirm(`Are you sure you want to cancel order #${this.order.order_number}? This action cannot be undone.`);
      if (!confirmed) return;

      this.isCancelling = true;
      this.cancelError = null;
      try {
         // Gunakan orderService yang sudah diperbarui
         await orderService.cancelOrder(this.order.id); 
         
         // Tampilkan notifikasi sukses
         this.$store.dispatch('showNotification', {
            title: 'Order Cancelled',
            message: `Order #${this.order.order_number} has been cancelled.`,
            type: 'success'
         });

         // Refresh data order untuk mendapatkan status terbaru
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
      this.paymentError = null; // Clear previous payment errors
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrder(orderId);
        this.order = response.data;

        // --- Ambil snap_token ---
        // Asumsi backend mengirim snap_token dalam response order
        // Ini bisa dalam `order.snap_token` atau melalui relasi `order.payment.snap_token`
        // Periksa struktur respons dari `PhotobookOrderController@checkout`
        
        // Cara 1: Jika snap_token dikirim langsung di level order
        if (this.order.snap_token) {
            this.snapToken = this.order.snap_token;
        }
        // Cara 2: Jika snap_token ada di relasi payment (lebih umum)
        else if (this.order.payment && this.order.payment.snap_token) {
            this.snapToken = this.order.payment.snap_token;
        }
        // Jika tidak ditemukan, snapToken tetap null, dan komponen akan menangani kasus ini
        
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
        // --- 1. Muat Snap.js ---
        // Gunakan Client Key dari environment variable Laravel Mix
        const clientKey = process.env.MIX_MIDTRANS_CLIENT_KEY;
        if (!clientKey) {
            throw new Error('MIDTRANS_CLIENT_KEY is not configured in .env file.');
        }
        await loadScript('https://app.sandbox.midtrans.com/snap/snap.js', 'data-client-key', clientKey);
        
        // --- 2. Pastikan window.snap tersedia ---
        if (typeof window.snap === 'undefined') {
            throw new Error('Failed to load Midtrans Snap.js. Please check your internet connection and try again.');
        }

        // --- 3. Buka halaman pembayaran Snap ---
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
                // Pengguna menutup popup. Tidak berarti sukses atau gagal.
                // Bisa memilih untuk tidak melakukan apa-apa, atau memberi info.
                // Opsi: Refresh status order dari backend untuk memastikan tidak ada perubahan yang terlewat.
                // Untuk sekarang, kita hanya menghentikan indikator loading.
                this.isPaying = false;
                 // Opsional: Refresh status order dari backend?
                 // this.loadOrder();
            }
        });
      } catch (error) {
        console.error('Failed to initiate Snap payment:', error);
        this.paymentError = error.message || 'Failed to open payment page. Please try again.';
        this.isPaying = false;
      }
    },
    handlePaymentOutcome(outcome, result) {
        // --- Tangani hasil pembayaran dari callback Snap.js ---
        this.isPaying = false;
        
        // Tampilkan notifikasi berdasarkan outcome
        let notification = { title: '', message: '', type: '' };
        if (outcome === 'success') {
            notification = {
                title: 'Payment Successful',
                message: 'Your payment has been processed successfully. Redirecting...',
                type: 'success'
            };
            // Refresh data order untuk mendapatkan status terbaru
            // Tunggu sebentar sebelum refresh agar backend sempat memproses notifikasi
            setTimeout(async () => {
                await this.loadOrder();
                // Opsional: Redirect otomatis ke halaman upload jika status sudah paid
                // if (this.order && this.order.status === 'paid') {
                //     this.$router.push({ name: 'FileUpload', params: { id: this.order.id } });
                // }
            }, 2000); // Tunggu 2 detik
        } else if (outcome === 'pending') {
             notification = {
                title: 'Payment Pending',
                message: result.status_message || 'Your payment is being processed. We will notify you once it is confirmed.',
                type: 'warning'
            };
            // Refresh data order untuk mendapatkan status terbaru
            setTimeout(async () => {
                await this.loadOrder();
            }, 2000);
        } else if (outcome === 'error') {
             notification = {
                title: 'Payment Error',
                message: result.status_message || 'An error occurred during payment. Please try again or check your transaction history.',
                type: 'danger'
            };
            // Tidak perlu refresh otomatis untuk error
        }

        if (notification.title) {
            this.$store.dispatch('showNotification', notification);
        }
        // Anda juga bisa menyimpan `result` ke log atau database jika diperlukan
    },
    getStatusVariant(status) {
      const statusMap = {
        'pending': 'warning',
        'paid': 'success',
        'file_upload': 'info',
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
        'paid': 'Payment Received',
        'file_upload': 'Awaiting Files',
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
      return new Date(dateString).toLocaleDateString('id-ID', options); // Gunakan locale Indonesia
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    },
    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) {
          return product.thumbnail;
        }
        return product.thumbnail; // Asumsi path relatif sudah benar
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg'; // Placeholder umum
    },
    onItemImageError(event) {
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
</style>

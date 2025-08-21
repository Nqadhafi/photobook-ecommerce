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
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button variant="primary" :to="{ name: 'Orders' }" size="sm">
              <b-icon icon="arrow-left"></b-icon> Kembali ke Daftar Pesanan
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Content -->
      <b-row v-else-if="order">
        <b-col cols="12">
          <b-breadcrumb :items="breadcrumbItems"></b-breadcrumb>
        </b-col>

        <!-- LEFT: Timeline + Summary + Customer Address -->
        <b-col lg="8">
          <!-- Order Progress -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="kanban"></b-icon>
              Progress pesanan <span v-if="order">#{{ order.order_number }}</span>
            </b-card-title>
            <OrderTimeline />
          </b-card>

          <!-- Order Summary -->
          <b-card class="mb-4">
            <b-card-title class="d-flex align-items-center flex-wrap">
              <span class="mr-2">
                <b-icon icon="receipt"></b-icon> Ringkasan Order #{{ order.order_number }}
              </span>
              <b-badge :variant="getStatusVariant(order.status)" class="ml-2">
                {{ formatStatus(order.status) }}
              </b-badge>
            </b-card-title>

            <b-row>
              <b-col md="6">
                <p class="mb-1"><strong>Tanggal :</strong> {{ formatDate(order.created_at) }}</p>
              </b-col>
              <b-col md="6">
                <p class="mb-1"><strong>Nama:</strong> {{ order.customer_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ order.customer_email }}</p>
              </b-col>
            </b-row>
   
            <hr>

            <b-list-group flush>
              <b-list-group-item
                v-for="item in order.items"
                :key="item.id"
              >
                <b-row class="align-items-center">
                  <b-col md="2" class="text-center mb-2 mb-md-0">
                    <b-img
                      :src="getProductImage(item.product)"
                      rounded
                      fluid
                      class="product-thumbnail"
                      @error="onItemImageError"
                    />
                  </b-col>
                  <b-col md="6">
                    <h6 class="mb-1">{{ item.product ? item.product.name : 'No Product' }}</h6>
                    <p class="text-muted small mb-0">
                      Template: {{ item.template ? item.template.name : 'N/A' }}<br>
                      Qty: {{ item.quantity }} × {{ formatCurrency(item.price) }}
                      <span v-if="!item.design_same">(Beda Desain)</span><br>
                      <span v-if="item.template && item.template.layout_data && item.template.layout_data.photo_slots !== undefined">
                        <b-icon icon="images" class="mr-1"></b-icon>
                        Foto yang diperlukan:
                        <strong>
                          {{ getTotalPhotoSlotsForItem(item) }}
                          ({{ item.template.layout_data.photo_slots }} per photobook)
                        </strong>
                      </span>
                      <span v-else-if="item.template">
                        <b-icon icon="images" class="mr-1"></b-icon>
                        Foto yang diperlukan: <strong>N/A</strong>
                      </span>
                    </p>
                  </b-col>
                  <b-col md="4" class="text-md-right">
                    <strong>{{ formatCurrency(item.quantity * item.price) }}</strong>
                  </b-col>
                </b-row>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between px-0">
                <span>Subtotal</span>
                <strong>{{ formatCurrency(order.sub_total_amount) }}</strong>
              </b-list-group-item>

              <b-list-group-item
                v-if="order.discount_amount && order.discount_amount > 0"
                class="d-flex justify-content-between px-0 text-success"
              >
                <span>
                  Diskon
                  <span v-if="order.coupons && order.coupons.length > 0">
                    ({{ order.coupons.map(c => c.code).join(', ') }})
                  </span>
                </span>
                <strong>- {{ formatCurrency(order.discount_amount) }}</strong>
              </b-list-group-item>

              <b-list-group-item class="d-flex justify-content-between px-0 bg-light">
                <h5 class="mb-0">Total</h5>
                <h5 class="mb-0 text-primary">{{ formatCurrency(order.total_amount) }}</h5>
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Customer Address (billing/contact) -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="person-lines-fill"></b-icon> Informasi Pelanggan
            </b-card-title>
            <address class="mb-0">
              <strong>{{ order.customer_name }}</strong><br>
              {{ order.customer_address }}<br>
              {{ order.customer_city }}, {{ order.customer_postal_code }}<br>
              <span v-if="order.customer_phone"><strong>Phone:</strong> {{ order.customer_phone }}</span>
            </address>
          </b-card>
        </b-col>

        <!-- RIGHT: Payment/Actions + NEW: Pickup Location -->
        <b-col lg="4">
          <!-- Payment & Actions -->
          <b-card class="mb-4">
            <b-card-title><b-icon icon="credit-card"></b-icon> Pembayaran & Aksi</b-card-title>

            <b-alert v-if="paymentError" variant="danger" dismissible @dismissed="paymentError = ''" class="mb-3">
              {{ paymentError }}
            </b-alert>

            <!-- Cancel (pending) -->
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

            <!-- Pay Now (pending) -->
            <div v-if="order.status === 'pending' && snapToken">
              <p class="text-muted small mb-2">Selesaikan pembayaran anda.</p>
              <b-button
                variant="success"
                size="lg"
                block
                @click="initiatePayment"
                :disabled="isPaying"
                class="mb-2"
              >
                <b-spinner v-if="isPaying" small></b-spinner>
                {{ isPaying ? 'Opening Payment...' : 'Pay Now (' + formatCurrency(order.total_amount) + ')' }}
              </b-button>
              <p class="text-muted small mb-0">
                <b-icon icon="info-circle"></b-icon>
                You will be redirected to Midtrans secure payment page.
              </p>
            </div>

            <div v-else-if="order.status === 'pending' && !snapToken">
              <b-alert variant="warning" show class="mb-0">
                Payment info unavailable. Refresh or contact support.
              </b-alert>
            </div>

            <!-- Paid -->
            <div v-else-if="order.status === 'paid'">
              <b-alert variant="info" show class="mb-3">
                <b-icon icon="hourglass-split"></b-icon>
                <strong>Payment Berhasil!</strong><br>
                <small class="text-muted">Paid on {{ formatDate(order.paid_at) }}</small>
              </b-alert>
              <p class="mb-2">
                <b-icon icon="cloud-download"></b-icon>
                We are preparing your Google Drive folder for file uploads. You will receive the link soon.
              </p>
              <b-alert variant="warning" show class="small mb-0">
                <b-icon icon="info-circle"></b-icon>
                Please wait for the folder link. In-app upload is no longer used.
              </b-alert>
            </div>

            <!-- File Upload -->
            <div v-else-if="order.status === 'file_upload'">
              <b-alert variant="success" show class="mb-3">
                <b-icon icon="check-circle"></b-icon>
                <strong>Folder Siap!</strong>
                <br>
                <small class="text-muted">Updated {{ formatDate(order.updated_at) }}</small>
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
                <b-icon icon="exclamation-triangle"></b-icon>
                Folder link is missing. Please contact support.
              </b-alert>
              <p class="text-muted small mb-0">
                Silahkan upload file photo ke link di atas, tim kami akan menghubungi anda.
              </p>
            </div>

            <!-- Cancelled -->
            <div v-else-if="order.status === 'cancelled'">
              <b-alert variant="danger" show class="mb-2">
                <b-icon icon="x-circle"></b-icon>
                <strong>Order Cancelled</strong><br>
                <small class="text-muted" v-if="order.cancelled_at">Dibatalkan pada {{ formatDate(order.cancelled_at) }}</small>
              </b-alert>
              <p class="mb-0">Mengalami kendala?,hubungi tim kami.</p>
            </div>

            <!-- Other statuses -->
            <div v-else>
              <b-alert :variant="getStatusVariant(order.status)" show class="mb-0">
                <strong>{{ formatStatus(order.status) }}</strong>
              </b-alert>
              <p class="mt-2 mb-0">
                Status order anda sekarang <strong>{{ formatStatus(order.status) }}</strong>.
              </p>
            </div>

            <hr>
            <b-button
              variant="outline-primary"
              block
              size="sm"
              :to="{ name: 'Orders' }"
              class="mb-2"
            >
              <b-icon icon="arrow-left"></b-icon> Kembali ke Daftar Pesanan
            </b-button>
          </b-card>

          <!-- NEW: Pickup Location (Ambil di Toko) -->
          <b-card class="mb-4">
            <b-card-title>
              <b-icon icon="geo-alt"></b-icon> Ambil di Toko
            </b-card-title>

            <div class="mb-2">
              <div class="font-weight-bold">{{ pickupInfo.name }}</div>
              <address class="mb-2">
                {{ pickupInfo.address_line }}<br>
                {{ pickupInfo.city }} {{ pickupInfo.postal_code }}
              </address>
              <div class="small text-muted">
                <b-icon icon="clock"></b-icon>
                Jam Operasional: {{ pickupInfo.open_hours }}
              </div>
              <div class="small text-muted mt-1" v-if="pickupInfo.phone">
                <b-icon icon="telephone"></b-icon>
                <a :href="'tel:' + pickupInfo.phone" class="ml-1">{{ pickupInfo.phone }}</a>
              </div>
            </div>

            <div class="d-flex flex-wrap">
              <b-button
                v-if="pickupInfo.maps_url"
                :href="pickupInfo.maps_url"
                target="_blank"
                variant="outline-primary"
                class="mr-2 mb-2"
              >
                <b-icon icon="map"></b-icon> Buka di Google Maps
              </b-button>
              <b-button
                v-if="pickupInfo.whatsapp"
                :href="'https://wa.me/' + formatPhoneToWa(pickupInfo.whatsapp)"
                target="_blank"
                variant="success"
                class="mb-2"
              >
                <b-icon icon="whatsapp"></b-icon> Chat via WhatsApp
              </b-button>
            </div>
              <div v-if="order.pickup_code" class="mt-3">
              <label class="small text-muted d-block mb-2">
                <b-icon icon="key" class="mr-1"></b-icon>
                <strong>Pickup Code</strong>
              </label>
              <b-input-group size="sm" class="pickup-code-group">
                <b-form-input
                  :value="order.pickup_code"
                  readonly
                  class="fw-bold"
                />
                <b-input-group-append>
                  <b-button variant="outline-primary" @click="copyPickupCode" class="pickup-copy-btn">
                    <b-icon icon="clipboard" class="mr-1"></b-icon> Copy
                  </b-button>
                </b-input-group-append>
              </b-input-group>
              <small class="text-muted d-block mt-1">
                Tunjukkan atau ketik kode ini saat pengambilan pesanan.
              </small>
            </div>


          </b-card>
        </b-col>
      </b-row>
    </b-container>
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
      cancelError: null,
      isPaying: false,
      snapToken: null,

      // Default pickup location (ubah sesuai kebutuhan)
      defaultPickupLocation: {
        name: 'Shabat Printing',
        address_line: 'Jl. Perintis Kemerdekaan No 20C-D, Sondakan, Laweyan',
        city: 'Surakarta',
        postal_code: '12345',
        open_hours: 'Senin–Sabtu 09.00–18.00',
        phone: '+6285601367643',
        whatsapp: '6285601367643',
        maps_url: 'https://maps.app.goo.gl/3bwQUdssE2EuhVe9A'
      }
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
    pickupInfo() {
      // Jika backend mengirim field pickup_location, gunakan itu. Jika tidak, fallback ke default.
      // Struktur yang diharapkan:
      // order.pickup_location = {
      //   name, address_line, city, postal_code, open_hours, phone, whatsapp, maps_url
      // }
      const loc = (this.order && this.order.pickup_location) ? this.order.pickup_location : {};
      return {
        name: loc.name || this.defaultPickupLocation.name,
        address_line: loc.address_line || this.defaultPickupLocation.address_line,
        city: loc.city || this.defaultPickupLocation.city,
        postal_code: loc.postal_code || this.defaultPickupLocation.postal_code,
        open_hours: loc.open_hours || this.defaultPickupLocation.open_hours,
        phone: loc.phone || this.defaultPickupLocation.phone,
        whatsapp: loc.whatsapp || this.defaultPickupLocation.whatsapp,
        maps_url: loc.maps_url || this.defaultPickupLocation.maps_url
      };
    }
  },
  async created() {
    await this.loadOrder();
  },
  methods: {
    async cancelOrder() {
      if (!this.order) return;
      const ok = window.confirm(`Batalkan pesanan #${this.order.order_number}?`);
      if (!ok) return;
      this.isCancelling = true;
      this.cancelError = null;
      try {
        await orderService.cancelOrder(this.order.id);
        this.$store.dispatch('showNotification', {
          title: 'Order Cancelled',
          message: `Pesanan #${this.order.order_number} telah dibatalkan.`,
          type: 'success'
        });
        await this.loadOrder();
      } catch (error) {
        console.error('Failed to cancel order:', error);
        this.cancelError = error.message || 'Tidak bisa membatalkan order. Silahkan coba lagi.';
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

        // coupons relation should be included by backend (as you noted earlier)
        // snap token priority: direct on order, else on order.payment
        if (this.order.snap_token) {
          this.snapToken = this.order.snap_token;
        } else if (this.order.payment && this.order.payment.snap_token) {
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
        this.paymentError = 'Payment token is missing. Please refresh or contact support.';
        return;
      }
      this.isPaying = true;
      this.paymentError = null;
      try {
        const clientKey = process.env.MIX_MIDTRANS_CLIENT_KEY;
        if (!clientKey) throw new Error('MIDTRANS_CLIENT_KEY is not configured in .env');

        await loadScript('https://app.sandbox.midtrans.com/snap/snap.js', 'data-client-key', clientKey);
        if (typeof window.snap === 'undefined') {
          throw new Error('Failed to load Midtrans Snap.js. Check your connection.');
        }
        window.snap.pay(this.snapToken, {
          onSuccess: (result) => this.handlePaymentOutcome('success', result),
          onPending: (result) => this.handlePaymentOutcome('pending', result),
          onError:   (result) => this.handlePaymentOutcome('error', result),
          onClose:   () => { this.isPaying = false; this.loadOrder(); }
        });
      } catch (error) {
        console.error('Failed to initiate payment:', error);
        this.paymentError = error.message || 'Failed to open payment page. Please try again.';
        this.isPaying = false;
      }
    },

    handlePaymentOutcome(outcome, result) {
      this.isPaying = false;
      let notification;
      if (outcome === 'success') {
        notification = { title: 'Payment Successful', message: 'Pembayaran Berhasil.', type: 'success' };
        setTimeout(() => this.loadOrder(), 1200);
      } else if (outcome === 'pending') {
        notification = { title: 'Payment Pending', message: result.status_message || 'Pembayaran diproses.', type: 'warning' };
        setTimeout(() => this.loadOrder(), 1200);
      } else {
        notification = { title: 'Payment Error', message: result.status_message || 'An error occurred during payment.', type: 'danger' };
      }
      this.$store.dispatch('showNotification', notification);
    },

    getStatusVariant(status) {
      const statusMap = {
        pending: 'warning',
        paid: 'info',
        file_upload: 'success',
        processing: 'primary',
        ready: 'success',
        completed: 'success',
        cancelled: 'danger'
      };
      return statusMap[status] || 'secondary';
    },
    formatStatus(status) {
      const statusLabels = {
        pending: 'Menunggu Pembayaran',
        paid: 'Pembayaran Diterima',
        file_upload: 'Upload File Anda',
        processing: 'Sedang Diproduksi',
        ready: 'Siap Diambil',
        completed: 'Selesai',
        cancelled: 'Dibatalkan'
      };
      return statusLabels[status] || status;
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);
    },

    getProductImage(product) {
      if (product && product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        return product.thumbnail;
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
    },

    formatPhoneToWa(phone) {
      // sederhana: hapus non-digit, ubah 0xxxx -> 62xxxx
      const digits = (phone || '').replace(/\D/g, '');
      if (digits.startsWith('0')) return '62' + digits.slice(1);
      return digits;
    },
        async copyPickupCode() {
      if (!this.order || !this.order.pickup_code) return;
      const text = this.order.pickup_code.toString();
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text);
        } else {
          // fallback untuk browser lama
          const ta = document.createElement('textarea');
          ta.value = text;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
        this.$store.dispatch('showNotification', {
          title: 'Copied',
          message: 'Pickup code disalin ke papan klip.',
          type: 'success'
        });
      } catch (e) {
        this.$store.dispatch('showNotification', {
          title: 'Copy Failed',
          message: 'Unable to copy pickup code. Please copy manually.',
          type: 'danger'
        });
      }
    },

  }
};
</script>

<style scoped>
.product-thumbnail {
  max-height: 80px;
  object-fit: cover;
}

/* Sticky only on large to avoid overlap on small screens */
.sticky-card {
  position: sticky;
  top: 20px;
  z-index: 1;
}

@media (max-width: 991.98px) {
  .sticky-card {
    position: static;
    top: auto;
  }
}
</style>

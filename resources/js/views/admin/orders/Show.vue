<template>
  <div v-if="order">
    <b-row class="mb-3">
      <b-col>
        <h2>Order Details: {{ order.order_number }}</h2>
      </b-col>
      <b-col class="text-right">
        <b-button variant="secondary" :to="{ name: 'AdminOrders' }">Back to List</b-button>
      </b-col>
    </b-row>

    <b-row>
      <!-- Informasi Order -->
      <b-col md="6">
        <b-card title="Order Information" class="mb-4">
          <b-list-group flush>
            <b-list-group-item>
              <strong>Order Number:</strong> {{ order.order_number }}
            </b-list-group-item>
            <b-list-group-item>
              <strong>Status:</strong>
              <b-badge :variant="getStatusVariant(order.status)" class="ml-2">
                {{ order.status }}
              </b-badge>
            </b-list-group-item>
            <b-list-group-item>
              <strong>Total Amount:</strong> Rp{{ parseFloat(order.total_amount).toFixed(2) }}
            </b-list-group-item>
            <b-list-group-item>
              <strong>Created At:</strong> {{ formatDate(order.created_at) }}
            </b-list-group-item>
            <b-list-group-item v-if="order.paid_at">
              <strong>Paid At:</strong> {{ formatDate(order.paid_at) }}
            </b-list-group-item>
            <b-list-group-item v-if="order.completed_at">
              <strong>Completed At:</strong> {{ formatDate(order.completed_at) }}
            </b-list-group-item>
            <b-list-group-item v-if="order.cancelled_at">
              <strong>Cancelled At:</strong> {{ formatDate(order.cancelled_at) }}
            </b-list-group-item>
            <b-list-group-item v-if="order.google_drive_folder_url">
              <strong>Google Drive Folder:</strong>
              <a :href="order.google_drive_folder_url" target="_blank" rel="noopener noreferrer">
                Open Folder
                <b-icon icon="box-arrow-up-right" class="ml-1"></b-icon>
              </a>
            </b-list-group-item>
             <b-list-group-item v-if="order.notes">
              <strong>Notes:</strong> {{ order.notes }}
            </b-list-group-item>
          </b-list-group>
        </b-card>
      </b-col>

      <!-- Informasi Customer -->
      <b-col md="6">
        <b-card title="Customer Information" class="mb-4">
          <b-list-group flush>
            <b-list-group-item>
              <strong>Name:</strong> {{ order.customer_name }}
            </b-list-group-item>
            <b-list-group-item>
              <strong>Email:</strong> {{ order.customer_email }}
            </b-list-group-item>
            <b-list-group-item>
              <strong>Phone:</strong> {{ order.customer_phone }}
            </b-list-group-item>
            <b-list-group-item>
              <strong>Address:</strong>
              {{ order.customer_address }}, {{ order.customer_city }},
              {{ order.customer_postal_code }}
            </b-list-group-item>
             <b-list-group-item v-if="order.pickup_code">
              <strong>Pickup Code:</strong> {{ order.pickup_code }}
            </b-list-group-item>
          </b-list-group>
        </b-card>
      </b-col>
    </b-row>

    <!-- Items dalam Order -->
    <b-card title="Order Items" class="mb-4">
      <b-table
        striped
        hover
        responsive
        :items="order.items"
        :fields="itemFields"
        show-empty
        empty-text="No items in this order."
      >
        <template #cell(product_name)="row">
          {{ row.item.product ? row.item.product.name : 'N/A' }}
        </template>
        <template #cell(template_name)="row">
          {{ row.item.template ? row.item.template.name : 'N/A' }}
        </template>
        <template #cell(price)="row">
          Rp{{ parseFloat(row.item.price).toFixed(2) }}
        </template>
        <template #cell(total_price)="row">
          Rp{{ (parseFloat(row.item.price) * row.item.quantity).toFixed(2) }}
        </template>
      </b-table>
    </b-card>

    <!-- Aksi Admin -->
    <b-card title="Admin Actions" class="mb-4">
      <b-row>
        <b-col md="6">
          <b-form-group label="Update Status:">
            <b-form-select v-model="newStatus" :options="statusOptions" class="mb-2"></b-form-select>
            <b-button
              variant="primary"
              @click="updateStatus"
              :disabled="!newStatus || newStatus === order.status || isActionBusy"
            >
              <b-spinner small v-if="isActionBusy && actionType === 'status'"></b-spinner>
              Update Status
            </b-button>
          </b-form-group>
        </b-col>
        <b-col md="6">
          <b-form-group label="Send to Deskprint:">
            <b-form-select
              v-model="selectedDeskprintId"
              :options="deskprintOptions"
              text-field="name"
              value-field="id"
              class="mb-2"
            >
              <template #first>
                <b-form-select-option :value="null">-- Please select a deskprint --</b-form-select-option>
              </template>
            </b-form-select>
            <b-button
              variant="info"
              @click="sendToDeskprint"
              :disabled="!selectedDeskprintId || isActionBusy"
            >
              <b-spinner small v-if="isActionBusy && actionType === 'deskprint'"></b-spinner>
              Send to Deskprint
            </b-button>
          </b-form-group>
        </b-col>
      </b-row>
    </b-card>

    <!-- Riwayat Status (Timeline) - Opsional -->
    <!-- Kita bisa tambahkan ini nanti jika diperlukan -->

  </div>

  <div v-else-if="loading">
    <b-spinner variant="primary" label="Loading..."></b-spinner> Loading order details...
  </div>

  <div v-else>
    <b-alert variant="danger" show>
      <h4>Error loading order</h4>
      <p v-if="errorMessage">{{ errorMessage }}</p>
      <p v-else>Order not found or an error occurred.</p>
      <b-button variant="secondary" :to="{ name: 'AdminOrders' }">Back to List</b-button>
    </b-alert>
  </div>
</template>

<script>
import orderService from '../../../services/orderService';
import deskprintService from '../../../services/deskprintService';

export default {
  name: 'AdminOrderShow',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: false,
      order: null,
      errorMessage: '',
      isActionBusy: false,
      actionType: null, // 'status' or 'deskprint'
      newStatus: null,
      selectedDeskprintId: null,
      // Dummy data untuk deskprint options, nanti akan diambil dari API
     deskprintOptions: [],
      statusOptions: [
        { value: 'pending', text: 'Pending' },
        { value: 'paid', text: 'Paid' },
        { value: 'file_upload', text: 'File Upload' },
        { value: 'processing', text: 'Processing' },
        { value: 'ready', text: 'Ready' },
        { value: 'completed', text: 'Completed' },
        { value: 'cancelled', text: 'Cancelled' },
      ],
      itemFields: [
        { key: 'product_name', label: 'Product' },
        { key: 'template_name', label: 'Template' },
        { key: 'quantity', label: 'Qty' },
        { key: 'price', label: 'Unit Price' },
        { key: 'total_price', label: 'Total Price' },
      ]
    };
  },
  created() {
    this.fetchOrder();
    this.fetchDeskprints(); 
  },
  methods: {
    async fetchOrder() {
      this.loading = true;
      this.errorMessage = '';
      try {
        const orderId = this.id;
        // Gunakan method khusus admin dari service
        const orderData = await orderService.getAdminOrder(orderId);
        this.order = orderData;
        // Set default newStatus ke status saat ini
        this.newStatus = orderData.status;
      } catch (error) {
        console.error('Failed to fetch order details:', error);
        this.errorMessage = error.error || 'Failed to load order details. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async fetchDeskprints() {
      try {
        // Gunakan method khusus yang memastikan parameter benar
        const deskprints = await deskprintService.getActiveDeskprintsForDropdown();
        this.deskprintOptions = deskprints;
        console.log('Deskprint options for dropdown loaded:', this.deskprintOptions);
      } catch (error) {
        console.error('Failed to fetch deskprints for dropdown:', error);
        // Tampilkan pesan peringatan ringan karena ini tidak menghentikan seluruh halaman
        this.$bvToast.toast('Failed to load deskprint options. Please try updating the list.', {
          title: 'Warning',
          variant: 'warning',
          solid: true
        });
    }
    },

    async updateStatus() {
      if (!this.newStatus || this.newStatus === this.order.status) return;

      this.isActionBusy = true;
      this.actionType = 'status';
      try {
        const orderId = this.id;
        const statusData = { status: this.newStatus };
        // Gunakan method khusus admin dari service
        const response = await orderService.updateOrderStatus(orderId, statusData);

        // Update data order lokal
        this.order.status = response.order.status;
        // Update timestamp sesuai status baru jika diperlukan
        // Misalnya, jika status menjadi 'paid', update paid_at
        // Ini bisa disederhanakan jika API mengembalikan seluruh objek order yang diperbarui
        
        this.$bvToast.toast('Order status updated successfully.', {
          title: 'Success',
          variant: 'success',
          solid: true
        });
      } catch (error) {
        console.error('Failed to update order status:', error);
        const errorMsg = error.error || 'Failed to update order status. Please try again.';
        this.$bvToast.toast(errorMsg, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
      } finally {
        this.isActionBusy = false;
        this.actionType = null;
      }
    },

    async sendToDeskprint() {
      if (!this.selectedDeskprintId) return;

      this.isActionBusy = true;
      this.actionType = 'deskprint';
      try {
        const orderId = this.id;
        const deskprintData = { deskprint_id: this.selectedDeskprintId };
        // Gunakan method khusus admin dari service
        const response = await orderService.sendToDeskprint(orderId, deskprintData);

        // Update data order lokal jika perlu
        // Misalnya, tambah flag 'sent_to_deskprint'
        this.order.sent_to_deskprint = true; // Jika ada field seperti ini

        this.$bvToast.toast('Order details sent to deskprint successfully.', {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        
        // Reset pilihan setelah berhasil
        this.selectedDeskprintId = null;
        
      } catch (error) {
        console.error('Failed to send order to deskprint:', error);
        const errorMsg = error.error || 'Failed to send order to deskprint. Please try again.';
        this.$bvToast.toast(errorMsg, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
      } finally {
        this.isActionBusy = false;
        this.actionType = null;
      }
    },

    getStatusVariant(status) {
      const statusMap = {
        pending: 'secondary',
        paid: 'info',
        file_upload: 'warning',
        processing: 'primary',
        ready: 'success',
        completed: 'success',
        cancelled: 'danger',
      };
      return statusMap[status] || 'secondary';
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleString('id-ID');
    }
  }
};
</script>

<style scoped>
/* Tambahkan style khusus jika diperlukan */
</style>
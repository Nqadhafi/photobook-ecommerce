<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Order Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <!-- Tempat untuk tombol aksi global jika diperlukan di masa depan -->
      </b-col>
    </b-row>

    <!-- Filter dan Pencarian -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="4">
            <b-form-group label="Status:" label-for="filter-status">
              <b-form-select
                id="filter-status"
                v-model="filters.status"
                :options="statusOptions"
                @change="applyFilters"
              ></b-form-select>
            </b-form-group>
          </b-col>
          <b-col md="4">
            <b-form-group label="Search Customer:" label-for="filter-customer">
              <b-form-input
                id="filter-customer"
                v-model="filters.customer_name"
                placeholder="Enter customer name..."
                @keyup.enter="applyFilters"
              ></b-form-input>
            </b-form-group>
          </b-col>
          <b-col md="4" class="d-flex align-items-end">
            <b-button variant="primary" @click="applyFilters">Apply Filters</b-button>
            <b-button variant="secondary" class="ml-2" @click="resetFilters">Reset</b-button>
          </b-col>
        </b-row>
      </b-card-body>
    </b-card>

    <!-- Tabel Order -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="orders"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No orders found."
        empty-filtered-text="No orders match your filters."
      >
        <template #cell(order_number)="row">
          <router-link :to="{ name: 'AdminOrderDetail', params: { id: row.item.id } }">
            {{ row.item.order_number }}
          </router-link>
        </template>

        <template #cell(customer_name)="row">
          {{ row.item.customer_name }}
        </template>

        <template #cell(total_amount)="row">
          Rp{{ parseFloat(row.item.total_amount).toFixed(2) }}
        </template>

        <template #cell(status)="row">
          <b-badge :variant="getStatusVariant(row.item.status)">
            {{ row.item.status }}
          </b-badge>
        </template>

        <template #cell(created_at)="row">
          {{ formatDate(row.item.created_at) }}
        </template>

        <template #cell(actions)="row">
          <b-button
            size="sm"
            variant="primary"
            :to="{ name: 'AdminOrderDetail', params: { id: row.item.id } }"
            class="mr-1"
          >
            View
          </b-button>
          <!-- Tombol untuk update status bisa ditambahkan di sini atau di halaman detail -->
        </template>
      </b-table>

      <!-- Pagination -->
      <b-card-footer class="d-flex justify-content-between align-items-center">
        <div>
          <strong>Total Orders:</strong> {{ pagination.total }}
        </div>
        <b-pagination
          v-model="pagination.currentPage"
          :total-rows="pagination.total"
          :per-page="pagination.perPage"
          @change="onPageChange"
        ></b-pagination>
      </b-card-footer>
    </b-card>
  </div>
</template>

<script>
import { mapActions } from 'vuex'; // Jika menggunakan Vuex untuk memanggil API
// Atau, jika menggunakan service terpisah:
import orderService from '../../../services/orderService';

export default {
  name: 'AdminOrderIndex',
  data() {
    return {
      isBusy: false,
      orders: [],
      fields: [
        { key: 'order_number', label: 'Order Number', sortable: true },
        { key: 'customer_name', label: 'Customer', sortable: true },
        { key: 'total_amount', label: 'Total Amount', sortable: true, formatter: (value) => `Rp${parseFloat(value).toFixed(2)}` },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'created_at', label: 'Created At', sortable: true },
        { key: 'actions', label: 'Actions' }
      ],
      statusOptions: [
        { value: null, text: 'All Statuses' },
        { value: 'pending', text: 'Pending' },
        { value: 'paid', text: 'Paid' },
        { value: 'file_upload', text: 'File Upload' },
        { value: 'processing', text: 'Processing' },
        { value: 'ready', text: 'Ready' },
        { value: 'completed', text: 'Completed' },
        { value: 'cancelled', text: 'Cancelled' },
        // Tambahkan status lain jika ada
      ],
      filters: {
        status: null,
        customer_name: '',
        // Tambahkan filter lain jika diperlukan
      },
      pagination: {
        currentPage: 1,
        perPage: 15, // Harus sesuai dengan default backend
        total: 0,
      }
    };
  },
  created() {
    this.fetchOrders();
  },
  methods: {
    // ...mapActions jika menggunakan Vuex actions
    // Misalnya: ...mapActions('orders', ['fetchOrders']), // Tapi biasanya langsung di komponen

async fetchOrders(page = 1) {
  this.isBusy = true;
  try {
    const params = {
      page: page,
      per_page: this.pagination.perPage,
      status: this.filters.status,
      customer_name: this.filters.customer_name,
    };

    // Gunakan method khusus admin
    const data = await orderService.getAdminOrders(params);

    this.orders = data.data;
    this.pagination.total = data.total;
    this.pagination.currentPage = data.current_page;

  } catch (error) {
    console.error('Failed to fetch orders:', error);
    // Tampilkan pesan error yang lebih spesifik jika ada
    const errorMessage = error.error || 'Failed to load orders. Please try again.';
    this.$bvToast.toast(errorMessage, {
      title: 'Error',
      variant: 'danger',
      solid: true
    });
    this.orders = [];
  } finally {
    this.isBusy = false;
  }
},

    onPageChange(page) {
      this.fetchOrders(page);
    },

    applyFilters() {
      // Reset ke halaman 1 saat filter diterapkan
      this.pagination.currentPage = 1;
      this.fetchOrders(1);
    },

    resetFilters() {
      this.filters.status = null;
      this.filters.customer_name = '';
      this.pagination.currentPage = 1;
      this.fetchOrders(1);
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
      return date.toLocaleString('id-ID'); // Format sesuai locale Indonesia
    }

  }
};
</script>

<style scoped>
/* Tambahkan style khusus jika diperlukan */
</style>
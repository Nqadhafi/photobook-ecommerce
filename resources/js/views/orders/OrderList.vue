<!-- resources/js/views/orders/OrderList.vue -->
<template>
  <app-layout>
    <b-container>
      <!-- Breadcrumb -->
      <b-row>
        <b-col cols="12">
          <b-breadcrumb :items="breadcrumbItems"></b-breadcrumb>
        </b-col>
      </b-row>

      <!-- Title -->
      <b-row>
        <b-col cols="12">
          <h2 class="mb-4">My Orders</h2>
        </b-col>
      </b-row>

      <!-- Loading -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading your orders...</p>
        </b-col>
      </b-row>

      <!-- Error -->
      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button variant="primary" @click="loadOrders" size="sm">
              <b-icon icon="arrow-repeat"></b-icon> Retry
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Empty -->
      <b-row v-else-if="orders.length === 0">
        <b-col cols="12">
          <b-card class="text-center py-5">
            <b-icon icon="inbox" font-scale="2" variant="secondary"></b-icon>
            <h4 class="mt-3">No Orders Found</h4>
            <p class="mb-4">You haven't placed any orders yet.</p>
            <b-button variant="primary" :to="{ name: 'Products' }">
              <b-icon icon="cart-plus"></b-icon> Start Shopping
            </b-button>
          </b-card>
        </b-col>
      </b-row>

      <!-- Order List -->
      <b-row v-else>
        <b-col cols="12">
          <b-card no-body>
            <b-list-group flush>
              <b-list-group-item 
                v-for="order in orders" 
                :key="order.id" 
                :to="{ name: 'OrderDetail', params: { id: order.id } }"
                class="order-list-item"
              >
                <b-row class="align-items-center">
                  <b-col md="2">
                    <strong>Order #{{ order.order_number }}</strong>
                  </b-col>
                  <b-col md="3">
                    <b-badge :variant="getStatusVariant(order.status)">
                      {{ formatStatus(order.status) }}
                    </b-badge>
                  </b-col>
                  <b-col md="3">
                    {{ formatDate(order.created_at) }}
                  </b-col>
                  <b-col md="3">
                    <strong>Rp {{ formatCurrency(order.total_amount) }}</strong>
                  </b-col>
                  <b-col md="1" class="text-right">
                    <b-icon icon="chevron-right" class="text-muted"></b-icon>
                  </b-col>
                </b-row>
              </b-list-group-item>
            </b-list-group>
          </b-card>

          <!-- Pagination -->
          <b-pagination
            v-if="pagination && pagination.last_page > 1"
            :value="currentPage"
            :total-rows="pagination.total"
            :per-page="pagination.per_page"
            @input="onPageChange"
            align="center"
            class="mt-4"
          ></b-pagination>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import orderService from '../../services/orderService';

export default {
  name: 'Orders',
  data() {
    return {
      orders: [],
      pagination: null,
      loading: false,
      error: null,
      currentPage: 1
    };
  },
  computed: {
    breadcrumbItems() {
      return [
        { text: 'Dashboard', to: { name: 'Dashboard' } },
        { text: 'Orders', active: true }
      ];
    }
  },
  async created() {
    await this.loadOrders();
  },
  methods: {
    async loadOrders(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await orderService.getOrders({ page });
        this.orders = response.data || [];
        this.pagination = response.pagination || {
          total: this.orders.length,
          per_page: 10,
          current_page: 1,
          last_page: 1
        };
        this.currentPage = page;
      } catch (error) {
        console.error('Failed to load orders:', error);
        this.error = error.message || 'Failed to load orders. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    onPageChange(page) {
      this.loadOrders(page);
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
      try {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      } catch {
        return 'Invalid Date';
      }
    },
    formatCurrency(amount) {
      try {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(amount || 0);
      } catch {
        return 'Rp 0';
      }
    }
  }
};
</script>

<style scoped>
.order-list-item {
  transition: background-color 0.2s ease;
}
.order-list-item:hover {
  background-color: #f8f9fa;
  text-decoration: none;
}
</style>

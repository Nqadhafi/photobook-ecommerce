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
          <h2 class="mb-3">My Orders</h2>
          <p class="muted mb-4">Track your orders and continue where you left off.</p>
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
            <h4 class="mb-2"><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p class="mb-3">{{ error }}</p>
            <b-button variant="primary" @click="loadOrders" size="sm">
              <b-icon icon="arrow-repeat"></b-icon> Retry
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Empty -->
      <b-row v-else-if="orders.length === 0">
        <b-col cols="12">
          <b-card class="text-center py-5 border-0 shadow-sm soft-card">
            <b-icon icon="inbox" font-scale="2" class="text-primary-600"></b-icon>
            <h4 class="mt-3 mb-2">No Orders Found</h4>
            <p class="muted mb-4">You haven't placed any orders yet.</p>
            <b-button variant="primary" :to="{ name: 'Products' }">
              <b-icon icon="cart-plus"></b-icon> Start Shopping
            </b-button>
          </b-card>
        </b-col>
      </b-row>

      <!-- Order List -->
      <b-row v-else>
        <b-col cols="12">

          <!-- Desktop header -->
          <div class="order-header d-none d-md-grid">
            <div>Order</div>
            <div>Date</div>
            <div>Status</div>
            <div class="text-right">Total</div>
            <div></div>
          </div>

          <!-- Rows -->
          <div class="order-list">
            <router-link
              v-for="order in orders"
              :key="order.id"
              :to="{ name: 'OrderDetail', params: { id: order.id } }"
              class="order-row"
            >
              <!-- Desktop columns -->
              <div class="col-order d-none d-md-block">
                <div class="order-no">#{{ order.order_number }}</div>
              </div>
              <div class="col-date d-none d-md-block">
                <div class="date">{{ formatDate(order.created_at) }}</div>
              </div>
              <div class="col-status d-none d-md-block">
                <span class="status-chip" :class="'status-' + (order.status || 'other')">
                  {{ formatStatus(order.status) }}
                </span>
              </div>
              <div class="col-total d-none d-md-block text-right">
                <div class="total">{{ formatCurrency(order.total_amount) }}</div>
              </div>
              <div class="col-chevron d-none d-md-flex">
                <b-icon icon="chevron-right" class="chev muted"></b-icon>
              </div>

              <!-- Mobile stacked card -->
              <div class="mobile-card d-md-none">
                <div class="top">
                  <div class="order-no">#{{ order.order_number }}</div>
                  <div class="total">{{ formatCurrency(order.total_amount) }}</div>
                </div>
                <div class="mid">
                  <span class="status-chip" :class="'status-' + (order.status || 'other')">
                    {{ formatStatus(order.status) }}
                  </span>
                  <div class="date">{{ formatDate(order.created_at) }}</div>
                </div>
              </div>
            </router-link>
          </div>

          <!-- Pagination -->
          <b-pagination
            v-if="pagination && pagination.last_page > 1"
            :value="currentPage"
            :total-rows="pagination.total"
            :per-page="pagination.per_page"
            @input="onPageChange"
            align="center"
            class="mt-4"
            first-number
            last-number
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
          current_page: page,
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
    formatStatus(status) {
      const labels = {
        pending: 'Pending Payment',
        paid: 'Payment Received',
        file_upload: 'Awaiting Files',
        processing: 'In Production',
        ready: 'Ready for Pickup',
        completed: 'Completed',
        cancelled: 'Cancelled'
      };
      return labels[status] || status || '—';
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
:root { --soft: #f8fafc; }

.muted { color: #64748b; }
.text-primary-600 { color: #0ea5e9; }

.soft-card {
  border-radius: .9rem;
  box-shadow: 0 10px 24px rgba(2,132,199,.06) !important;
}

/* Desktop table-like header */
.order-header {
  display: grid;
  grid-template-columns: 240px 180px 220px 1fr 20px;
  gap: 12px;
  padding: .6rem 1rem;
  border-bottom: 1px solid #eaeef4;
  color: #475569;
  font-weight: 700;
  font-size: .9rem;
}

/* List container */
.order-list {
  display: flex;
  flex-direction: column;
}

/* Row */
.order-row {
  position: relative;
  display: grid;
  grid-template-columns: 240px 180px 220px 1fr 20px; /* match header */
  gap: 12px;
  align-items: center;
  padding: .85rem 1rem;
  border-bottom: 1px solid #eef2f7;
  background: #fff;
  text-decoration: none !important;
  transition: background-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.order-row:hover {
  background: #fbfdff;
  box-shadow: 0 6px 20px rgba(2,132,199,.06);
  transform: translateY(-1px);
}

/* Desktop cells */
.order-no { font-weight: 800; color: #0f172a; }
.date { color: #64748b; }
.total { font-weight: 800; color: #0ea5e9; }
.chev { font-size: 1.1rem; }

/* Status chip */
.status-chip {
  display: inline-flex;
  align-items: center;
  padding: .2rem .55rem;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 700;
  border: 1px solid transparent;
  white-space: nowrap;
}
.status-pending    { background: #fff7ed; color: #b45309; border-color: #fde68a; }
.status-paid       { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.status-file_upload{ background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
.status-processing { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
.status-ready      { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.status-completed  { background: #f0fdf4; color: #065f46; border-color: #bbf7d0; }
.status-cancelled  { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.status-other      { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }

/* Mobile card */
.mobile-card {
  display: grid;
  grid-template-rows: auto auto;
  gap: .35rem;
  width: 100%;
}
.mobile-card .top {
  display: flex; align-items: center; justify-content: space-between;
}
.mobile-card .mid {
  display: flex; align-items: center; justify-content: space-between; gap: .5rem;
}

/* Responsive: switch to card layout for < md */
@media (max-width: 767.98px) {
  .order-header { display: none; }
  .order-row {
    grid-template-columns: 1fr;
    padding: .85rem .9rem;
    border: 1px solid #eef2f7;
    border-radius: .9rem;
    margin-bottom: .6rem;
  }
}
</style>

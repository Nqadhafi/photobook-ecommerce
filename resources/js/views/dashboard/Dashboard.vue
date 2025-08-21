<!-- resources/js/views/dashboard/Dashboard.vue -->
<template>
  <app-layout>
    <b-container>
      <!-- Welcome -->
      <b-row class="mb-3">
        <b-col>
          <h2 class="mb-1">Hi, {{ userName }} 👋</h2>
          <p class="muted mb-0">Create beautiful photobooks with premium materials & easy templates.</p>
        </b-col>
      </b-row>

      <!-- Quick Actions -->
      <b-row class="mt-3">
        <b-col md="4" class="mb-3 mb-md-0">
          <b-card class="quick-card h-100 text-center border-0 shadow-sm">
            <div class="icon-wrap">
              <b-icon icon="images" font-scale="1.6"></b-icon>
            </div>
            <b-card-title class="mt-2 mb-1">Browse Products</b-card-title>
            <b-card-text class="muted small">
              Explore our collection of photobooks & templates.
            </b-card-text>
            <b-button variant="primary" :to="{ name: 'Products' }">
              View Products
            </b-button>
          </b-card>
        </b-col>

        <b-col md="4" class="mb-3 mb-md-0">
          <b-card class="quick-card h-100 text-center border-0 shadow-sm">
            <div class="icon-wrap success">
              <b-icon icon="cart" font-scale="1.6"></b-icon>
            </div>
            <b-card-title class="mt-2 mb-1">Your Cart</b-card-title>
            <b-card-text class="muted small">
              You have <strong>{{ cartItemCount }}</strong> {{ cartItemCount === 1 ? 'item' : 'items' }} in your cart.
            </b-card-text>
            <b-button variant="success" :to="{ name: 'Cart' }">
              View Cart
            </b-button>
          </b-card>
        </b-col>

        <b-col md="4">
          <b-card class="quick-card h-100 text-center border-0 shadow-sm">
            <div class="icon-wrap info">
              <b-icon icon="list" font-scale="1.6"></b-icon>
            </div>
            <b-card-title class="mt-2 mb-1">Order History</b-card-title>
            <b-card-text class="muted small">
              Track your previous orders & their status.
            </b-card-text>
            <b-button variant="info" :to="{ name: 'Orders' }">
              View Orders
            </b-button>
          </b-card>
        </b-col>
      </b-row>

      <!-- Recent Orders -->
      <b-row class="mt-4">
        <b-col>
          <b-card class="border-0 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <b-card-title class="mb-0">Recent Orders</b-card-title>
              <b-button size="sm" variant="outline-primary" :to="{ name: 'Orders' }">
                See all
              </b-button>
            </div>

            <!-- Loading -->
            <div v-if="loadingRecent" class="text-center py-4">
              <b-spinner variant="primary"></b-spinner>
              <div class="small muted mt-2">Loading your recent orders…</div>
            </div>

            <!-- Error -->
            <b-alert v-else-if="errorRecent" show variant="danger" class="mb-0">
              {{ errorRecent }}
              <b-button size="sm" variant="outline-light" class="ml-2" @click="loadRecentOrders">
                <b-icon icon="arrow-repeat"></b-icon> Retry
              </b-button>
            </b-alert>

            <!-- Empty -->
            <div v-else-if="!recentOrders || recentOrders.length === 0" class="text-center py-4">
              <b-icon icon="clock-history" font-scale="1.6" class="text-primary-600"></b-icon>
              <h5 class="mt-2 mb-1">No Recent Orders</h5>
              <p class="muted small mb-3">Start by choosing a photobook you like.</p>
              <b-button variant="primary" :to="{ name: 'Products' }">
                Browse Products
              </b-button>
            </div>

            <!-- Desktop table -->
            <div v-else class="d-none d-md-block">
              <b-table
                :items="recentOrders"
                :fields="orderFields"
                striped
                hover
                small
                responsive="md"
                head-variant="light"
                class="rounded overflow-hidden"
              >
                <template #cell(order_number)="ctx">
                  <strong>#{{ ctx.item.order_number }}</strong>
                </template>

                <template #cell(created_at)="ctx">
                  {{ formatDate(ctx.item.created_at) }}
                </template>

                <template #cell(status)="ctx">
                  <span class="status-chip" :class="'status-' + (ctx.item.status || 'other')">
                    {{ formatStatus(ctx.item.status) }}
                  </span>
                </template>

                <template #cell(total_amount)="ctx">
                  {{ formatCurrency(ctx.item.total_amount) }}
                </template>

                <template #cell(actions)="ctx">
                  <b-button
                    size="sm"
                    variant="outline-primary"
                    :to="{ name: 'OrderDetail', params: { id: ctx.item.id } }"
                  >
                    View
                  </b-button>
                </template>
              </b-table>
            </div>

            <!-- Mobile cards -->
            <div v-if="recentOrders && recentOrders.length" class="d-md-none">
              <div
                v-for="o in recentOrders"
                :key="o.id"
                class="order-mini-card"
                @click="$router.push({ name: 'OrderDetail', params: { id: o.id } })"
              >
                <div class="top">
                  <div class="left">
                    <div class="ord">#{{ o.order_number }}</div>
                    <div class="date">{{ formatDate(o.created_at) }}</div>
                  </div>
                  <div class="right">{{ formatCurrency(o.total_amount) }}</div>
                </div>
                <div class="bottom">
                  <span class="status-chip" :class="'status-' + (o.status || 'other')">
                    {{ formatStatus(o.status) }}
                  </span>
                  <b-icon icon="chevron-right" class="chev"></b-icon>
                </div>
              </div>
            </div>

          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import { mapGetters } from 'vuex';
import orderService from '../../services/orderService';

export default {
  name: 'Dashboard',
  data() {
    return {
      recentOrders: [],
      loadingRecent: false,
      errorRecent: null,
      orderFields: [
        { key: 'order_number', label: 'Order' },
        { key: 'created_at',   label: 'Date' },
        { key: 'status',       label: 'Status' },
        { key: 'total_amount', label: 'Total', class: 'text-right' },
        { key: 'actions',      label: '',      class: 'text-right' }
      ]
    };
  },
  computed: {
    ...mapGetters('auth', ['user']),
    ...mapGetters('cart', ['cartItemCount']),
    userName() {
      return this.user ? this.user.name : 'Guest';
    }
  },
  async created() {
    await this.loadRecentOrders();
  },
  methods: {
    async loadRecentOrders() {
      this.loadingRecent = true;
      this.errorRecent = null;
      try {
        // Ambil 5 order terakhir; sesuaikan param dengan backend kamu.
        // Banyak backend mengembalikan { data, pagination }, tapi kita handle beberapa variasi respons.
        const resp = await orderService.getOrders({ page: 1, per_page: 5, limit: 5, sort: 'created_at_desc' });

        // Prefer resp.data bila ada, fallback ke resp.orders atau resp
        const items = (resp && (resp.data || resp.orders || resp.items)) || [];
        // Jika pagination tersedia tapi data kosong, tetap aman
        this.recentOrders = Array.isArray(items) ? items.slice(0, 5) : [];
      } catch (err) {
        console.error('Failed to load recent orders:', err);
        this.errorRecent = err?.message || err?.error || 'Failed to load recent orders.';
        // Opsional: tampilkan notif global
        this.$store?.dispatch?.('showNotification', {
          title: 'Error',
          message: this.errorRecent,
          type: 'danger'
        });
      } finally {
        this.loadingRecent = false;
      }
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
        const opt = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', opt);
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

.quick-card {
  border-radius: .9rem;
  transition: transform .18s ease, box-shadow .18s ease;
}
.quick-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 32px rgba(2,132,199,.08);
}
.icon-wrap {
  width: 48px; height: 48px;
  border-radius: 999px;
  display: inline-flex; align-items: center; justify-content: center;
  background: #e0f2fe; color: #0ea5e9;
}
.icon-wrap.success { background: #dcfce7; color: #16a34a; }
.icon-wrap.info    { background: #e0e7ff; color: #4f46e5; }

.soft-card {
  border-radius: .9rem;
  box-shadow: 0 10px 24px rgba(2,132,199,.06) !important;
}

/* Mobile “recent order” cards */
.order-mini-card {
  border: 1px solid #eef2f7;
  border-radius: .9rem;
  padding: .75rem .9rem;
  margin-bottom: .6rem;
  background: #fff;
  box-shadow: 0 4px 14px rgba(15, 23, 42, .02);
  cursor: pointer;
}
.order-mini-card .top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .25rem;
}
.order-mini-card .ord { font-weight: 800; color: #0f172a; }
.order-mini-card .date { color: #64748b; font-size: .85rem; }
.order-mini-card .right { font-weight: 800; color: #0ea5e9; }
.order-mini-card .bottom {
  display: flex; align-items: center; justify-content: space-between;
}
.order-mini-card .chev { color: #94a3b8; }

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
</style>

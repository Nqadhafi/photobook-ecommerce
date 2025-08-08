<template>
  <div>
    <b-row class="mb-4">
      <b-col>
        <h2>Admin Dashboard</h2>
        <p>Welcome, {{ currentUser ? currentUser.name : 'Admin' }}!</p>
        <p v-if="isSuperAdmin"><b>You have Super Admin privileges.</b></p>
        <p v-else><b>You have Admin privileges.</b></p>
      </b-col>
    </b-row>

    <!-- Statistik Ringkas -->
    <b-row class="mb-4">
      <b-col md="3">
        <b-card bg-variant="info" text-variant="white" class="mb-2">
          <b-card-body>
            <b-card-title>{{ stats.orders_pending || 0 }}</b-card-title>
            <b-card-text>Pending Orders</b-card-text>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col md="3">
        <b-card bg-variant="success" text-variant="white" class="mb-2">
          <b-card-body>
            <b-card-title>{{ stats.orders_paid || 0 }}</b-card-title>
            <b-card-text>Paid Orders</b-card-text>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col md="3">
        <b-card bg-variant="warning" text-variant="white" class="mb-2">
          <b-card-body>
            <b-card-title>{{ stats.orders_processing || 0 }}</b-card-title>
            <b-card-text>Processing Orders</b-card-text>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Statistik Keuangan Bulan Ini -->
    <b-row class="mb-4">
      <b-col md="6">
        <b-card bg-variant="secondary" text-variant="white">
          <b-card-body>
            <b-card-title>Rp{{ formatCurrency(stats.total_amount_this_month || 0) }}</b-card-title>
            <b-card-text>Total Order Value This Month</b-card-text>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- Grafik Tren Order -->
    <b-row class="mb-4">
      <b-col md="12">
        <!-- Gunakan komponen grafik baru -->
        <b-card no-body>
          <b-card-body>
            <OrderTrendChart
              :chart-data="orderTrendChartData"
              :loaded="!loading"
            />
          </b-card-body>
        </b-card>
        <!-- Akhir komponen grafik -->
      </b-col>
    </b-row>

    <!-- Placeholder untuk bagian lain -->
    <b-row>
      <b-col md="6">
        <b-card title="Recent Orders" class="mb-4">
          <p>Daftar order terbaru bisa ditampilkan di sini (dalam pengembangan).</p>
        </b-card>
      </b-col>
      <b-col md="6">
        <b-card title="Order Status Distribution" class="mb-4">
          <p>Grafik distribusi status order bisa ditambahkan di sini (dalam pengembangan).</p>
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import orderService from '../../services/orderService';
// --- IMPOR KOMPONEN GRAFIK ---
import OrderTrendChart from '../../components/admin/charts/OrderTrendChart.vue';
// -----------------------------

export default {
  name: 'AdminDashboard',
  // --- DAFTARKAN KOMPONEN GRAFIK ---
  components: {
    OrderTrendChart
  },
  // ---------------------------------
  data() {
    return {
      stats: {
        total_orders: 0,
        orders_pending: 0,
        orders_paid: 0,
        orders_processing: 0,
        total_amount_this_month: 0,
        order_trends: [], // Tambahkan untuk menyimpan data tren
      },
      loading: false,
      error: null
    };
  },
  computed: {
    ...mapGetters('auth', ['user']),
    currentUser() {
      return this.user;
    },
    isSuperAdmin() {
      return this.user && this.user.role === 'super_admin';
    },
    // --- TAMBAHKAN COMPUTED PROPERTY UNTUK DATA GRAFIK ---
    orderTrendChartData() {
      // Format data dari API menjadi format yang dibutuhkan oleh Chart.js
      const labels = this.stats.order_trends.map(item => item.month);
      const data = this.stats.order_trends.map(item => item.count);

      return {
        labels: labels,
        datasets: [
          {
            label: 'Number of Orders',
            backgroundColor: 'rgba(52, 152, 219, 0.7)', // Biru
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 1,
            data: data,
          }
        ]
      };
    }
    // --- AKHIR COMPUTED PROPERTY ---
  },
  created() {
    this.fetchDashboardStats();
  },
  methods: {
    async fetchDashboardStats() {
      this.loading = true;
      this.error = null;
      try {
        const statsData = await orderService.getAdminDashboardStats();
        this.stats = statsData;
      } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
        this.error = 'Failed to load dashboard statistics.';
        this.$bvToast.toast(this.error, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
      } finally {
        this.loading = false;
      }
    },
    formatCurrency(value) {
      return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
  }
};
</script>

<style scoped>
/* Tambahkan style khusus jika diperlukan */
</style>
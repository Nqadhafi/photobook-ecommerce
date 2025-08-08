<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Coupon Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <b-button variant="success" :to="{ name: 'AdminCouponCreate' }">
          <b-icon icon="plus-circle" class="mr-1"></b-icon>
          Add New Coupon
        </b-button>
      </b-col>
    </b-row>

    <!-- Filter -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="4">
            <b-form-group label="Search Code:" label-for="filter-code">
              <b-form-input
                id="filter-code"
                v-model="filters.search"
                placeholder="Enter coupon code..."
                @keyup.enter="applyFilters"
              ></b-form-input>
            </b-form-group>
          </b-col>
          <b-col md="4">
            <b-form-group label="Status:" label-for="filter-status">
              <b-form-select
                id="filter-status"
                v-model="filters.is_active"
                :options="statusOptions"
                @change="applyFilters"
              >
                <template #first>
                  <b-form-select-option :value="null">All Statuses</b-form-select-option>
                </template>
              </b-form-select>
            </b-form-group>
          </b-col>
          <b-col md="4" class="d-flex align-items-end">
            <b-button variant="primary" @click="applyFilters">Apply Filters</b-button>
            <b-button variant="secondary" class="ml-2" @click="resetFilters">Reset</b-button>
          </b-col>
        </b-row>
      </b-card-body>
    </b-card>

    <!-- Tabel Coupon -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="coupons"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No coupons found."
        empty-filtered-text="No coupons match your filters."
      >
        <template #cell(code)="row">
          <router-link :to="{ name: 'AdminCouponEdit', params: { id: row.item.id } }">
            {{ row.item.code }}
          </router-link>
        </template>

        <template #cell(discount_percent)="row">
          {{ parseFloat(row.item.discount_percent).toFixed(2) }}%
        </template>

        <!-- Jika menggunakan discount_amount, uncomment bagian ini -->
        <!--
        <template #cell(discount_amount)="row">
          Rp{{ parseFloat(row.item.discount_amount).toFixed(2) }}
        </template>
        -->

        <template #cell(max_uses)="row">
          {{ row.item.max_uses !== null ? row.item.max_uses : 'Unlimited' }}
        </template>

        <template #cell(is_active)="row">
          <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
            {{ row.item.is_active ? 'Active' : 'Inactive' }}
          </b-badge>
        </template>

        <template #cell(starts_at)="row">
          {{ row.item.starts_at ? formatDate(row.item.starts_at) : 'N/A' }}
        </template>

        <template #cell(expires_at)="row">
          {{ row.item.expires_at ? formatDate(row.item.expires_at) : 'N/A' }}
        </template>

        <template #cell(created_at)="row">
          {{ formatDate(row.item.created_at) }}
        </template>

        <template #cell(actions)="row">
          <b-button
            size="sm"
            variant="primary"
            :to="{ name: 'AdminCouponEdit', params: { id: row.item.id } }"
            class="mr-1"
          >
            Edit
          </b-button>
          <b-button
            size="sm"
            variant="danger"
            @click="confirmDelete(row.item.id, row.item.code)"
          >
            Delete
          </b-button>
        </template>
      </b-table>

      <!-- Pagination -->
      <b-card-footer class="d-flex justify-content-between align-items-center">
        <div>
          <strong>Total Coupons:</strong> {{ pagination.total }}
        </div>
        <b-pagination
          v-model="pagination.currentPage"
          :total-rows="pagination.total"
          :per-page="pagination.perPage"
          @change="onPageChange"
        ></b-pagination>
      </b-card-footer>
    </b-card>

    <!-- Modal Konfirmasi Hapus -->
    <b-modal
      id="delete-coupon-modal"
      title="Confirm Delete"
      @ok="deleteCoupon"
      ok-variant="danger"
      ok-title="Delete"
    >
      <p>Are you sure you want to delete coupon <strong>{{ itemToDelete.code }}</strong>?</p>
      <p class="text-danger"><small>This action cannot be undone.</small></p>
    </b-modal>
  </div>
</template>

<script>
import couponService from '../../../services/couponService';

export default {
  name: 'AdminCouponIndex',
  data() {
    return {
      isBusy: false,
      coupons: [],
      fields: [
        { key: 'code', label: 'Code', sortable: true },
        { key: 'discount_percent', label: 'Discount %', sortable: true },
        // { key: 'discount_amount', label: 'Discount Amount', sortable: true }, // Jika menggunakan discount_amount
        { key: 'max_uses', label: 'Max Uses', sortable: true },
        { key: 'is_active', label: 'Status', sortable: true },
        { key: 'starts_at', label: 'Starts At', sortable: true },
        { key: 'expires_at', label: 'Expires At', sortable: true },
        { key: 'created_at', label: 'Created At', sortable: true },
        { key: 'actions', label: 'Actions' }
      ],
      statusOptions: [
        { value: 1, text: 'Active' },
        { value: 0, text: 'Inactive' },
      ],
      filters: {
        search: '',
        is_active: null, // null = all, 1 = active, 0 = inactive
      },
      pagination: {
        currentPage: 1,
        perPage: 15,
        total: 0,
      },
      itemToDelete: {
        id: null,
        code: ''
      }
    };
  },
  created() {
    this.fetchCoupons();
  },
  methods: {
    async fetchCoupons(page = 1) {
      this.isBusy = true;
      try {
        const params = {
          page: page,
          per_page: this.pagination.perPage,
          search: this.filters.search,
          is_active: this.filters.is_active, // null, 1, atau 0
        };

        const data = await couponService.getAdminCoupons(params);

        this.coupons = data.data;
        this.pagination.total = data.total;
        this.pagination.currentPage = data.current_page;

      } catch (error) {
        console.error('Failed to fetch admin coupons:', error);
        const errorMessage = error.error || 'Failed to load coupons. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.coupons = [];
      } finally {
        this.isBusy = false;
      }
    },

    onPageChange(page) {
      this.fetchCoupons(page);
    },

    applyFilters() {
      this.pagination.currentPage = 1;
      this.fetchCoupons(1);
    },

    resetFilters() {
      this.filters.search = '';
      this.filters.is_active = null;
      this.pagination.currentPage = 1;
      this.fetchCoupons(1);
    },

    confirmDelete(id, code) {
      this.itemToDelete.id = id;
      this.itemToDelete.code = code;
      this.$bvModal.show('delete-coupon-modal');
    },

    async deleteCoupon(bvModalEvt) {
      bvModalEvt.preventDefault();

      try {
        await couponService.deleteAdminCoupon(this.itemToDelete.id);
        this.$bvToast.toast(`Coupon '${this.itemToDelete.code}' deleted successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        this.fetchCoupons(this.pagination.currentPage);
        this.$bvModal.hide('delete-coupon-modal');
      } catch (error) {
        console.error('Failed to delete coupon:', error);
        const errorMessage = error.error || 'Failed to delete coupon. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.$bvModal.hide('delete-coupon-modal');
      }
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
<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Product Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <b-button variant="success" :to="{ name: 'AdminProductCreate' }">
          <b-icon icon="plus-circle" class="mr-1"></b-icon>
          Add New Product
        </b-button>
      </b-col>
    </b-row>

    <!-- Filter dan Pencarian -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="6">
            <b-form-group label="Search Product:" label-for="filter-product">
              <b-form-input
                id="filter-product"
                v-model="filters.search"
                placeholder="Enter product name..."
                @keyup.enter="applyFilters"
              ></b-form-input>
            </b-form-group>
          </b-col>
          <b-col md="6" class="d-flex align-items-end">
            <b-button variant="primary" @click="applyFilters">Apply Filters</b-button>
            <b-button variant="secondary" class="ml-2" @click="resetFilters">Reset</b-button>
          </b-col>
        </b-row>
      </b-card-body>
    </b-card>

    <!-- Tabel Produk -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="products"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No products found."
        empty-filtered-text="No products match your filters."
      >
        <template #cell(name)="row">
          <router-link :to="{ name: 'AdminProductEdit', params: { id: row.item.id } }">
            {{ row.item.name }}
          </router-link>
        </template>

        <template #cell(thumbnail)="row">
          <b-img
            v-if="row.item.thumbnail"
            :src="getThumbnailUrl(row.item.thumbnail)"
            :alt="row.item.name"
            thumbnail
            fluid
            style="max-height: 50px; width: auto;"
          ></b-img>
          <span v-else>No Image</span>
        </template>

        <template #cell(price)="row">
          Rp{{ parseFloat(row.item.price).toFixed(2) }}
        </template>

        <template #cell(is_active)="row">
          <b-badge :variant="row.item.is_active ? 'success' : 'secondary'">
            {{ row.item.is_active ? 'Active' : 'Inactive' }}
          </b-badge>
        </template>

        <template #cell(created_at)="row">
          {{ formatDate(row.item.created_at) }}
        </template>

        <template #cell(actions)="row">
          <b-button
            size="sm"
            variant="primary"
            :to="{ name: 'AdminProductEdit', params: { id: row.item.id } }"
            class="mr-1"
          >
            Edit
          </b-button>
          <b-button
            size="sm"
            variant="danger"
            @click="confirmDelete(row.item.id, row.item.name)"
          >
            Delete
          </b-button>
        </template>
      </b-table>

      <!-- Pagination -->
      <b-card-footer class="d-flex justify-content-between align-items-center">
        <div>
          <strong>Total Products:</strong> {{ pagination.total }}
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
      id="delete-product-modal"
      title="Confirm Delete"
      @ok="deleteProduct"
      ok-variant="danger"
      ok-title="Delete"
    >
      <p>Are you sure you want to delete product <strong>{{ itemToDelete.name }}</strong>?</p>
      <p class="text-danger"><small>This action cannot be undone.</small></p>
    </b-modal>
  </div>
</template>

<script>
import productService from '../../../services/productService';

export default {
  name: 'AdminProductIndex',
  data() {
    return {
      isBusy: false,
      products: [],
      fields: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'thumbnail', label: 'Thumbnail' },
        { key: 'price', label: 'Price', sortable: true },
        { key: 'is_active', label: 'Status', sortable: true },
        { key: 'created_at', label: 'Created At', sortable: true },
        { key: 'actions', label: 'Actions' }
      ],
      filters: {
        search: '',
        // Tambahkan filter lain jika diperlukan
      },
      pagination: {
        currentPage: 1,
        perPage: 15, // Harus sesuai dengan default backend
        total: 0,
      },
      itemToDelete: {
        id: null,
        name: ''
      }
    };
  },
  created() {
    this.fetchProducts();
  },
  methods: {
    async fetchProducts(page = 1) {
      this.isBusy = true;
      try {
        const params = {
          page: page,
          per_page: this.pagination.perPage,
          search: this.filters.search,
          // Tambahkan parameter filter lainnya
        };

        const data = await productService.getAdminProducts(params);

        this.products = data.data; // Data dari pagination Laravel
        this.pagination.total = data.total;
        this.pagination.currentPage = data.current_page;

      } catch (error) {
        console.error('Failed to fetch products:', error);
        const errorMessage = error.error || 'Failed to load products. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.products = [];
      } finally {
        this.isBusy = false;
      }
    },

    onPageChange(page) {
      this.fetchProducts(page);
    },

    applyFilters() {
      this.pagination.currentPage = 1;
      this.fetchProducts(1);
    },

    resetFilters() {
      this.filters.search = '';
      this.pagination.currentPage = 1;
      this.fetchProducts(1);
    },

    confirmDelete(id, name) {
      this.itemToDelete.id = id;
      this.itemToDelete.name = name;
      this.$bvModal.show('delete-product-modal');
    },

    async deleteProduct(bvModalEvt) {
      // Mencegah modal menutup otomatis jika terjadi error
      bvModalEvt.preventDefault();

      try {
        await productService.deleteAdminProduct(this.itemToDelete.id);
        this.$bvToast.toast(`Product '${this.itemToDelete.name}' deleted successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        // Refresh list setelah hapus
        this.fetchProducts(this.pagination.currentPage);
        // Sembunyikan modal
        this.$bvModal.hide('delete-product-modal');
      } catch (error) {
        console.error('Failed to delete product:', error);
        const errorMessage = error.error || 'Failed to delete product. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        // Sembunyikan modal jika error juga
        this.$bvModal.hide('delete-product-modal');
      }
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleString('id-ID');
    },

    getThumbnailUrl(thumbnailPath) {
      // Asumsi thumbnail disimpan di storage/app/public dan diakses via /storage
      // Laravel biasanya membuat symlink storage ke public/storage
      // Jika menggunakan Cloudflare R2 atau lainnya, URL akan berbeda
      return `/storage/${thumbnailPath}`;
    }

  }
};
</script>

<style scoped>
/* Tambahkan style khusus jika diperlukan */
</style>
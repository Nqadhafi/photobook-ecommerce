<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Deskprint Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <b-button variant="success" :to="{ name: 'AdminDeskprintCreate' }">
          <b-icon icon="plus-circle" class="mr-1"></b-icon>
          Add New Deskprint
        </b-button>
      </b-col>
    </b-row>

    <!-- Filter dan Pencarian -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="6">
            <b-form-group label="Search Deskprint:" label-for="filter-deskprint">
              <b-form-input
                id="filter-deskprint"
                v-model="filters.search"
                placeholder="Enter name or location..."
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

    <!-- Tabel Deskprint -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="deskprints"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No deskprints found."
        empty-filtered-text="No deskprints match your filters."
      >
        <template #cell(name)="row">
          <router-link :to="{ name: 'AdminDeskprintEdit', params: { id: row.item.id } }">
            {{ row.item.name }}
          </router-link>
        </template>

        <template #cell(contact_number)="row">
          {{ row.item.contact_number }}
        </template>

        <template #cell(location)="row">
          {{ row.item.location }}
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
            :to="{ name: 'AdminDeskprintEdit', params: { id: row.item.id } }"
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
          <strong>Total Deskprints:</strong> {{ pagination.total }}
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
      id="delete-deskprint-modal"
      title="Confirm Delete"
      @ok="deleteDeskprint"
      ok-variant="danger"
      ok-title="Delete"
    >
      <p>Are you sure you want to delete deskprint <strong>{{ itemToDelete.name }}</strong>?</p>
      <p class="text-danger"><small>This action cannot be undone.</small></p>
    </b-modal>
  </div>
</template>

<script>
import deskprintService from '../../../services/deskprintService';

export default {
  name: 'AdminDeskprintIndex',
  data() {
    return {
      isBusy: false,
      deskprints: [],
      fields: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'contact_number', label: 'Contact Number', sortable: true },
        { key: 'location', label: 'Location', sortable: true },
        { key: 'is_active', label: 'Status', sortable: true },
        { key: 'created_at', label: 'Created At', sortable: true },
        { key: 'actions', label: 'Actions' }
      ],
      filters: {
        search: '',
      },
      pagination: {
        currentPage: 1,
        perPage: 15,
        total: 0,
      },
      itemToDelete: {
        id: null,
        name: ''
      }
    };
  },
  created() {
    this.fetchDeskprints();
  },
  methods: {
    async fetchDeskprints(page = 1) {
      this.isBusy = true;
      try {
        const params = {
          page: page,
          per_page: this.pagination.perPage,
          search: this.filters.search,
        };

        const data = await deskprintService.getAdminDeskprints(params);

        this.deskprints = data.data;
        this.pagination.total = data.total;
        this.pagination.currentPage = data.current_page;

      } catch (error) {
        console.error('Failed to fetch admin deskprints:', error);
        const errorMessage = error.error || 'Failed to load deskprints. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.deskprints = [];
      } finally {
        this.isBusy = false;
      }
    },

    onPageChange(page) {
      this.fetchDeskprints(page);
    },

    applyFilters() {
      this.pagination.currentPage = 1;
      this.fetchDeskprints(1);
    },

    resetFilters() {
      this.filters.search = '';
      this.pagination.currentPage = 1;
      this.fetchDeskprints(1);
    },

    confirmDelete(id, name) {
      this.itemToDelete.id = id;
      this.itemToDelete.name = name;
      this.$bvModal.show('delete-deskprint-modal');
    },

    async deleteDeskprint(bvModalEvt) {
      bvModalEvt.preventDefault();

      try {
        await deskprintService.deleteAdminDeskprint(this.itemToDelete.id);
        this.$bvToast.toast(`Deskprint '${this.itemToDelete.name}' deleted successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        this.fetchDeskprints(this.pagination.currentPage);
        this.$bvModal.hide('delete-deskprint-modal');
      } catch (error) {
        console.error('Failed to delete deskprint:', error);
        const errorMessage = error.error || 'Failed to delete deskprint. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.$bvModal.hide('delete-deskprint-modal');
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
<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Admin User Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <b-button variant="success" :to="{ name: 'AdminUserCreate' }">
          <b-icon icon="plus-circle" class="mr-1"></b-icon>
          Add New Admin
        </b-button>
      </b-col>
    </b-row>

    <!-- Filter dan Pencarian -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="6">
            <b-form-group label="Search Admin:" label-for="filter-user">
              <b-form-input
                id="filter-user"
                v-model="filters.search"
                placeholder="Enter name or email..."
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

    <!-- Tabel User Admin -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="users"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No admin users found."
        empty-filtered-text="No admin users match your filters."
      >
        <template #cell(name)="row">
          <router-link :to="{ name: 'AdminUserEdit', params: { id: row.item.id } }">
            {{ row.item.name }}
          </router-link>
        </template>

        <template #cell(email)="row">
          {{ row.item.email }}
        </template>

        <template #cell(created_at)="row">
          {{ formatDate(row.item.created_at) }}
        </template>

        <template #cell(actions)="row">
          <b-button
            size="sm"
            variant="primary"
            :to="{ name: 'AdminUserEdit', params: { id: row.item.id } }"
            class="mr-1"
          >
            Edit
          </b-button>
          <!-- Pindahkan komentar ke luar atau hapus -->
          <b-button
            v-if="row.item.id !== currentUserId"
            size="sm"
            variant="danger"
            @click="confirmDelete(row.item.id, row.item.name)"
          >
            Delete
          </b-button>
          <b-button
            v-else
            size="sm"
            variant="secondary"
            disabled
          >
            Delete (You)
          </b-button>
        </template>
      </b-table>

      <!-- Pagination -->
      <b-card-footer class="d-flex justify-content-between align-items-center">
        <div>
          <strong>Total Admins:</strong> {{ pagination.total }}
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
      id="delete-user-modal"
      title="Confirm Delete"
      @ok="deleteUser"
      ok-variant="danger"
      ok-title="Delete"
    >
      <p>Are you sure you want to delete admin user <strong>{{ itemToDelete.name }}</strong>?</p>
      <p class="text-danger"><small>This action cannot be undone.</small></p>
    </b-modal>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'; // Untuk mendapatkan ID user yang sedang login
import userService from '../../../services/userService';

export default {
  name: 'AdminUserIndex',
  data() {
    return {
      isBusy: false,
      users: [],
      fields: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
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
  computed: {
    // Dapatkan ID user yang sedang login dari Vuex store
    // Asumsi ada module 'auth' dengan getter 'user'
    ...mapGetters('auth', ['user']),
    currentUserId() {
      return this.user ? this.user.id : null;
    }
  },
  created() {
    this.fetchUsers();
  },
  methods: {
    async fetchUsers(page = 1) {
      this.isBusy = true;
      try {
        const params = {
          page: page,
          per_page: this.pagination.perPage,
          search: this.filters.search,
        };

        const data = await userService.getAdminUsers(params);

        this.users = data.data;
        this.pagination.total = data.total;
        this.pagination.currentPage = data.current_page;

      } catch (error) {
        console.error('Failed to fetch admin users:', error);
        const errorMessage = error.error || 'Failed to load admin users. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.users = [];
      } finally {
        this.isBusy = false;
      }
    },

    onPageChange(page) {
      this.fetchUsers(page);
    },

    applyFilters() {
      this.pagination.currentPage = 1;
      this.fetchUsers(1);
    },

    resetFilters() {
      this.filters.search = '';
      this.pagination.currentPage = 1;
      this.fetchUsers(1);
    },

    confirmDelete(id, name) {
      this.itemToDelete.id = id;
      this.itemToDelete.name = name;
      this.$bvModal.show('delete-user-modal');
    },

    async deleteUser(bvModalEvt) {
      bvModalEvt.preventDefault();

      try {
        await userService.deleteAdminUser(this.itemToDelete.id);
        this.$bvToast.toast(`Admin user '${this.itemToDelete.name}' deleted successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        this.fetchUsers(this.pagination.currentPage);
        this.$bvModal.hide('delete-user-modal');
      } catch (error) {
        console.error('Failed to delete admin user:', error);
        const errorMessage = error.error || 'Failed to delete admin user. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.$bvModal.hide('delete-user-modal');
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
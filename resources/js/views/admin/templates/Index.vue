<template>
  <div>
    <b-row class="mb-3">
      <b-col md="6">
        <h2>Template Management</h2>
      </b-col>
      <b-col md="6" class="text-md-right">
        <b-button variant="success" :to="{ name: 'AdminTemplateCreate' }">
          <b-icon icon="plus-circle" class="mr-1"></b-icon>
          Add New Template
        </b-button>
      </b-col>
    </b-row>

    <!-- Filter dan Pencarian -->
    <b-card no-body class="mb-4">
      <b-card-body>
        <b-row>
          <b-col md="6">
            <b-form-group label="Search Template:" label-for="filter-template">
              <b-form-input
                id="filter-template"
                v-model="filters.search"
                placeholder="Enter template name..."
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

    <!-- Tabel Template -->
    <b-card no-body>
      <b-table
        striped
        hover
        responsive
        :items="templates"
        :fields="fields"
        :busy="isBusy"
        show-empty
        empty-text="No templates found."
        empty-filtered-text="No templates match your filters."
      >
        <template #cell(name)="row">
          <router-link :to="{ name: 'AdminTemplateEdit', params: { id: row.item.id } }">
            {{ row.item.name }}
          </router-link>
        </template>

        <template #cell(product_name)="row">
          {{ row.item.product ? row.item.product.name : 'N/A' }}
        </template>

        <template #cell(layout_type)="row">
          {{ row.item.layout_data && row.item.layout_data.layout_type ? row.item.layout_data.layout_type : 'N/A' }}
        </template>

        <template #cell(pages)="row">
          {{ row.item.layout_data && row.item.layout_data.pages ? row.item.layout_data.pages : 'N/A' }}
        </template>

        <template #cell(photo_slots)="row">
          {{ row.item.layout_data && row.item.layout_data.photo_slots ? row.item.layout_data.photo_slots : 'N/A' }}
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
            :to="{ name: 'AdminTemplateEdit', params: { id: row.item.id } }"
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
          <strong>Total Templates:</strong> {{ pagination.total }}
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
      id="delete-template-modal"
      title="Confirm Delete"
      @ok="deleteTemplate"
      ok-variant="danger"
      ok-title="Delete"
    >
      <p>Are you sure you want to delete template <strong>{{ itemToDelete.name }}</strong>?</p>
      <p class="text-danger"><small>This action cannot be undone.</small></p>
    </b-modal>
  </div>
</template>

<script>
import templateService from '../../../services/templateService';

export default {
  name: 'AdminTemplateIndex',
  data() {
    return {
      isBusy: false,
      templates: [],
      fields: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'product_name', label: 'Product', sortable: true },
        { key: 'layout_type', label: 'Layout Type', sortable: false },
        { key: 'pages', label: 'Pages', sortable: true },
        { key: 'photo_slots', label: 'Photo Slots', sortable: true },
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
    this.fetchTemplates();
  },
  methods: {
    async fetchTemplates(page = 1) {
      this.isBusy = true;
      try {
        const params = {
          page: page,
          per_page: this.pagination.perPage,
          search: this.filters.search,
        };

        const data = await templateService.getAdminTemplates(params);

        this.templates = data.data;
        this.pagination.total = data.total;
        this.pagination.currentPage = data.current_page;

      } catch (error) {
        console.error('Failed to fetch admin templates:', error);
        const errorMessage = error.error || 'Failed to load templates. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.templates = [];
      } finally {
        this.isBusy = false;
      }
    },

    onPageChange(page) {
      this.fetchTemplates(page);
    },

    applyFilters() {
      this.pagination.currentPage = 1;
      this.fetchTemplates(1);
    },

    resetFilters() {
      this.filters.search = '';
      this.pagination.currentPage = 1;
      this.fetchTemplates(1);
    },

    confirmDelete(id, name) {
      this.itemToDelete.id = id;
      this.itemToDelete.name = name;
      this.$bvModal.show('delete-template-modal');
    },

    async deleteTemplate(bvModalEvt) {
      bvModalEvt.preventDefault();

      try {
        await templateService.deleteAdminTemplate(this.itemToDelete.id);
        this.$bvToast.toast(`Template '${this.itemToDelete.name}' deleted successfully.`, {
          title: 'Success',
          variant: 'success',
          solid: true
        });
        this.fetchTemplates(this.pagination.currentPage);
        this.$bvModal.hide('delete-template-modal');
      } catch (error) {
        console.error('Failed to delete template:', error);
        const errorMessage = error.error || 'Failed to delete template. Please try again.';
        this.$bvToast.toast(errorMessage, {
          title: 'Error',
          variant: 'danger',
          solid: true
        });
        this.$bvModal.hide('delete-template-modal');
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

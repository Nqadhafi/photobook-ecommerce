<template>
  <app-layout>
    <b-container>
      <!-- Header -->
      <b-row class="mb-4">
        <b-col>
          <h1>Our Photobook Collection</h1>
          <p class="text-muted">Discover our premium photobook products</p>
        </b-col>
      </b-row>

      <!-- Loading State -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading our amazing products...</p>
        </b-col>
      </b-row>

      <!-- Error State -->
      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button variant="primary" @click="loadProducts">Try Again</b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Products Grid -->
      <b-row v-else>
        <b-col cols="12" class="mb-4">
          <b-card>
            <b-row class="align-items-center">
              <b-col md="6">
                <b-form-input
                  v-model="searchQuery"
                  type="search"
                  placeholder="Search products..."
                  @input="onSearch"
                ></b-form-input>
              </b-col>
              <b-col md="6" class="text-md-right mt-2 mt-md-0">
                <b-form-select
                  v-model="sortBy"
                  :options="sortOptions"
                  @change="onSortChange"
                ></b-form-select>
              </b-col>
            </b-row>
          </b-card>
        </b-col>

        <b-col v-if="products.length === 0" cols="12" class="text-center py-5">
          <b-icon icon="images" font-scale="3" variant="secondary"></b-icon>
          <h4 class="mt-3">No products found</h4>
          <p>Try adjusting your search or filter criteria</p>
        </b-col>

        <b-col md="4" v-for="product in products" :key="product.id" class="mb-4">
          <b-card class="h-100 product-card shadow-sm" no-body>
            <div class="position-relative">
              <b-card-img 
                :src="getProductImage(product)" 
                :alt="product.name"
                top
                class="product-image"
                @error="onImageError"
              ></b-card-img>
              <b-badge v-if="!product.is_active" variant="danger" class="position-absolute" style="top: 10px; right: 10px;">
                Inactive
              </b-badge>
            </div>
            
            <b-card-body class="d-flex flex-column">
              <b-card-title class="mb-2">{{ product.name }}</b-card-title>
              <b-card-text class="text-muted small mb-3">
                {{ truncateText(product.description, 100) }}
              </b-card-text>
              
              <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="text-primary mb-0">Rp {{ formatCurrency(product.price) }}</h5>
                  <b-badge v-if="product.templates && product.templates.length > 0" variant="info">
                    {{ product.templates.length }} Templates
                  </b-badge>
                </div>
                
                <b-button 
                  variant="primary" 
                  block
                  :to="{ name: 'ProductDetail', params: { id: product.id } }"
                >
                  <b-icon icon="eye"></b-icon> View Details
                </b-button>
              </div>
            </b-card-body>
          </b-card>
        </b-col>
      </b-row>

      <!-- Pagination -->
      <b-row v-if="pagination && pagination.last_page > 1">
        <b-col cols="12" class="d-flex justify-content-center">
          <b-pagination
            v-model="currentPage"
            :total-rows="pagination.total"
            :per-page="pagination.per_page"
            @change="onPageChange"
            first-number
            last-number
          ></b-pagination>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import productService from '../../services/productService';

export default {
  name: 'ProductList',
  data() {
    return {
      products: [],
      pagination: null,
      currentPage: 1,
      loading: false,
      error: null,
      searchQuery: '',
      sortBy: 'name',
      sortOptions: [
        { value: 'name', text: 'Sort by Name' },
        { value: 'price', text: 'Sort by Price' },
        { value: 'created_at', text: 'Sort by Newest' }
      ],
      searchTimeout: null
    };
  },
  async created() {
    await this.loadProducts();
  },
  methods: {
    async loadProducts(page = 1, search = '', sort = 'name') {
      this.loading = true;
      this.error = null;
      
      try {
        const params = {
          page: page,
          search: search,
          sort: sort
        };
        
        const response = await productService.getProducts(params);
        this.products = response.data || [];
        this.pagination = response.pagination || null;
        this.currentPage = page;
      } catch (error) {
        console.error('Failed to load products:', error);
        this.error = error.message || 'Failed to load products';
      } finally {
        this.loading = false;
      }
    },
    
    onPageChange(page) {
      this.loadProducts(page, this.searchQuery, this.sortBy);
    },
    
    onSearch() {
      // Debounce search
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadProducts(1, this.searchQuery, this.sortBy);
      }, 500);
    },
    
    onSortChange() {
      this.loadProducts(1, this.searchQuery, this.sortBy);
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID').format(amount);
    },
    
    truncateText(text, length) {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    },
    
    getProductImage(product) {
      if (product.thumbnail) {
        // Handle both relative and absolute paths
        if (product.thumbnail.startsWith('http')) {
          return product.thumbnail;
        }
        return product.thumbnail;
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    
    onImageError(event) {
      event.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    }
  }
};
</script>

<style scoped>
.product-card {
  transition: all 0.3s ease;
  border: none;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.product-image {
  height: 200px;
  object-fit: cover;
}

.badge {
  font-size: 0.75rem;
}
</style>
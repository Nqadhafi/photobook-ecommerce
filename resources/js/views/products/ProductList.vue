<template>
  <app-layout>
    <b-container>

      <!-- ========== 3 CARA MUDAH ORDER (TOP SECTION) ========== -->
      <section class="howto-section my-3">
        <b-row class="no-gutters">
          <b-col cols="12" class="mb-2">
            <h2 class="howto-title text-center">3 Cara Mudah Order Photobook Online</h2>
          </b-col>

          <b-col sm="12" md="4" class="mb-3">
            <div class="howto-card">
              <div class="howto-icon">
                <b-icon icon="cart"></b-icon>
              </div>
              <div class="howto-text">
                <div class="howto-step">1. Pilih Produk</div>
                <div class="howto-desc">Pilih photobook & template favoritmu.</div>
              </div>
            </div>
          </b-col>

          <b-col sm="12" md="4" class="mb-3">
            <div class="howto-card">
              <div class="howto-icon">
                <b-icon icon="credit-card"></b-icon>
              </div>
              <div class="howto-text">
                <div class="howto-step">2. Pembayaran</div>
                <div class="howto-desc">Lakukan pemesanan & bayar pesanan.</div>
              </div>
            </div>
          </b-col>

          <b-col sm="12" md="4" class="mb-3">
            <div class="howto-card">
              <div class="howto-icon">
                <b-icon icon="cloud-upload"></b-icon>
              </div>
              <div class="howto-text">
                <div class="howto-step">3. Upload Desain</div>
                <div class="howto-desc">Unggah file desain ke link Gdrive yang dikirim.</div>
              </div>
            </div>
          </b-col>
        </b-row>
      </section>
      <!-- ========== /HOWTO ========== -->

      <!-- Header (desktop only) -->
      <b-row class="mb-3 d-none d-md-flex">
        <b-col>
          <h1>Our Photobook Collection</h1>
          <p class="text-muted" v-if="!searchQuery">Discover our premium photobook products</p>
          <p class="text-muted" v-else>Search results for: "{{ searchQuery }}"</p>
        </b-col>
      </b-row>

      <!-- Mobile Toolbar -->
      <div class="mobile-toolbar d-md-none sticky-top bg-white py-2">
        <div class="d-flex align-items-center justify-content-between">
          <div class="toolbar-pill">
            <b-form-select v-model="sortBy" :options="sortOptions" @change="onSortChange" size="sm"></b-form-select>
          </div>
          <b-button size="sm" variant="outline-primary" @click="showFilter = !showFilter">
            <b-icon icon="sliders"></b-icon> Filter
          </b-button>
        </div>
        <b-collapse v-model="showFilter" class="mt-2">
          <div class="d-flex">
            <b-form-input v-model="priceMin" type="number" placeholder="Min (Rp)" @input="onFilterChange" class="mr-1" size="sm"/>
            <b-form-input v-model="priceMax" type="number" placeholder="Max (Rp)" @input="onFilterChange" size="sm"/>
          </div>
        </b-collapse>
      </div>

      <!-- Desktop Filter Bar -->
      <b-row class="mb-3 d-none d-md-flex">
        <b-col>
          <b-card>
            <b-row class="align-items-center">
              <b-col md="6">
                <b-form-group label="Filter Harga:" label-cols="4" label-size="sm" class="mb-0">
                  <div class="d-flex">
                    <b-form-input v-model="priceMin" type="number" placeholder="Min (Rp)" @input="onFilterChange" class="mr-2" size="sm"/>
                    <b-form-input v-model="priceMax" type="number" placeholder="Max (Rp)" @input="onFilterChange" size="sm"/>
                  </div>
                </b-form-group>
              </b-col>
              <b-col md="6" class="text-md-right mt-2 mt-md-0">
                <b-form-select v-model="sortBy" :options="sortOptions" @change="onSortChange" size="sm" class="w-auto d-inline-block"></b-form-select>
              </b-col>
            </b-row>
          </b-card>
        </b-col>
      </b-row>

      <!-- Loading -->
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading our amazing products...</p>
        </b-col>
      </b-row>

      <!-- Error -->
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
        <b-col v-if="products.length === 0" cols="12" class="text-center py-5">
          <b-icon icon="images" font-scale="3" variant="secondary"></b-icon>
          <h4 class="mt-3">No products found</h4>
          <p>Try adjusting your search or filter criteria</p>
        </b-col>

        <!-- Card ala Shopee: 2 kolom mobile, 3 md, 4 lg -->
        <b-col
          cols="6"
          sm="6"
          md="4"
          lg="3"
          v-for="product in products"
          :key="product.id"
          class="mb-3"
        >
          <b-card class="product-card shopee no-border shadow-none" no-body>
            <!-- Square image -->
            <div class="img-square">
              <img
                :src="getProductImage(product)"
                :alt="product.name"
                class="img-fill"
                @error="onImageError"
              />
            </div>

            <div class="p-2 product-body">
              <!-- Name clamp 2 lines -->
              <div class="prod-name">{{ product.name }}</div>

              <!-- Price strong -->
              <div class="prod-price">Rp {{ formatCurrency(product.price) }}</div>

              <!-- Meta row -->
              <div class="prod-meta">
                <span v-if="product.total_sold > 0" class="sold">Terjual {{ formatCurrency(product.total_sold) }}</span>
                <span v-else>-</span>
                <span v-if="product.templates && product.templates.length > 0" class="tmpl">
                  {{ product.templates.length }} template
                </span>
                
                
              </div>

              <b-button
                variant="primary"
                size="sm"
                block
                :to="{ name: 'ProductDetail', params: { id: product.id } }"
              >
                <b-icon icon="eye"></b-icon> Detail
              </b-button>
            </div>
          </b-card>
        </b-col>
      </b-row>

      <!-- Pagination -->
      <b-row v-if="pagination && pagination.last_page > 1" class="mt-3">
        <b-col cols="12" class="d-flex justify-content-center">
          <b-pagination
            v-model="currentPage"
            :total-rows="pagination.total"
            :per-page="pagination.per_page"
            @change="onPageChange"
            first-number
            last-number
            size="sm"
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
      priceMin: '',
      priceMax: '',
      searchQuery: '',
      sortBy: 'created_at',
      sortOptions: [
        { value: 'price', text: 'Harga: Terendah' },
        { value: 'price_desc', text: 'Harga: Tertinggi' },
        { value: 'created_at', text: 'Terbaru' },
        { value: 'best_selling', text: 'Terlaris' }
      ],
      filterTimeout: null,
      showFilter: false
    };
  },
  async created() {
    this.searchQuery = this.$route.query.search || '';
    await this.loadProducts(
      this.$route.query.page || 1,
      this.$route.query.sort || 'name',
      this.$route.query.price_min || '',
      this.$route.query.price_max || '',
      this.searchQuery
    );
  },
  watch: {
    '$route.query': {
      handler(newQuery) {
        this.searchQuery = newQuery.search || '';
        this.loadProducts(
          newQuery.page || 1,
          newQuery.sort || 'name',
          newQuery.price_min || '',
          newQuery.price_max || '',
          this.searchQuery
        );
      },
      immediate: true
    }
  },
  methods: {
    async loadProducts(page = 1, sort = 'name', priceMin = '', priceMax = '', search = '') {
      this.loading = true;
      this.error = null;
      try {
        const params = {
          page,
          sort,
          price_min: priceMin,
          price_max: priceMax,
          search
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
      this.loadProducts(page, this.sortBy, this.priceMin, this.priceMax, this.searchQuery);
    },
    onFilterChange() {
      clearTimeout(this.filterTimeout);
      this.filterTimeout = setTimeout(() => {
        this.loadProducts(1, this.sortBy, this.priceMin, this.priceMax, this.searchQuery);
      }, 400);
    },
    onSortChange() {
      this.loadProducts(1, this.sortBy, this.priceMin, this.priceMax, this.searchQuery);
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID').format(amount || 0);
    },
    truncateText(text, length) {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    },
    getProductImage(product) {
      if (product.thumbnail) {
        if (product.thumbnail.startsWith('http')) return product.thumbnail;
        return '/storage/' + product.thumbnail;
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
/* --- HOWTO (3 langkah) --- */
.howto-title{
  font-size: 2.15rem;
  font-weight: 700;
  margin-bottom: .25rem;
}
.howto-card{
  display: flex;
  align-items: center;
  gap: .75rem;
  background: #ffffff;
  border: 1px solid rgba(0,0,0,.06);
  border-radius: .75rem;
  padding: .75rem .9rem;
  box-shadow: 0 2px 10px rgba(0,0,0,.03);
}
.howto-icon{
  width: 42px; height: 42px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(14,165,233,.15), rgba(59,130,246,.15));
  color: #0ea5e9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}
.howto-step{
  font-weight: 700;
  line-height: 1.2;
}
.howto-desc{
  margin-top: 2px;
  font-size: .85rem;
  color: #64748b;
}

/* --- Mobile toolbar --- */
.mobile-toolbar { z-index: 10; border-bottom: 1px solid rgba(0,0,0,.06); }
.toolbar-pill { flex: 1; margin-right: .5rem; }

/* --- Card ala Shopee --- */
.product-card.shopee {
  border-radius: .5rem;
  border: 1px solid rgba(0,0,0,.06);
  background: #fff;
  transition: box-shadow .18s ease, transform .18s ease;
}
.product-card.shopee:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,.08);
}
/* Square image wrapper (1:1) */
.img-square { position: relative; width: 100%; padding-top: 100%; overflow: hidden; border-top-left-radius: .5rem; border-top-right-radius: .5rem; background: #f5f7fa; }
.img-square .img-fill { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
/* Body compact */
.product-body { padding: .5rem .5rem .75rem !important; }
.prod-name {
  font-size: .9rem; line-height: 1.25rem; height: 2.5rem;
  overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  margin-bottom: .35rem;
}
.prod-price { color: #0d6efd; font-weight: 700; font-size: 1rem; margin-bottom: .25rem; }
.prod-meta { display: flex; justify-content: space-between; color: #6b7280; font-size: .75rem; margin-bottom: .4rem; }
.prod-meta .sold { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-meta .tmpl { background: #eef6ff; color: #1d4ed8; border-radius: 999px; padding: 0 .4rem; }

/* Legacy card tweak (harmless) */
.product-card { border: none; }
.product-image { height: 200px; object-fit: cover; }
</style>

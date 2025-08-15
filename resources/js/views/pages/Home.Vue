<template>
  <div class="home-page">
    <!-- Navbar -->
    <Navbar />
    
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center">
      <b-container>
        <b-row class="align-items-center">
          <b-col md="6">
            <h1 class="display-4 font-weight-bold text-white">Buat Photobook Impianmu</h1>
            <p class="lead text-light">
              Cetak kenangan terbaikmu dalam photobook premium dengan desain stylish dan kekinian.
            </p>
            <b-button variant="light" size="lg" :to="{ name: 'Products' }" class="mt-3">
              <b-icon icon="images"></b-icon> Jelajahi Produk
            </b-button>
            <b-button
              v-if="!$store.getters['auth/isAuthenticated']"
              variant="outline-light"
              size="lg"
              :to="{ name: 'Register' }"
              class="mt-3 ml-2"
            >
              <b-icon icon="person-plus"></b-icon> Daftar Sekarang
            </b-button>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5 bg-light">
      <b-container>
        <h2 class="text-center mb-5">Kenapa Pilih Kami?</h2>
        <b-row>
          <b-col md="4" class="mb-4" v-for="(feature, index) in features" :key="index">
            <b-card class="h-100 text-center border-0 shadow-sm p-3">
              <b-icon :icon="feature.icon" font-scale="2" variant="primary" class="my-3"></b-icon>
              <b-card-title>{{ feature.title }}</b-card-title>
              <b-card-text>{{ feature.text }}</b-card-text>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- Products Preview -->
    <section class="products-preview-section py-5">
      <b-container>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Photobook Terpopuler</h2>
          <b-button variant="outline-primary" :to="{ name: 'Products' }">
            Lihat Semua Produk
          </b-button>
        </div>

        <b-row v-if="loading">
          <b-col cols="12" class="text-center">
            <b-spinner variant="primary"></b-spinner>
          </b-col>
        </b-row>

        <b-row v-else>
          <b-col md="4" v-for="product in featuredProducts" :key="product.id" class="mb-4">
            <b-card class="h-100 product-card border-0 shadow-sm">
              <b-card-img
                :src="product.thumbnail || defaultThumbnail"
                alt="Product Image"
                top
                class="product-image"
              ></b-card-img>
              <b-card-body class="d-flex flex-column">
                <b-card-title>{{ product.name }}</b-card-title>
                <b-card-text class="flex-grow-1">
                  {{ product.description.substring(0, 100) }}...
                </b-card-text>
                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <h5 class="text-primary mb-0">Rp {{ formatCurrency(product.price) }}</h5>
                  <b-button variant="primary" size="sm" :to="{ name: 'ProductDetail', params: { id: product.id } }">
                    Detail
                  </b-button>
                </div>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-white text-center py-5">
      <b-container>
        <h2 class="mb-3">Mulai Buat Photobook Kamu Hari Ini!</h2>
        <p class="lead mb-4">Gratis desain, mudah digunakan, dan kualitas terbaik di kelasnya.</p>
        <b-button v-if="!$store.getters['auth/isAuthenticated']" variant="light" size="lg" :to="{ name: 'Register' }" class="mr-2">
          <b-icon icon="person-plus"></b-icon> Buat Akun
        </b-button>
        <b-button variant="outline-light" size="lg" :to="{ name: 'Products' }">
          <b-icon icon="images"></b-icon> Lihat Produk
        </b-button>
      </b-container>
    </section>
  </div>
</template>

<script>
import Navbar from '../../components/shared/Navbar.vue';
import productService from '../../services/productService';

export default {
  name: 'Home',
  components: {
    Navbar
  },
  data() {
    return {
      featuredProducts: [],
      loading: false,
      defaultThumbnail: 'https://via.placeholder.com/300x200/3490dc/ffffff?text=Product',
      features: [
        {
          icon: 'star',
          title: 'Kualitas Premium',
          text: 'Bahan terbaik dan cetakan tajam untuk kenangan yang abadi.'
        },
        {
          icon: 'brush',
          title: 'Desain Mudah',
          text: 'Editor desain yang intuitif dengan ratusan template kekinian.'
        },
        {
          icon: 'truck',
          title: 'Pengiriman Cepat',
          text: 'Proses cepat dan aman sampai ke rumah Anda.'
        }
      ]
    };
  },
  async created() {
    await this.loadFeaturedProducts();
  },
  methods: {
    async loadFeaturedProducts() {
      this.loading = true;
      try {
        const response = await productService.getProducts({ limit: 3, featured: true });
        this.featuredProducts = response.data || [];
      } catch (error) {
        console.error('Failed to load products:', error);
        this.featuredProducts = [];
      } finally {
        this.loading = false;
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID').format(amount);
    }
  }
};
</script>

<style scoped>
.hero-section {
  background: linear-gradient(135deg, #ff6a3d 0%, #ff3d6e 100%);
  min-height: 80vh;
  padding-top: 100px;
}

.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border-radius: 0.75rem;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.product-image {
  height: 200px;
  object-fit: cover;
  border-top-left-radius: 0.75rem;
  border-top-right-radius: 0.75rem;
}

.cta-section {
  background: linear-gradient(135deg, #ff6a3d 0%, #ff3d6e 100%);
}
</style>
<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
      <b-container>
        <b-row class="align-items-center">
          <b-col md="6">
            <h1 class="display-4 font-weight-bold">Create Beautiful Photobooks</h1>
            <p class="lead">
              Transform your precious memories into stunning photobooks with our 
              easy-to-use templates and premium quality printing.
            </p>
            <b-button 
              variant="light" 
              size="lg" 
              :to="{ name: 'Products' }"
              class="mt-3"
            >
              <b-icon icon="images"></b-icon> Browse Products
            </b-button>
            <b-button 
              v-if="!$store.getters['auth/isAuthenticated']"
              variant="outline-light" 
              size="lg" 
              :to="{ name: 'Register' }"
              class="mt-3 ml-2"
            >
              <b-icon icon="person-plus"></b-icon> Get Started
            </b-button>
          </b-col>
          <b-col md="6" class="text-center">
            <b-img 
              src="https://via.placeholder.com/500x300/3490dc/ffffff?text=Photobook+Hero" 
              fluid 
              alt="Photobook Hero"
              class="rounded shadow"
            />
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5 bg-light">
      <b-container>
        <h2 class="text-center mb-5">Why Choose Our Photobooks</h2>
        <b-row>
          <b-col md="4" class="mb-4">
            <b-card class="h-100 text-center border-0 shadow-sm">
              <b-icon icon="star" font-scale="2" variant="primary" class="my-3"></b-icon>
              <b-card-title>Premium Quality</b-card-title>
              <b-card-text>
                High-quality paper and professional printing for lasting memories.
              </b-card-text>
            </b-card>
          </b-col>
          <b-col md="4" class="mb-4">
            <b-card class="h-100 text-center border-0 shadow-sm">
              <b-icon icon="brush" font-scale="2" variant="primary" class="my-3"></b-icon>
              <b-card-title>Easy Design</b-card-title>
              <b-card-text>
                Intuitive editor with beautiful templates for effortless creation.
              </b-card-text>
            </b-card>
          </b-col>
          <b-col md="4" class="mb-4">
            <b-card class="h-100 text-center border-0 shadow-sm">
              <b-icon icon="truck" font-scale="2" variant="primary" class="my-3"></b-icon>
              <b-card-title>Fast Delivery</b-card-title>
              <b-card-text>
                Quick processing and reliable shipping to your doorstep.
              </b-card-text>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- Featured Products Preview -->
    <section class="products-preview-section py-5">
      <b-container>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Popular Photobooks</h2>
          <b-button variant="outline-primary" :to="{ name: 'Products' }">
            View All Products
          </b-button>
        </div>
        
        <b-row v-if="loading">
          <b-col cols="12" class="text-center">
            <b-spinner variant="primary"></b-spinner>
          </b-col>
        </b-row>
        
        <b-row v-else>
          <b-col md="4" v-for="product in featuredProducts" :key="product.id" class="mb-4">
            <b-card class="h-100 product-card" no-body>
              <b-card-img 
                :src="product.thumbnail || 'https://via.placeholder.com/300x200/3490dc/ffffff?text=Product'" 
                alt="Product Image"
                top
                class="product-image"
              ></b-card-img>
              <b-card-body class="d-flex flex-column">
                <b-card-title>{{ product.name }}</b-card-title>
                <b-card-text class="flex-grow-1">
                  {{ product.description.substring(0, 100) }}...
                </b-card-text>
                <div class="mt-auto">
                  <div class="d-flex justify-content-between align-items-center">
                    <h5 class="text-primary mb-0">Rp {{ formatCurrency(product.price) }}</h5>
                    <b-button 
                      variant="primary" 
                      size="sm"
                      :to="{ name: 'ProductDetail', params: { id: product.id } }"
                    >
                      View Details
                    </b-button>
                  </div>
                </div>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- CTA Section -->
    <section class="cta-section bg-primary text-white py-5">
      <b-container class="text-center">
        <h2 class="mb-3">Ready to Create Your Photobook?</h2>
        <p class="lead mb-4">
          Join thousands of happy customers who have preserved their memories with us.
        </p>
        <b-button 
          v-if="!$store.getters['auth/isAuthenticated']"
          variant="light" 
          size="lg" 
          :to="{ name: 'Register' }"
          class="mr-2"
        >
          <b-icon icon="person-plus"></b-icon> Create Account
        </b-button>
        <b-button 
          variant="outline-light" 
          size="lg" 
          :to="{ name: 'Products' }"
        >
          <b-icon icon="images"></b-icon> Browse Products
        </b-button>
      </b-container>
    </section>
  </div>
</template>

<script>
import productService from '../../services/productService';

export default {
  name: 'Home',
  data() {
    return {
      featuredProducts: [],
      loading: false
    };
  },
  async created() {
    await this.loadFeaturedProducts();
  },
  methods: {
    async loadFeaturedProducts() {
      this.loading = true;
      try {
        const response = await productService.getProducts({
          limit: 3, // Hanya 3 produk teratas
          featured: true
        });
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
  background: linear-gradient(135deg, #3490dc 0%, #657eea 100%);
}

.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.product-image {
  height: 200px;
  object-fit: cover;
}

.cta-section {
  background: linear-gradient(135deg, #3490dc 0%, #657eea 100%);
}
</style>
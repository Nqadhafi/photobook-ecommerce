<template>
  <div class="home-page">
    <!-- Navbar -->
    <Navbar />

    <!-- ===== HERO ===== -->
    <section class="hero-section d-flex align-items-center">
      <b-container>
        <b-row class="align-items-center">
          <!-- Gambar HERO (mobile di atas, desktop di kanan) -->
          <b-col md="6" class="text-center order-1 order-md-2 mb-4 mb-md-0">
            <div class="hero-image-wrap mx-auto">
              <b-img
                :src="heroImage"
                alt="Ilustrasi Photobook"
                fluid
                class="hero-image"
              />
            </div>
          </b-col>

          <!-- Teks HERO -->
          <b-col md="6" class="order-2 order-md-1">
            <div class="hero-kicker mb-2">Shabat Printing Photobook</div>
            <h1 class="hero-title mb-3">Abadikan Momen Berharga Dengan Keluarga</h1>
            <p class="hero-subtitle mb-4">
              Cetak photobook dengan desain elegan, bahan terbaik, dan proses pemesanan super mudah.
            </p>
            <div class="d-flex flex-wrap">
              <b-button variant="light" size="lg" :to="{ name: 'Products' }">
                <b-icon icon="images"></b-icon> Jelajahi Produk
              </b-button>
              <b-button
                v-if="!$store.getters['auth/isAuthenticated']"
                variant="outline-light"
                size="lg"
                :to="{ name: 'Register' }"
                class="ml-2 mt-2 mt-md-0"
              >
                <b-icon icon="person-plus"></b-icon> Daftar Sekarang
              </b-button>
            </div>
            <div class="trust-row text-light mt-4">
              ★ 4.9/5 dari pelanggan • Garansi kualitas • Pengiriman cepat
            </div>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- ===== BEST SELLERS (maks 5 item, kartu kompak) ===== -->
    <section class="best-seller-section py-5 bg-soft">
      <b-container>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="section-title mb-1">Terlaris Minggu Ini</h2>
            <p class="section-subtitle mb-0">Produk dengan penjualan tertinggi — tepercaya banyak pelanggan.</p>
          </div>
          <b-button variant="outline-primary" :to="{ name: 'Products', query: { sort: 'best_selling' } }">
            Lihat Semua
          </b-button>
        </div>

        <b-row v-if="bestLoading">
          <b-col cols="12" class="text-center"><b-spinner variant="primary"></b-spinner></b-col>
        </b-row>

        <b-row v-else>
          <b-col
            lg="3"
            md="4"
            sm="6"
            v-for="product in bestSellers"
            :key="product.id"
            class="mb-3"
          >
            <b-card class="h-100 product-card compact border-0 shadow-sm">
              <div class="image-wrap">
                <b-card-img
                  :src="product.thumbnail ? '/storage/' + product.thumbnail : defaultThumbnail"
                  alt="Gambar Produk"
                  top
                  class="product-image compact"
                />

                <span class="sold-badge">
                  <b-icon icon="bar-chart"></b-icon>
                  Terjual {{ formatNumber(product.total_sold || 0) }}
                </span>
              </div>
              <b-card-body class="d-flex flex-column py-3">
                <b-card-title class="mb-1 text-dark h6">{{ product.name }}</b-card-title>
                <b-card-text class="flex-grow-1 muted small mb-2">
                  {{ (product.description || '').substring(0, 80) }}<span v-if="(product.description || '').length > 80">…</span>
                </b-card-text>
                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <div class="price text-primary font-weight-bold">Rp {{ formatCurrency(product.price) }}</div>
                  <b-button
                    variant="primary"
                    size="sm"
                    :to="{ name: 'ProductDetail', params: { id: product.id } }"
                  >
                    Detail
                  </b-button>
                </div>
              </b-card-body>
            </b-card>
          </b-col>

          <b-col v-if="!bestSellers.length && !bestLoading" cols="12" class="text-center text-muted">
            Belum ada data penjualan.
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="features-section py-5">
      <b-container>
        <h2 class="section-title text-center mb-1">Kenapa Pilih Kami?</h2>
        <p class="section-subtitle text-center mb-5">Kualitas, kemudahan, dan layanan yang konsisten.</p>
        <b-row>
          <b-col md="4" class="mb-4" v-for="(feature, index) in features" :key="index">
            <b-card class="h-100 text-center border-0 soft-card p-3">
              <div class="feature-icon-wrap my-2">
                <b-icon :icon="feature.icon" font-scale="1.7" class="feature-icon"></b-icon>
              </div>
              <div class="h5 mb-2">{{ feature.title }}</div>
              <div class="text-muted small">{{ feature.text }}</div>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta-section text-white text-center py-5 mb-0">
      <b-container>
        <h2 class="mb-2">Siap Mencetak Photobook Pertamamu?</h2>
        <p class="lead mb-4">Desain moderen, hasil tajam, pengalaman belanja yang mulus.</p>
        <b-button
          v-if="!$store.getters['auth/isAuthenticated']"
          variant="light"
          size="lg"
          :to="{ name: 'Register' }"
          class="mr-2"
        >
          <b-icon icon="person-plus"></b-icon> Buat Akun
        </b-button>
        <b-button variant="outline-light" size="lg" :to="{ name: 'Products', query: { sort: 'best_selling' } }">
          <b-icon icon="images"></b-icon> Lihat Terlaris
        </b-button>
      </b-container>
    </section>

    <!-- Footer -->
    <Footer class="mt-0" />
  </div>
</template>

<script>
import Navbar from '../../components/shared/Navbar.vue';
import Footer from '../../components/shared/Footer.vue';
import productService from '../../services/productService';

export default {
  name: 'Home',
  components: { Navbar, Footer },
  data() {
    return {
      heroImage: require('../../components/assets/hero.webp'), // <-- taruh gambarmu di sini (ganti path sesuai asetmu)
      bestSellers: [],
      bestLoading: false,
      defaultThumbnail: 'https://via.placeholder.com/600x400/0ea5e9/ffffff?text=Photobook',
      features: [
        { icon: 'star',  title: 'Kualitas Premium',  text: 'Material unggul & cetakan tajam untuk hasil elegan.' },
        { icon: 'brush', title: 'Desain Variatif',    text: 'Puluhan template modern sesuai tema favorit.' },
        { icon: 'truck', title: 'Pengiriman Cepat',   text: 'Proses ringkas & aman sampai ke alamat Anda.' }
      ]
    };
  },
  async created() {
    await this.loadBestSellers();
  },
  methods: {
    async loadBestSellers() {
      this.bestLoading = true;
      try {
        const response = await productService.getProducts({ sort: 'best_selling' });
        // Maksimal 5 item agar ringkas & tidak membingungkan
        this.bestSellers = Array.isArray(response.data) ? response.data.slice(0, 5) : [];
      } catch (error) {
        console.error('Failed to load best sellers:', error);
        this.bestSellers = [];
      } finally {
        this.bestLoading = false;
      }
    },
    formatCurrency(amount) {
      try { return new Intl.NumberFormat('id-ID').format(amount || 0); }
      catch { return amount; }
    },
    formatNumber(num) {
      try { return new Intl.NumberFormat('id-ID').format(num || 0); }
      catch { return num; }
    }
  }
};
</script>

<!-- ===== GLOBAL (NON-SCOPED) ===== -->
<style>
:root{
  --hero-grad-a: #0ea5e9; /* cyan-500 */
  --hero-grad-b: #3b82f6; /* blue-500 */
  --soft-bg: #f8fafc;     /* very light */
}
.bg-soft{ background: var(--soft-bg, #f8fafc); }
</style>

<!-- ===== KOMPONEN (SCOPED) ===== -->
<style scoped>
/* ===== HERO ===== */
.hero-section {
  background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
  background: linear-gradient(135deg, var(--hero-grad-a, #0ea5e9) 0%, var(--hero-grad-b, #3b82f6) 100%);
  min-height: 70vh;
  margin-top: -1px;
  padding-top: 38px; /* clear navbar fixed */
  padding-bottom: 48px;
  position: relative;
  overflow: hidden;
}
.hero-section::after{
  content: '';
  position: absolute;
  inset: 0;
  opacity: .12;
  background-image: radial-gradient(#ffffff 1px, transparent 1px);
  background-size: 12px 12px;
  pointer-events: none;
}
.hero-image-wrap{
  width: 100%;
  max-width: 540px;         /* batasi lebar gambar di desktop */
  aspect-ratio: 4 / 3;      /* rasio enak dipandang; aman di browser modern */
  background: rgba(255,255,255,.08);
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 16px 40px rgba(2,132,199,.25);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.hero-image{
  width: 100%;
  height: 100%;
  object-fit: contain;      /* biar gambar tidak terpotong */
}
.hero-kicker{
  letter-spacing: .08em;
  text-transform: uppercase;
  font-weight: 600;
  color: rgba(255,255,255,.85);
  font-size: .8rem;
}
.hero-title{
  color: #fff;
  font-weight: 800;
  line-height: 1.15;
}
.hero-subtitle{
  color: rgba(255,255,255,.92);
  font-size: 1.05rem;
  max-width: 42rem;
}
.trust-row{ font-size: .9rem; opacity: .95; }

/* ===== SECTION TITLES ===== */
.section-title{ font-weight: 700; }
.section-subtitle{ color: #64748b; font-size: .95rem; }

/* ===== BEST SELLER (KOMPAK) ===== */
.product-card{
  transition: transform .18s ease, box-shadow .18s ease;
  border-radius: .75rem;
  background: #fff;
}
.product-card.compact .product-image{
  height: 180px;          /* lebih pendek dari 220px */
}
.product-card:hover{
  transform: translateY(-3px);
  box-shadow: 0 12px 24px rgba(2,132,199,.08);
}
.image-wrap{ position: relative; }
.product-image{
  height: 220px;
  object-fit: cover;
  border-top-left-radius: .75rem;
  border-top-right-radius: .75rem;
}
.sold-badge{
  position: absolute;
  right: .6rem;
  bottom: .6rem;
  background: rgba(14,165,233,.95);
  color: #fff;
  font-size: .75rem;
  padding: .2rem .5rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  box-shadow: 0 6px 16px rgba(14,165,233,.35);
}
.price{ font-size: .98rem; }

/* ===== FEATURES ===== */
.soft-card{
  background: #fff;
  border-radius: .9rem;
  box-shadow: 0 6px 16px rgba(2, 132, 199, .06);
}
.feature-icon-wrap{
  width: 56px; height: 56px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(14,165,233,.18), rgba(59,130,246,.18));
}
.feature-icon{ color: #0ea5e9; }

/* ===== CTA ===== */
.cta-section{
  background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
  background: linear-gradient(135deg, var(--hero-grad-b, #3b82f6) 0%, var(--hero-grad-a, #0ea5e9) 100%);
}
.muted{ color: #6b7280; }

/* ===== RESPONSIVE ===== */
@media (max-width: 991.98px){
  .hero-section{ min-height: 60vh; padding-top: 88px; }
  .hero-image-wrap{ max-width: 440px; }
  .product-card.compact .product-image{ height: 160px; }
}
@media (max-width: 575.98px){
  .hero-image-wrap{ max-width: 360px; aspect-ratio: 4/3; }
  .product-card.compact .product-image{ height: 150px; }
}
</style>

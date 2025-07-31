<!-- resources/js/components/Footer.vue -->
<template>
  <footer class="bg-dark text-white mt-5 py-4">
    <b-container>
      <b-row>
        <!-- Informasi Perusahaan -->
        <b-col md="4" class="mb-4 mb-md-0">
          <h5>{{ appName }}</h5>
          <p class="text-muted small">
            Menyediakan layanan pembuatan photobook berkualitas tinggi untuk mengabadikan momen berharga Anda.
          </p>
          <!-- Social Media Links (Opsional) -->
          <!--
          <div class="mt-2">
            <a href="#" class="text-white mr-3"><b-icon icon="facebook"></b-icon></a>
            <a href="#" class="text-white mr-3"><b-icon icon="instagram"></b-icon></a>
            <a href="#" class="text-white"><b-icon icon="twitter"></b-icon></a>
          </div>
          -->
        </b-col>

        <!-- Tautan Cepat -->
        <b-col md="4" class="mb-4 mb-md-0">
          <h5>Quick Links</h5>
          <b-nav class="flex-column">
            <b-nav-item :to="{ name: 'Home' }" class="p-0 text-muted">Home</b-nav-item>
            <b-nav-item :to="{ name: 'Products' }" class="p-0 text-muted">Products</b-nav-item>
            <b-nav-item :to="{ name: 'About' }" class="p-0 text-muted" v-if="false">About Us</b-nav-item> <!-- Ganti v-if="false" jika ada halaman About -->
            <b-nav-item :to="{ name: 'Contact' }" class="p-0 text-muted" v-if="false">Contact</b-nav-item> <!-- Ganti v-if="false" jika ada halaman Contact -->
          </b-nav>
        </b-col>

        <!-- Kontak -->
        <b-col md="4">
          <h5>Contact Us</h5>
          <ul class="list-unstyled text-muted small">
            <li class="mb-2"><b-icon icon="geo-alt" class="mr-2"></b-icon> Jl. Contoh Alamat No. 123, Kota</li>
            <li class="mb-2"><b-icon icon="telephone" class="mr-2"></b-icon> +62 812 3456 7890</li>
            <li class="mb-2"><b-icon icon="envelope" class="mr-2"></b-icon> email@photobookapp.com</li>
          </ul>
        </b-col>
      </b-row>

      <b-row class="mt-4 pt-3 border-top border-secondary">
        <b-col md="6" class="text-center text-md-left">
          <small class="text-muted">&copy; {{ currentYear }} {{ appName }}. All rights reserved.</small>
        </b-col>
        <b-col md="6" class="text-center text-md-right">
          <small class="text-muted">
            <!-- Tautan Kebijakan & Syarat (Opsional) -->
            <!--
            <a href="#" class="text-muted mr-3">Privacy Policy</a>
            <a href="#" class="text-muted">Terms of Service</a>
            -->
          </small>
        </b-col>
      </b-row>
    </b-container>
  </footer>
</template>

<script>
export default {
  name: 'AppFooter',
  data() {
    return {
      appName: process.env.MIX_APP_NAME || 'Photobook App', // Ambil dari .env
    }
  },
  computed: {
    currentYear() {
      return new Date().getFullYear();
    }
  }
}
</script>

<style scoped>
/* Opsional: Tambahkan gaya khusus jika diperlukan */
footer a.text-muted {
  transition: color 0.2s ease-in-out;
}

footer a.text-muted:hover {
  color: #ffffff !important; /* Gunakan !important jika diperlukan untuk mengatasi gaya Bootstrap */
  text-decoration: none;
}
</style>
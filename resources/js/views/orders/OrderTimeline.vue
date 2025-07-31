<template>
    <b-container>
      <b-row v-if="loading">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading order progress...</p>
        </b-col>
      </b-row>

      <b-row v-else-if="error">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ error }}</p>
            <b-button v-if="order" :to="{ name: 'OrderDetail', params: { id: $route.params.id } }" variant="primary" size="sm" class="mr-2">
              <b-icon icon="arrow-left"></b-icon> Back to Order
            </b-button>
            <b-button :to="{ name: 'Orders' }" variant="outline-primary" size="sm">
              <b-icon icon="list"></b-icon> View All Orders
            </b-button>
          </b-alert>
        </b-col>
      </b-row>

      <!-- Wizard Timeline -->
      <b-row v-else-if="timeline.length > 0">
        <b-col cols="12">
          <b-card>
            <div class="wizard-container">
              <div
                v-for="(event, index) in timeline"
                :key="index"
                :class="[
                  'wizard-step',
                  { 'active': index === currentStepIndex },
                  { 'completed': index < currentStepIndex },
                  { 'future': index > currentStepIndex },
                  { 'no-timestamp': !event.timestamp } // Tambahkan kelas jika tidak ada timestamp
                ]"
              >
                <div class="step-line"></div>
                <div class="step-icon-wrapper">
                  <div class="step-icon">
                    <b-icon :icon="getTimelineIcon(event.status)" :variant="getTimelineIconVariant(event.status)"></b-icon>
                  </div>
                </div>
                <div class="step-label">{{ event.label }}</div>
                <!-- --- Tambahkan Tanggal --- -->
                <div v-if="event.timestamp" class="step-date text-muted small">
                  {{ formatDate(event.timestamp) }}
                </div>
                <div v-else class="step-date text-muted small">
                  Pending
                </div>
                 <!-- ---------------------- -->
              </div>
            </div>

            <!-- Detail Langkah Saat Ini -->
            <div v-if="currentStep" class="current-step-detail mt-4 p-3 bg-light rounded">
              <h4 class="mb-2">
                <b-icon :icon="getTimelineIcon(currentStep.status)" :variant="getTimelineIconVariant(currentStep.status)" class="mr-2"></b-icon>
                {{ currentStep.label }}
              </h4>
              <p class="mb-1">{{ currentStep.description }}</p>
              <small class="text-muted">
                <b-icon icon="clock"></b-icon> {{ formatDate(currentStep.timestamp) }}
              </small>
              <div v-if="currentStep.pickup_code" class="mt-2">
                <strong>Pickup Code:</strong> <code class="bg-white px-2 py-1 rounded">{{ currentStep.pickup_code }}</code>
              </div>
            </div>
          </b-card>
        </b-col>
      </b-row>
      <!-- No Timeline Data -->
      <b-row v-else>
        <b-col cols="12" class="text-center py-5">
          <b-icon icon="hourglass-split" font-scale="3" variant="secondary"></b-icon>
          <h4 class="mt-3">No timeline data available.</h4>
          <p class="text-muted">The progress for this order will be updated as it advances.</p>
           <b-button v-if="order" :to="{ name: 'OrderDetail', params: { id: $route.params.id } }" variant="primary" class="mr-2">
            <b-icon icon="arrow-left"></b-icon> Back to Order
          </b-button>
          <b-button :to="{ name: 'Orders' }" variant="outline-primary">
            <b-icon icon="list"></b-icon> View All Orders
          </b-button>
        </b-col>
      </b-row>
    </b-container>
</template>

<script>
import orderService from '../../services/orderService';

export default {
  name: 'OrderTimeline',
  data() {
    return {
      order: null,
      timeline: [],
      loading: false,
      error: null,
      currentStepIndex: -1 // Inisialisasi dengan -1
    };
  },
  computed: {
    breadcrumbItems() {
      const items = [
        { text: 'Dashboard', to: { name: 'Dashboard' } },
        { text: 'Orders', to: { name: 'Orders' } }
      ];
      if (this.order) {
        items.push({ text: `Order #${this.order.order_number}`, to: { name: 'OrderDetail', params: { id: this.$route.params.id } } });
      }
      items.push({ text: 'Progress', active: true });
      return items;
    },
    currentStep() {
      // Pastikan currentStepIndex valid
      if (this.currentStepIndex >= 0 && this.currentStepIndex < this.timeline.length) {
        return this.timeline[this.currentStepIndex];
      }
      return null; // Atau objek kosong {}
    }
  },
  async created() {
    await this.loadOrder();
    await this.loadTimeline();
  },
  methods: {
    async loadOrder() {
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrder(orderId);
        this.order = response.data;
      } catch (error) {
        console.warn('Failed to load order for breadcrumb:', error);
        // Tidak perlu set error utama di sini
      }
    },
    async loadTimeline() {
      this.loading = true;
      this.error = null;
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrderTimeline(orderId);
        this.timeline = response.data || [];
        this.determineCurrentStep();
      } catch (error) {
        console.error('Failed to load timeline:', error);
        this.error = error.message || 'Failed to load order progress.';
      } finally {
        this.loading = false;
      }
    },
    determineCurrentStep() {
      // Cari indeks langkah terakhir yang memiliki timestamp (artinya sudah terjadi)
      // Ini akan menjadi langkah "aktif" yang ditampilkan
      let lastCompletedIndex = -1;
      for (let i = this.timeline.length - 1; i >= 0; i--) {
        if (this.timeline[i].timestamp) {
          lastCompletedIndex = i;
          break;
        }
      }
      this.currentStepIndex = lastCompletedIndex;
    },
    getTimelineIcon(status) {
      const iconMap = {
        'created': 'cart-plus',
        'paid': 'cash-coin',
        'file_uploaded': 'cloud-upload',
        'processing': 'gear',
        'ready': 'box-seam',
        'completed': 'check-circle',
        'cancelled': 'x-circle'
      };
      return iconMap[status] || 'question-circle';
    },
    getTimelineIconVariant(status) {
      const variantMap = {
        'created': 'secondary',
        'paid': 'success',
        'file_uploaded': 'info',
        'processing': 'primary',
        'ready': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
      };
      return variantMap[status] || 'secondary';
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    }
  }
};
</script>

<style scoped>
/* --- Wizard Styles --- */
.wizard-container {
  display: flex;
  justify-content: space-between;
  position: relative;
  margin: 2rem 0;
}

/* Garis horizontal penghubung */
.wizard-container::before {
  content: '';
  position: absolute;
  top: 20px; /* Sesuaikan dengan tinggi ikon */
  left: 0;
  right: 0;
  height: 2px;
  background-color: #ced4da; /* Warna default garis */
  z-index: 1;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
  z-index: 2; /* Di atas garis */
  padding: 0 10px; /* Tambahkan padding untuk jarak antar langkah */
}

/* Garis kecil antar ikon */
.step-line {
  position: absolute;
  top: 20px; /* Sesuaikan dengan tinggi ikon */
  left: 0;
  right: 0;
  height: 2px;
  background-color: #ced4da; /* Warna default */
  transition: background-color 0.3s ease;
}
/* Sembunyikan garis untuk langkah pertama */
.wizard-step:first-child .step-line {
  left: 50%;
}
/* Sembunyikan garis untuk langkah terakhir */
.wizard-step:last-child .step-line {
  right: 50%;
}

.step-icon-wrapper {
  position: relative;
  margin-bottom: 0.5rem;
}

.step-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background-color: #e9ecef; /* Default background */
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ced4da; /* Default border */
  color: #6c757d; /* Default icon color */
  transition: all 0.3s ease;
  font-size: 1.2rem;
}

.step-label {
  font-size: 0.85rem;
  text-align: center;
  color: #6c757d; /* Default label color */
  transition: color 0.3s ease;
  font-weight: 500;
  max-width: 100px; /* Batasi lebar label */
  word-break: break-word; /* Pecah kata jika terlalu panjang */
  margin-bottom: 0.25rem; /* Jarak antara label dan tanggal */
}

/* --- Tanggal Langkah --- */
.step-date {
  font-size: 0.7rem; /* Ukuran font lebih kecil */
  text-align: center;
  color: #6c757d; /* Warna teks abu-abu */
  max-width: 100px; /* Batasi lebar agar sesuai dengan label */
  word-break: break-word;
}
/* --------------------- */

/* Status: Future (Belum terjadi) */
.wizard-step.future .step-icon {
  background-color: #f8f9fa;
  border-color: #ced4da;
  color: #6c757d;
}
.wizard-step.future .step-label,
.wizard-step.future .step-date {
  color: #6c757d;
}
.wizard-step.future .step-line {
   background-color: #ced4da;
}

/* Status: Completed (Sudah terjadi) */
.wizard-step.completed .step-icon {
  background-color: #28a745; /* Success color */
  border-color: #28a745;
  color: white;
}
.wizard-step.completed .step-label {
  color: #28a745;
  font-weight: bold;
}
.wizard-step.completed .step-date {
  color: #28a745; /* Tanggal juga ikut berubah warna */
}
.wizard-step.completed .step-line {
   background-color: #28a745; /* Success color */
}

/* Status: Active (Langkah saat ini) */
.wizard-step.active .step-icon {
  background-color: #007bff; /* Primary color */
  border-color: #007bff;
  color: white;
  transform: scale(1.1); /* Sedikit memperbesar ikon aktif */
  box-shadow: 0 2px 4px rgba(0,0,0,0.2); /* Tambahkan bayangan untuk efek */
}
.wizard-step.active .step-label {
  color: #007bff;
  font-weight: bold;
}
.wizard-step.active .step-date {
  color: #007bff; /* Tanggal juga ikut berubah warna */
}
.wizard-step.active .step-line {
   /* Garis menuju langkah aktif bisa berbeda warna */
   /* background-color: #007bff; */
   /* Atau gunakan warna completed jika lebih konsisten */
   background-color: #28a745;
}

/* Status: No Timestamp (Pending) */
.wizard-step.no-timestamp .step-date {
  font-style: italic; /* Buat tanggal miring jika pending */
}

/* Detail Langkah Saat Ini */
.current-step-detail h4 {
  margin-top: 0;
}
.current-step-detail code {
  font-size: 1rem;
}

/* Responsif untuk layar kecil */
@media (max-width: 767.98px) {
  .wizard-container {
    flex-wrap: wrap;
  }
  .wizard-step {
    flex: 0 0 50%; /* 2 kolom di layar kecil */
    margin-bottom: 2rem;
  }
  .wizard-step:nth-child(odd) {
    padding-right: 20px; /* Tambahkan padding kanan untuk kolom ganjil */
  }
  .wizard-step:nth-child(even) {
    padding-left: 20px; /* Tambahkan padding kiri untuk kolom genap */
  }
  /* Sesuaikan garis horizontal untuk 2 kolom */
  .wizard-container::before {
    display: none; /* Sembunyikan garis horizontal utuh */
  }
  /* Tampilkan garis vertikal antar baris jika diperlukan */
  /* ... (CSS tambahan untuk garis vertikal bisa ditambahkan di sini jika diinginkan) ... */

  .step-label {
    font-size: 0.75rem;
    max-width: 80px;
  }
  .step-date {
    font-size: 0.65rem; /* Ukuran font lebih kecil lagi di layar kecil */
    max-width: 80px;
  }
  .step-icon {
    width: 36px;
    height: 36px;
    font-size: 1rem;
  }
}

@media (max-width: 575.98px) {
  .wizard-step {
    flex: 0 0 100%; /* 1 kolom di layar sangat kecil */
    padding: 0 10px !important; /* Reset padding */
    margin-bottom: 1.5rem;
  }
  .step-label {
    max-width: none; /* Hapus batas lebar */
  }
  .step-date {
    max-width: none; /* Hapus batas lebar */
  }
}
/* ---------------------------- */
</style>

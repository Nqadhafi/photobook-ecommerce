<!-- resources/js/views/orders/OrderTimeline.vue -->
<template>
  <b-container>
    <!-- Loading -->
    <b-row v-if="loading">
      <b-col cols="12" class="text-center py-5">
        <b-spinner variant="primary" style="width:3rem;height:3rem;"></b-spinner>
        <p class="mt-3">Loading order progress...</p>
      </b-col>
    </b-row>

    <!-- Error -->
    <b-row v-else-if="error">
      <b-col cols="12">
        <b-alert variant="danger" show>
          <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
          <p class="mb-3">{{ error }}</p>
          <div class="d-flex flex-wrap gap-2">
            <b-button v-if="order" :to="{ name: 'OrderDetail', params: { id: $route.params.id } }" variant="primary" size="sm" class="mr-2">
              <b-icon icon="arrow-left"></b-icon> Kembali ke Pesanan
            </b-button>
            <b-button :to="{ name: 'Orders' }" variant="outline-primary" size="sm">
              <b-icon icon="list"></b-icon> Lihat Semua Pesanan
            </b-button>
          </div>
        </b-alert>
      </b-col>
    </b-row>

    <!-- Timeline -->
    <b-row v-else-if="timeline.length">
      <b-col cols="12">
        <b-card class="border-0 shadow-sm">
          <!-- Wizard -->
          <div class="wizard-container">
            <div
              v-for="(step, idx) in timeline"
              :key="step.status"
              :class="[
                'wizard-step',
                { completed: idx < currentStepIndex },
                { active: idx === currentStepIndex },
                { future: idx > currentStepIndex },
                { cancelled: isCancelled && idx !== cancelIndex }
              ]"
            >
              <!-- connector line segment -->
              <div class="step-line"></div>

              <!-- icon -->
              <div class="step-icon-wrapper">
                <div class="step-icon" :class="'ico-' + step.status">
                  <b-icon :icon="getIcon(step.status)"></b-icon>
                </div>
              </div>

              <!-- label -->
              <div class="step-label">{{ getLabel(step.status) }}</div>

              <!-- timestamp / pending -->
              <div class="step-date text-muted small" v-if="step.timestamp">
                {{ formatDate(step.timestamp) }}
              </div>
              <div class="step-date text-muted small" v-else>Pending</div>
            </div>
          </div>

          <!-- Current step detail -->
          <div v-if="currentStep" class="current-step-detail mt-4 p-3 bg-light rounded">
            <h5 class="mb-2 d-flex align-items-center">
              <b-icon :icon="getIcon(currentStep.status)" :class="'mr-2 text-' + getVariant(currentStep.status)"></b-icon>
              {{ getLabel(currentStep.status) }}
            </h5>
            <p class="mb-1">{{ getDescription(currentStep.status) }}</p>
            <small class="text-muted d-block mb-2">
              <b-icon icon="clock"></b-icon>
              {{ currentStep.timestamp ? formatDate(currentStep.timestamp) : 'Waiting to be updated' }}
            </small>

            <!-- pickup code (show when ready/completed and code exists) -->
            <div v-if="pickupCode && (currentStep.status === 'ready' || currentStep.status === 'completed')" class="mt-2">
              <strong>Pickup Code:</strong>
              <code class="bg-white px-2 py-1 rounded">{{ pickupCode }}</code>
            </div>

            <!-- If cancelled -->
            <b-alert v-if="isCancelled" show variant="danger" class="mt-3 mb-0">
              <b-icon icon="x-circle"></b-icon>
              Order telah <strong>dibatalkan</strong>.
            </b-alert>
          </div>
        </b-card>
      </b-col>
    </b-row>

    <!-- Empty -->
    <b-row v-else>
      <b-col cols="12" class="text-center py-5">
        <b-icon icon="hourglass-split" font-scale="3" variant="secondary"></b-icon>
        <h4 class="mt-3">No timeline available.</h4>
        <p class="text-muted">Progress pesanan akan segera ditampilkan.</p>
        <div class="d-flex justify-content-center gap-2">
          <b-button v-if="order" :to="{ name: 'OrderDetail', params: { id: $route.params.id } }" variant="primary" class="mr-2">
            <b-icon icon="arrow-left"></b-icon> Kembali ke Pesanan
          </b-button>
          <b-button :to="{ name: 'Orders' }" variant="outline-primary">
            <b-icon icon="list"></b-icon> Lihat Semua Pesanan
          </b-button>
        </div>
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
      rawTimeline: [],
      timeline: [],
      loading: false,
      error: null,
      currentStepIndex: -1,
      cancelIndex: -1,
      pickupCode: null
    };
  },
  async created() {
    await this.loadOrder();
    await this.loadTimeline();
  },
  computed: {
    isCancelled() {
      return this.timeline.some(s => s.status === 'cancelled' && s.timestamp);
    },
    currentStep() {
      if (this.currentStepIndex >= 0 && this.currentStepIndex < this.timeline.length) {
        return this.timeline[this.currentStepIndex];
      }
      return null;
    }
  },
  methods: {
    async loadOrder() {
      try {
        const orderId = this.$route.params.id;
        const res = await orderService.getOrder(orderId);
        this.order = res.data;
        this.pickupCode = this.order && this.order.pickup_code ? this.order.pickup_code : null;
      } catch (e) {
        // breadcrumb/order optional; jangan ganggu flow
        console.warn('loadOrder (optional) failed:', e);
      }
    },
    async loadTimeline() {
      this.loading = true;
      this.error = null;
      try {
        const orderId = this.$route.params.id;
        const res = await orderService.getOrderTimeline(orderId);
        this.rawTimeline = Array.isArray(res.data) ? res.data : [];

        // Normalisasi dan urutkan sesuai step baku app
        this.timeline = this.normalizeTimeline(this.rawTimeline, this.order);
        this.computeCurrentIndex();
      } catch (e) {
        console.error('Failed to load timeline:', e);
        this.error = e?.message || 'Failed to load order progress.';
      } finally {
        this.loading = false;
      }
    },

    // --- Normalization ---
    normalizeTimeline(raw, order) {
      // Peta alias -> status baku
      const aliasMap = {
        created: 'pending',
        pending: 'pending',
        paid: 'paid',
        file_uploaded: 'file_upload',
        file_upload: 'file_upload',
        processing: 'processing',
        ready: 'ready',
        completed: 'completed',
        cancelled: 'cancelled'
      };

      // status baku & urutan
      const canonicalOrder = ['pending', 'paid', 'file_upload', 'processing', 'ready', 'completed'];

      // Build map dari raw
      const stampByStatus = {};
      raw.forEach(ev => {
        const key = aliasMap[(ev.status || '').toLowerCase()] || 'pending';
        // ambil timestamp terbaru jika duplikat
        const ts = ev.timestamp || ev.updated_at || ev.created_at || null;
        if (!stampByStatus[key] || (ts && new Date(ts) > new Date(stampByStatus[key]))) {
          stampByStatus[key] = ts;
        }
      });

      // Inject dari order (fallback) kalau backend timeline kosong/minim
      if (order) {
        if (order.created_at && !stampByStatus.pending) stampByStatus.pending = order.created_at;
        if (order.paid_at && !stampByStatus.paid) stampByStatus.paid = order.paid_at;
        if ((order.status === 'file_upload' || order.file_upload_at) && !stampByStatus.file_upload) {
          stampByStatus.file_upload = order.file_upload_at || order.updated_at || null;
        }
        if ((order.status === 'processing' || order.processing_at) && !stampByStatus.processing) {
          stampByStatus.processing = order.processing_at || order.updated_at || null;
        }
        if ((order.status === 'ready' || order.ready_at) && !stampByStatus.ready) {
          stampByStatus.ready = order.ready_at || order.updated_at || null;
        }
        if ((order.status === 'completed' || order.completed_at) && !stampByStatus.completed) {
          stampByStatus.completed = order.completed_at || order.updated_at || null;
        }
      }

      // Kalau cancelled, tambahkan node cancelled di akhir
      let cancelledTs = null;
      const rawCancelled = raw.find(e => aliasMap[(e.status || '').toLowerCase()] === 'cancelled');
      if (rawCancelled) {
        cancelledTs = rawCancelled.timestamp || rawCancelled.updated_at || rawCancelled.created_at || null;
      } else if (order && order.status === 'cancelled') {
        cancelledTs = order.cancelled_at || order.updated_at || null;
      }

      // Compose list final (baku)
      const finalList = canonicalOrder.map(s => ({
        status: s,
        timestamp: stampByStatus[s] || null
      }));

      if (cancelledTs) {
        finalList.push({ status: 'cancelled', timestamp: cancelledTs });
      }

      return finalList;
    },

    computeCurrentIndex() {
      // Jika cancelled, current = index cancelled
      const cancelIdx = this.timeline.findIndex(s => s.status === 'cancelled' && s.timestamp);
      if (cancelIdx !== -1) {
        this.currentStepIndex = cancelIdx;
        this.cancelIndex = cancelIdx;
        return;
      }

      // Ambil langkah terakhir yang sudah punya timestamp
      let lastIdx = -1;
      for (let i = 0; i < this.timeline.length; i++) {
        if (this.timeline[i].timestamp) lastIdx = i;
      }
      this.currentStepIndex = lastIdx;
    },

    // --- Labels, descriptions, icons, variants ---
    getLabel(status) {
      const labels = {
        pending: 'Menunggu Pembayaran',
        paid: 'Pembayaran Diterima',
        file_upload: 'Upload File Anda',
        processing: 'Sedang Diproduksi',
        ready: 'Siap Diambil',
        completed: 'Selesai',
        cancelled: 'Dibatalkan'
      };
      return labels[status] || status;
    },
    getDescription(status) {
      const desc = {
        pending: 'Pesanan berhasil dibuat, menunggu pembayaran.',
        paid: 'Pembayaran diterima. Sedang menyiapkan folder upload.',
        file_upload: 'Link Google Drive Siap, Silahkan Upload File Anda.',
        processing: 'Pesanan sedang diproduksi.',
        ready: 'Pesanan siap diambil..',
        completed: 'Pesanan telah selesai. Terima kasih!',
        cancelled: 'Pesanan dibatalkan.'
      };
      return desc[status] || '';
    },
    getIcon(status) {
      const icons = {
        pending: 'cart-plus',
        paid: 'cash-coin',
        file_upload: 'cloud-upload',
        processing: 'gear',
        ready: 'box-seam',
        completed: 'check-circle',
        cancelled: 'x-circle'
      };
      return icons[status] || 'question-circle';
    },
    getVariant(status) {
      const variants = {
        pending: 'warning',
        paid: 'info',
        file_upload: 'primary',
        processing: 'primary',
        ready: 'warning',
        completed: 'success',
        cancelled: 'danger'
      };
      return variants[status] || 'secondary';
    },

    // --- Utils ---
    formatDate(d) {
      if (!d) return 'N/A';
      try {
        const opt = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return new Date(d).toLocaleDateString('id-ID', opt);
      } catch {
        return 'N/A';
      }
    }
  }
};
</script>

<style scoped>
/* Container line across steps */
.wizard-container {
  position: relative;
  display: flex;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 0 6px;
  margin: .5rem 0 1rem;
}
.wizard-container::before {
  content: '';
  position: absolute;
  top: 28px; /* center line to icons */
  left: 0; right: 0;
  height: 2px;
  background: #e5e7eb;
  z-index: 1;
}

/* Step */
.wizard-step {
  position: relative;
  z-index: 2;
  flex: 1 1 0;
  display: flex;
  align-items: center;
  flex-direction: column;
  text-align: center;
}

/* Segment connector between steps */
.step-line {
  position: absolute;
  top: 28px;
  left: 0; right: 0;
  height: 2px;
  background: #e5e7eb;
  transition: background-color .25s ease;
}
/* First & last halves */
.wizard-step:first-child .step-line { left: 50%; }
.wizard-step:last-child .step-line { right: 50%; }

/* Icon bubble */
.step-icon {
  width: 44px;
  height: 44px;
  border-radius: 999px;
  background: #f1f5f9;
  border: 2px solid #e2e8f0;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .2s ease;
}
.step-label {
  margin-top: .5rem;
  font-size: .85rem;
  color: #6b7280;
  font-weight: 600;
}
.step-date {
  font-size: .72rem;
  color: #94a3b8;
}

/* Completed */
.wizard-step.completed .step-icon {
  background: #10b981;
  border-color: #10b981;
  color: #fff;
}
.wizard-step.completed .step-label,
.wizard-step.completed .step-date {
  color: #10b981;
}
.wizard-step.completed .step-line {
  background: #10b981;
}

/* Active */
.wizard-step.active .step-icon {
  background: #3b82f6;
  border-color: #3b82f6;
  color: #fff;
  transform: scale(1.08);
  box-shadow: 0 4px 10px rgba(59,130,246,.25);
}
.wizard-step.active .step-label,
.wizard-step.active .step-date {
  color: #3b82f6;
}
.wizard-step.active .step-line {
  background: #10b981; /* completed up to active */
}

/* Cancelled (dim others when cancelled) */
.wizard-step.cancelled .step-icon,
.wizard-step.cancelled .step-label,
.wizard-step.cancelled .step-date {
  opacity: .65;
}

/* Current step detail */
.current-step-detail {
  border: 1px solid #e5e7eb;
}

/* Responsive: grid-like on small screens */
@media (max-width: 767.98px) {
  .wizard-container {
    flex-wrap: wrap;
    gap: 14px 10px;
  }
  .wizard-container::before { display: none; }
  .wizard-step {
    flex: 0 0 calc(50% - 5px);
    align-items: center;
  }
  .wizard-step .step-line { display: none; }
  .step-icon { width: 40px; height: 40px; }
  .step-label { font-size: .8rem; }
  .step-date { font-size: .68rem; }
}
@media (max-width: 420px) {
  .wizard-step { flex: 0 0 100%; }
}
</style>

<!-- resources/js/components/admin/charts/OrderTrendChart.vue -->
<template>
  <div class="order-trend-chart">
    <!-- Komponen Bar akan dirender jika data sudah dimuat -->
    <Bar
      v-if="loaded"
      :data="chartData"
      :options="chartOptions"
      :key="chartKey"
      style="height: 100%;"
    />
    <div v-else class="text-center my-5">
      <b-spinner variant="primary" label="Loading chart data..."></b-spinner>
      <p>Loading chart data...</p>
    </div>
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'

import { Bar } from 'vue-chartjs'

// Daftarkan komponen yang dibutuhkan Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'OrderTrendChart',
  components: {
    Bar
  },
  props: {
    chartData: {
      type: Object,
      required: true
    },
    loaded: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      chartKey: 0, // Untuk merender ulang chart saat data berubah
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          },
          title: {
            display: true,
            text: 'Order Trends (Last 6 Months)'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0 // Pastikan tidak ada nilai desimal
            }
          }
        }
      }
    }
  },
  watch: {
    chartData: {
      handler() {
        // Untuk memaksa chart merender ulang saat data berubah
        this.chartKey += 1
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.order-trend-chart {
  height: 300px;
}
</style>

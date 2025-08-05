// resources/js/services/deskprintService.js
import api from './api'; // Gunakan instance api yang sudah dikonfigurasi

class DeskprintService {
  // Mengambil daftar deskprint, bisa dengan filter
  async getDeskprints(params = {}) {
    try {
      // Endpoint: GET /api/admin/deskprints
      const response = await api.get('/admin/deskprints', { params });
      // Asumsi response adalah array langsung atau pagination data
      // Jika pagination: return response.data.data;
      // Jika langsung array: return response.data;
      // Untuk flexibilitas, kita kembalikan response.data secara umum
      // dan biarkan pemanggil menangani struktur datanya jika perlu.
      // Tapi untuk dropdown, kita biasanya butuh array of objects.
      // Mari asumsikan API mengembalikan pagination object.
      return response.data; // Ini akan berupa { data: [...], ... } jika paginated
    } catch (error) {
      console.error('DeskprintService.getDeskprints failed:', error);
      throw error.response?.data || { error: 'Failed to fetch deskprints' };
    }
  }

  // Mengambil daftar deskprint aktif saja (untuk dropdown)
  async getActiveDeskprints() {
    try {
      // Filter di backend untuk is_active = true
      const params = { is_active: true, per_page: 100 }; // Ambil semua aktif, batasi jika perlu
      const response = await api.get('/admin/deskprints', { params });
      // Kembalikan array data-nya saja untuk dropdown
      return response.data.data || response.data; // Tangani jika tidak paginated
    } catch (error) {
      console.error('DeskprintService.getActiveDeskprints failed:', error);
      throw error.response?.data || { error: 'Failed to fetch active deskprints' };
    }
  }

  // Method lain untuk CRUD bisa ditambahkan di sini jika diperlukan di frontend
  // async createDeskprint(deskprintData) { ... }
  // async updateDeskprint(id, deskprintData) { ... }
  // async deleteDeskprint(id) { ... }
}

export default new DeskprintService();

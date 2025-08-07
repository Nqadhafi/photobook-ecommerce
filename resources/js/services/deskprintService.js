
import api from './api'; // Gunakan instance api yang sudah dikonfigurasi

class DeskprintService {
  // --- Methods untuk Admin Side (Super Admin) ---

  // Mengambil daftar deskprint untuk dashboard Super Admin
  async getAdminDeskprints(params = {}) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get('/admin/deskprints', { params });
      return response.data; // Data dari pagination Laravel
    } catch (error) {
      console.error('DeskprintService.getAdminDeskprints failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin deskprints' };
    }
  }

  // Mengambil detail satu deskprint untuk Super Admin
  async getAdminDeskprint(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get(`/admin/deskprints/${id}`);
      return response.data;
    } catch (error) {
      console.error(`DeskprintService.getAdminDeskprint(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin deskprint details' };
    }
  }

  // Membuat deskprint baru (Super Admin)
  async createAdminDeskprint(deskprintData) {
    try {
      // Prefix dengan /api/admin
      const response = await api.post('/admin/deskprints', deskprintData);
      return response.data;
    } catch (error) {
      console.error('DeskprintService.createAdminDeskprint failed:', error);
      throw error.response?.data || { error: 'Failed to create deskprint' };
    }
  }

  // Memperbarui deskprint (Super Admin)
  async updateAdminDeskprint(id, deskprintData) {
    try {
      // Prefix dengan /api/admin
      // Gunakan api.put() untuk konsistensi dan menghindari masalah method spoofing
      const response = await api.put(`/admin/deskprints/${id}`, deskprintData);
      return response.data;
    } catch (error) {
      console.error(`DeskprintService.updateAdminDeskprint(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update deskprint' };
    }
  }

  // Menghapus deskprint (Super Admin)
  async deleteAdminDeskprint(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.delete(`/admin/deskprints/${id}`);
      return response.data;
    } catch (error) {
      console.error(`DeskprintService.deleteAdminDeskprint(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to delete deskprint' };
    }
  }

  // (Opsional) Mengambil daftar deskprint aktif untuk dropdown di halaman order
  async getActiveDeskprints() {
    try {
      const params = { is_active: true, per_page: 100 };
      const response = await api.get('/admin/deskprints', { params });
      return response.data.data || response.data; // Tangani jika tidak paginated
    } catch (error) {
      console.error('DeskprintService.getActiveDeskprints failed:', error);
      throw error.response?.data || { error: 'Failed to fetch active deskprints' };
    }
  }
  async getActiveDeskprintsForDropdown() {
    try {
      // Parameter yang sesuai dengan aturan validasi Laravel 'nullable|boolean'
      // Mengirim 1 untuk merepresentasikan true
      const params = { 
        is_active: 1,   // Format yang benar untuk validasi boolean
        per_page: 100   // Ambil cukup banyak untuk dropdown
      };
      
      const response = await api.get('/admin/deskprints', { params });
      
      // Kembalikan array data. Sesuaikan dengan struktur paginasi API Anda.
      // Umumnya, response paginasi Laravel berbentuk:
      // {  [...] }
      // Maka kita kembalikan `response.data.data`
      // Jika API mengembalikan array langsung, gunakan `response.data`
      return response.data.data || response.data; 
      
    } catch (error) {
      console.error('DeskprintService.getActiveDeskprintsForDropdown failed:', error);
      // Lempar error untuk ditangani oleh komponen pemanggil
      throw error.response?.data || { error: 'Failed to fetch active deskprints for dropdown' };
    }
  }
  // Tambahkan method admin lainnya di sini jika diperlukan
}

export default new DeskprintService();

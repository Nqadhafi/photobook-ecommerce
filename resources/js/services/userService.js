// resources/js/services/userService.js
import api from './api'; 

class UserService {
  // --- Methods untuk Admin Side (Super Admin) ---

  // Mengambil daftar user admin untuk dashboard Super Admin
  async getAdminUsers(params = {}) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get('/admin/users', { params });
      return response.data; // Data dari pagination Laravel
    } catch (error) {
      console.error('UserService.getAdminUsers failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin users' };
    }
  }

  // Mengambil detail satu user admin untuk Super Admin
  async getAdminUser(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get(`/admin/users/${id}`);
      return response.data;
    } catch (error) {
      console.error(`UserService.getAdminUser(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin user details' };
    }
  }

  // Membuat user admin baru (Super Admin)
  async createAdminUser(userData) {
    try {
      // Prefix dengan /api/admin
      const response = await api.post('/admin/users', userData);
      return response.data;
    } catch (error) {
      console.error('UserService.createAdminUser failed:', error);
      throw error.response?.data || { error: 'Failed to create admin user' };
    }
  }

  // Memperbarui user admin (Super Admin)
  async updateAdminUser(id, userData) {
    try {
      // Prefix dengan /api/admin
      // Bisa juga menggunakan api.put jika backend mendukung
      const response = await api.put(`/admin/users/${id}`, userData); // Spoof method PUT
      return response.data;
    } catch (error) {
      console.error(`UserService.updateAdminUser(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update admin user' };
    }
  }

  // Menghapus user admin (Super Admin)
  async deleteAdminUser(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.delete(`/admin/users/${id}`);
      return response.data;
    } catch (error) {
      console.error(`UserService.deleteAdminUser(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to delete admin user' };
    }
  }

  // Tambahkan method admin lainnya di sini jika diperlukan
}

export default new UserService();

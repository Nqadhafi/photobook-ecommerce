// resources/js/services/couponService.js
import api from './api'; // Gunakan instance api yang sudah dikonfigurasi

class CouponService {
  // --- Methods untuk Admin Side (Super Admin) ---

  // Mengambil daftar kupon untuk dashboard Super Admin
  async getAdminCoupons(params = {}) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get('/admin/coupons', { params });
      return response.data; // Data dari pagination Laravel
    } catch (error) {
      console.error('CouponService.getAdminCoupons failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin coupons' };
    }
  }

  // Mengambil detail satu kupon untuk Super Admin
  async getAdminCoupon(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.get(`/admin/coupons/${id}`);
      return response.data;
    } catch (error) {
      console.error(`CouponService.getAdminCoupon(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin coupon details' };
    }
  }

  // Membuat kupon baru (Super Admin)
  async createAdminCoupon(couponData) {
    try {
      // Prefix dengan /api/admin
      const response = await api.post('/admin/coupons', couponData);
      return response.data;
    } catch (error) {
      console.error('CouponService.createAdminCoupon failed:', error);
      throw error.response?.data || { error: 'Failed to create coupon' };
    }
  }

  // Memperbarui kupon (Super Admin)
  async updateAdminCoupon(id, couponData) {
    try {
      // Prefix dengan /api/admin
      // Gunakan api.put() untuk konsistensi dan menghindari masalah method spoofing
      const response = await api.put(`/admin/coupons/${id}`, couponData);
      return response.data;
    } catch (error) {
      console.error(`CouponService.updateAdminCoupon(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update coupon' };
    }
  }

  // Menghapus kupon (Super Admin)
  async deleteAdminCoupon(id) {
    try {
      // Prefix dengan /api/admin
      const response = await api.delete(`/admin/coupons/${id}`);
      return response.data;
    } catch (error) {
      console.error(`CouponService.deleteAdminCoupon(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to delete coupon' };
    }
  }

  // Tambahkan method admin lainnya di sini jika diperlukan
}

export default new CouponService();

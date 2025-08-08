import api from './api';

class ProductService {
  // --- Methods untuk Customer/User Side ---

  async getProducts(params = {}) {
    try {
      const response = await api.get('/products', { params });
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch products' };
    }
  }

  async getProduct(id) {
    try {
      const response = await api.get(`/products/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch product' };
    }
  }

  async getProductTemplates(productId) {
    try {
      // Untuk customer, mengambil template terkait produk
      const response = await api.get(`/products/${productId}/templates`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch templates' };
    }
  }

  // --- Methods untuk Admin Side ---

  // Mengambil daftar produk untuk admin dashboard (Super Admin)
  async getAdminProducts(params = {}) {
    try {
      // Prefix dengan /api/admin - api instance sudah punya baseURL '/api'
      const response = await api.get('/admin/products', { params });
      return response.data;
    } catch (error) {
      console.error('ProductService.getAdminProducts failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin products' };
    }
  }

  async getAdminProduct(id) {
    try {
      const response = await api.get(`/admin/products/${id}`);
      return response.data;
    } catch (error) {
      console.error(`ProductService.getAdminProduct(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin product details' };
    }
  }

  async createAdminProduct(productData) {
    try {
      const response = await api.post('/admin/products', productData);
      return response.data;
    } catch (error) {
      console.error('ProductService.createAdminProduct failed:', error);
      throw error.response?.data || { error: 'Failed to create product' };
    }
  }

  async updateAdminProduct(id, productData) {
    try {
      // Bisa juga menggunakan api.put jika backend mendukung
      const response = await api.post(`/admin/products/${id}`, productData);
      return response.data;
    } catch (error) {
      console.error(`ProductService.updateAdminProduct(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update product' };
    }
  }

  async deleteAdminProduct(id) {
    try {
      const response = await api.delete(`/admin/products/${id}`);
      return response.data;
    } catch (error) {
      console.error(`ProductService.deleteAdminProduct(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to delete product' };
    }
  }

  // Tambahkan method admin lainnya di sini jika diperlukan
}

export default new ProductService();

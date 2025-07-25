import api from './api';

class ProductService {
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
      const response = await api.get(`/products/${productId}/templates`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch templates' };
    }
  }
}

export default new ProductService();
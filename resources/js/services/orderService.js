// resources/js/services/orderService.js
import api from './api'; // Gunakan instance api yang sudah dikonfigurasi

class OrderService {
  async checkout(orderData) {
    try {
      const response = await api.post('/checkout', orderData);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to process checkout' };
    }
  }

  async cancelOrder(orderId) {
    try {
      const response = await api.post(`/orders/${orderId}/cancel`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to cancel order' };
    }
  }

    async validateCoupon(couponCode) {
    try {
      const response = await api.post('/validate-coupon', { coupon_code: couponCode });
      return response.data; // Harus berisi data kupon jika valid
    } catch (error) {
      throw error.response?.data || { error: 'Invalid or expired coupon.' };
    }
  }

  async getOrders(params = {}) {
    try {
      const response = await api.get('/orders', { params });
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch orders' };
    }
  }

  async getOrder(id) {
    try {
      const response = await api.get(`/orders/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch order' };
    }
  }

  async uploadFiles(orderId, files) {
    try {
      const formData = new FormData();

      files.forEach((fileData, index) => {
        formData.append(`files[${index}][order_item_id]`, fileData.order_item_id);
        formData.append(`files[${index}][file]`, fileData.file);
      });

      const response = await api.post(`/orders/${orderId}/upload`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to upload files' };
    }
  }

  async getOrderTimeline(orderId) {
    try {
      const response = await api.get(`/orders/${orderId}/timeline`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch order timeline' };
    }
  }

  // --- Methods untuk Admin Side ---

  // Mengambil daftar order untuk admin dashboard
  async getAdminOrders(params = {}) {
    try {
      // Benar: /api/admin/orders (api instance punya baseURL '/api')
      const response = await api.get('/admin/orders', { params });
      return response.data;
    } catch (error) {
      console.error('OrderService.getAdminOrders failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin orders' };
    }
  }

  async getAdminOrder(id) {
    try {
      const response = await api.get(`/admin/orders/${id}`);
      return response.data;
    } catch (error) {
      console.error(`OrderService.getAdminOrder(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin order details' };
    }
  }

  async updateOrderStatus(orderId, statusData) {
    try {
      const response = await api.post(`/admin/orders/${orderId}/update-status`, statusData);
      return response.data;
    } catch (error) {
      console.error(`OrderService.updateOrderStatus(${orderId}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update order status' };
    }
  }

  async sendToDeskprint(orderId, deskprintData) {
    try {
      const response = await api.post(`/admin/orders/${orderId}/send-to-deskprint`, deskprintData);
      return response.data;
    } catch (error) {
      console.error(`OrderService.sendToDeskprint(${orderId}) failed:`, error);
      throw error.response?.data || { error: 'Failed to send order to deskprint' };
    }
  }

  async getAdminDashboardStats() {
    try {
      const response = await api.get(`/admin/dashboard`);
      return response.data;
    } catch (error) {
      console.error('OrderService.getAdminDashboardStats failed:', error);
      throw error.response?.data || { error: 'Failed to fetch dashboard statistics' };
    }
  }

  // Tambahkan method admin lainnya di sini jika diperlukan, misalnya:
  // async getDeskprintsForDropdown() { ... }
}

export default new OrderService();

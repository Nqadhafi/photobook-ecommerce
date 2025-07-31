import api from './api';

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
      // Endpoint sesuai dengan yang didefinisikan di routes/api.php
      const response = await api.post(`/orders/${orderId}/cancel`); 
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to cancel order' };
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
}

export default new OrderService();
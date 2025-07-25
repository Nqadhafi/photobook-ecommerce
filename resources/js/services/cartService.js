import api from './api';

class CartService {
  async getCart() {
    try {
      const response = await api.get('/cart');
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to fetch cart' };
    }
  }

  async addToCart(item) {
    try {
      const response = await api.post('/cart', item);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to add item to cart' };
    }
  }

  async removeFromCart(cartItemId) {
    try {
      const response = await api.delete(`/cart/${cartItemId}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to remove item from cart' };
    }
  }

  async updateCartItemQuantity(cartItemId, quantity) {
    try {
      const response = await api.put(`/cart/${cartItemId}`, { quantity });
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to update cart item' };
    }
  }
}

export default new CartService();
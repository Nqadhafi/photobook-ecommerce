import cartService from '../../services/cartService';

const state = {
  items: [],
  itemCount: 0,
  total: 0
};

const mutations = {
  SET_CART(state, cartData) {
    state.items = cartData.data || [];
    state.itemCount = state.items.length;
    state.total = state.items.reduce((sum, item) => {
      return sum + (item.quantity * item.product.price);
    }, 0);
  },
  ADD_ITEM(state, item) {
    state.items.push(item);
    state.itemCount = state.items.length;
    state.total += item.quantity * item.product.price;
  },
  REMOVE_ITEM(state, itemId) {
    const index = state.items.findIndex(item => item.id === itemId);
    if (index !== -1) {
      const item = state.items[index];
      state.total -= item.quantity * item.product.price;
      state.items.splice(index, 1);
      state.itemCount = state.items.length;
    }
  }
};

const actions = {
  async loadCart({ commit }) {
    try {
      const cartData = await cartService.getCart();
      commit('SET_CART', cartData);
      return cartData;
    } catch (error) {
      console.error('Failed to load cart:', error);
      commit('SET_CART', { data: [] });
      throw error;
    }
  },

  async addItem({ commit }, item) {
    try {
      const response = await cartService.addToCart(item);
      commit('ADD_ITEM', response.cart_item);
      return response;
    } catch (error) {
      throw error;
    }
  },

  async removeItem({ commit }, itemId) {
    try {
      await cartService.removeFromCart(itemId);
      commit('REMOVE_ITEM', itemId);
      return { message: 'Item removed from cart' };
    } catch (error) {
      throw error;
    }
  }
};

const getters = {
  cartItems: state => state.items,
  cartItemCount: state => state.itemCount,
  cartTotal: state => state.total,
  isEmpty: state => state.itemCount === 0
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
};
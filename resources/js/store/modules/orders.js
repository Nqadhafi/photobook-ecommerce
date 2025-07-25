import orderService from '../../services/orderService';

const state = {
  orders: [],
  currentOrder: null,
  pagination: null,
  loading: false
};

const mutations = {
  SET_ORDERS(state, ordersData) {
    state.orders = ordersData.data || [];
    state.pagination = ordersData.pagination || null;
  },
  SET_CURRENT_ORDER(state, order) {
    state.currentOrder = order;
  },
  ADD_ORDER(state, order) {
    state.orders.unshift(order);
  },
  UPDATE_ORDER(state, order) {
    const index = state.orders.findIndex(o => o.id === order.id);
    if (index !== -1) {
      state.orders.splice(index, 1, order);
    }
    if (state.currentOrder && state.currentOrder.id === order.id) {
      state.currentOrder = order;
    }
  }
};

const actions = {
  async loadOrders({ commit }, params = {}) {
    try {
      const ordersData = await orderService.getOrders(params);
      commit('SET_ORDERS', ordersData);
      return ordersData;
    } catch (error) {
      throw error;
    }
  },

  async loadOrder({ commit }, orderId) {
    try {
      const orderData = await orderService.getOrder(orderId);
      commit('SET_CURRENT_ORDER', orderData.data);
      return orderData.data;
    } catch (error) {
      throw error;
    }
  },

  async checkout({ commit }, orderData) {
    try {
      const response = await orderService.checkout(orderData);
      commit('ADD_ORDER', response.order);
      commit('SET_CURRENT_ORDER', response.order);
      return response;
    } catch (error) {
      throw error;
    }
  }
};

const getters = {
  orders: state => state.orders,
  currentOrder: state => state.currentOrder,
  orderPagination: state => state.pagination
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
};
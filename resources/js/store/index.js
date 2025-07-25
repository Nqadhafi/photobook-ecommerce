import Vue from 'vue';
import Vuex from 'vuex';

// Modules
import auth from './modules/auth';
import cart from './modules/cart';
import products from './modules/products';
import orders from './modules/orders';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    auth,
    cart,
    products,
    orders
  },
  state: {
    loading: false,
    notifications: []
  },
  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading;
    },
    ADD_NOTIFICATION(state, notification) {
      state.notifications.push({
        id: Date.now(),
        ...notification
      });
    },
    REMOVE_NOTIFICATION(state, id) {
      state.notifications = state.notifications.filter(n => n.id !== id);
    }
  },
  actions: {
    setLoading({ commit }, loading) {
      commit('SET_LOADING', loading);
    },
    showNotification({ commit }, notification) {
      commit('ADD_NOTIFICATION', notification);
      // Auto remove after 5 seconds
      setTimeout(() => {
        commit('REMOVE_NOTIFICATION', notification.id);
      }, 5000);
    }
  },
  getters: {
    isLoading: state => state.loading,
    notifications: state => state.notifications
  }
});
import authService from '../../services/authService';

const state = {
  user: null,
  isAuthenticated: false
};

const mutations = {
  SET_USER(state, user) {
    state.user = user;
    state.isAuthenticated = !!user;
  },
  SET_AUTHENTICATED(state, isAuthenticated) {
    state.isAuthenticated = isAuthenticated;
  },
  CLEAR_AUTH(state) {
    state.user = null;
    state.isAuthenticated = false;
  }
};

const actions = {
  async login({ commit }, credentials) {
    try {
      const response = await authService.login(credentials);
      commit('SET_USER', response.user);
      commit('SET_AUTHENTICATED', true);
      return response;
    } catch (error) {
      throw error;
    }
  },

  async register({ commit }, userData) {
    try {
      const response = await authService.register(userData);
      commit('SET_USER', response.user);
      commit('SET_AUTHENTICATED', true);
      return response;
    } catch (error) {
      throw error;
    }
  },

  async logout({ commit }) {
    try {
      await authService.logout();
      commit('CLEAR_AUTH');
      return { message: 'Logged out successfully' };
    } catch (error) {
      // Tetap clear auth meski API error
      commit('CLEAR_AUTH');
      throw error;
    }
  },

  async fetchUser({ commit }) {
    try {
      const user = await authService.getUser();
      commit('SET_USER', user);
      commit('SET_AUTHENTICATED', true);
      return user;
    } catch (error) {
      commit('CLEAR_AUTH');
      throw error;
    }
  }
};

const getters = {
  user: state => state.user,
  isAuthenticated: state => state.isAuthenticated,
  userProfile: state => state.user?.photobook_profile || {}
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
};
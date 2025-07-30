// store/modules/auth.js
import authService from '../../services/authService';

const state = {
  user: null,
  isAuthenticated: false,
  userFetched: false
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
    // state.userFetched = false;
  },
  SET_USER_FETCHED(state, status) {
    state.userFetched = status;
  }
};

const actions = {
  async login({ commit }, credentials) {
    try {
      const response = await authService.login(credentials);
      commit('SET_USER', response.user);
      commit('SET_AUTHENTICATED', true);
      commit('SET_USER_FETCHED', true);
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
      commit('SET_USER_FETCHED', true);
      return response;
    } catch (error) {
      throw error;
    }
  },

  async logout({ commit }) {
    try {
      await authService.logout();
    } catch (error) {
      // Tetap lanjutkan meski error
    } finally {
      commit('CLEAR_AUTH');
    }
  },

async fetchUser({ commit, state }) {
  if (state.userFetched) return;

  try {
    const user = await authService.getUser();
    commit('SET_USER', user);
    commit('SET_AUTHENTICATED', true);
  } catch (error) {
    commit('SET_AUTHENTICATED', false); // ❗ Penting: tetap false
  } finally {
    commit('SET_USER_FETCHED', true); // ✅ Wajib true
  }
},
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
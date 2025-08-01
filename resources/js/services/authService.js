// resources/js/services/authService.js
import api from './api';

class AuthService {
  async login(credentials) {
    try {
      const response = await api.post('/auth/login', credentials);
      localStorage.setItem('token', response.data.token);
      api.defaults.headers.common['Authorization'] = 'Bearer ' + response.data.token;

      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Login failed' };
    }
  }

async register(userData) {
  try {
    const response = await api.post('/auth/register', userData);
    return response.data; // Hanya kembalikan data
  } catch (error) {
    throw error.response?.data || { error: 'Registration failed' };
  }
}

  async logout() {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      // Tetap lanjutkan
    } finally {
      // ❗ Hapus token
      localStorage.removeItem('token');
      delete api.defaults.headers.common['Authorization'];
    }
  }

  async getUser() {
    try {
      const response = await api.get('/auth/user');
      return response.data.user;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to get user data' };
    }
  }


  async updateProfile(profileData) {
    try {
      const response = await api.put('/auth/profile', profileData);
      return response.data;
    } catch (error) {
      throw error.response?.data || { error: 'Failed to update profile' };
    }
  }
}

export default new AuthService();
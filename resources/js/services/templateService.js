import api from './api';

class TemplateService {
  // --- Admin Side (Super Admin) ---

  async getAdminTemplates(params = {}) {
    try {
      const response = await api.get('/admin/templates', { params });
      return response.data;
    } catch (error) {
      console.error('TemplateService.getAdminTemplates failed:', error);
      throw error.response?.data || { error: 'Failed to fetch admin templates' };
    }
  }

  async getAdminTemplate(id) {
    try {
      const response = await api.get(`/admin/templates/${id}`);
      return response.data;
    } catch (error) {
      console.error(`TemplateService.getAdminTemplate(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to fetch admin template details' };
    }
  }

  async createAdminTemplate(templateData) {
    try {
      const response = await api.post('/admin/templates', templateData);
      return response.data;
    } catch (error) {
      console.error('TemplateService.createAdminTemplate failed:', error);
      throw error.response?.data || { error: 'Failed to create template' };
    }
  }

  async updateAdminTemplate(id, templateData) {
    try {
      // Gunakan _method=PUT untuk spoofing jika FormData
      const response = await api.post(`/admin/templates/${id}`, templateData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    } catch (error) {
      console.error(`TemplateService.updateAdminTemplate(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to update template' };
    }
  }

  async deleteAdminTemplate(id) {
    try {
      const response = await api.delete(`/admin/templates/${id}`);
      return response.data;
    } catch (error) {
      console.error(`TemplateService.deleteAdminTemplate(${id}) failed:`, error);
      throw error.response?.data || { error: 'Failed to delete template' };
    }
  }
}

export default new TemplateService();

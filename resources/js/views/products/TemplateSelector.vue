<template>
  <div class="template-selector">
    <b-form-group :label="label" :description="description">
      <b-alert v-if="error" variant="danger" show>
        {{ error }}
      </b-alert>

      <div v-else-if="loading" class="text-center py-3">
        <b-spinner small></b-spinner> Loading templates...
      </div>

      <b-alert v-else-if="!templates || templates.length === 0" variant="warning" show>
        No templates available.
      </b-alert>

      <!-- === COMPACT MODE (chips) === -->
      <div v-else-if="compact" class="chip-grid">
        <button
          v-for="template in templates"
          :key="template.id"
          type="button"
          class="chip"
          :class="{ selected: isSelected(template) }"
          @click="toggleSelect(template)"
        >
          {{ template.name }}
        </button>
      </div>

      <!-- === DEFAULT MODE (cards with image) === -->
      <div v-else class="template-grid">
        <b-card
          v-for="template in templates"
          :key="template.id"
          :class="['template-card', { 'selected': isSelected(template) }]"
          @click="selectTemplate(template)"
          no-body
          class="mb-2"
        >
          <!-- <b-card-img
            :src="getTemplateImage(template)"
            :alt="template.name"
            class="template-image"
            @error="onImageError"
          /> -->
          <b-card-body class="p-2">
            <b-card-title class="h6 mb-0 text-center title-clamp">{{ template.name }}</b-card-title>
          </b-card-body>
        </b-card>
      </div>

      <input
        v-if="required"
        type="hidden"
        :value="selectedTemplate ? selectedTemplate.id : ''"
        :required="required"
      />
    </b-form-group>
  </div>
</template>

<script>
export default {
  name: 'TemplateSelector',
  props: {
    templates: { type: Array, default: () => [] },
    value: { type: [Object, Number, String], default: null },
    label: { type: String, default: 'Select a Template' },
    description: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    compact: { type: Boolean, default: false }   // << NEW
  },
  data() {
    return { internalSelectedTemplate: null };
  },
  computed: {
    selectedTemplate() {
      if (this.value) {
        if (typeof this.value === 'object' && this.value.id) return this.value;
        if (typeof this.value === 'number' || typeof this.value === 'string') {
          return this.templates.find(t => t.id == this.value) || null;
        }
      }
      return this.internalSelectedTemplate;
    }
  },
  methods: {
    selectTemplate(template) {
      this.internalSelectedTemplate = template;
      this.$emit('input', template ? template.id : null);
      this.$emit('selected', template || null);
    },
    toggleSelect(template) {
      // klik template yang sama → tetap terpilih (biar konsisten Add to Cart)
      // tapi tetap emit @selected agar ProductDetail bisa lompat slide
      this.internalSelectedTemplate = template;
      this.$emit('input', template.id);
      this.$emit('selected', template);
    },
    isSelected(template) {
      return this.selectedTemplate && this.selectedTemplate.id === template.id;
    },
    getTemplateImage(template) {
      if (template.sample_image) {
        if (template.sample_image.startsWith('http')) return template.sample_image;
        return '/storage/' + template.sample_image;
      }
      return 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    },
    onImageError(e) {
      e.target.src = 'https://www.aaronfaber.com/wp-content/uploads/2017/03/product-placeholder-wp.jpg';
    }
  },
  watch: {
    templates: {
      handler(newTemplates) {
        if (this.selectedTemplate && !newTemplates.some(t => t.id === this.selectedTemplate.id)) {
          this.selectTemplate(null);
        }
      },
      immediate: true
    }
  }
};
</script>

<style scoped>
/* === COMPACT (chips) === */
.chip-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 8px;
}
.chip {
  appearance: none;
  border: 1px solid #e5e7eb;
  background: #fff;
  color: #111827;
  font-size: .30rem;
  padding: .4rem .6rem;
  border-radius: 2rem;
  cursor: pointer;
  transition: all .15s ease;
  text-align: center;
}
.chip:hover { border-color: #0ea5e9; }
.chip.selected {
  border-color: #0ea5e9;
  background: #e6f6ff;
  color: #0b74c7;
  box-shadow: 0 0 0 3px rgba(14,165,233,.15);
}

/* === DEFAULT (with image) === */
.template-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
}
.template-card {
  cursor: pointer;
  border: 2px solid #e9ecef;
  transition: all .2s ease-in-out;
  height: 100%;
}
.template-card:hover { border-color: #007bff; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
.template-card.selected { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,.25); }
.template-image { height: 80px; object-fit: cover; width: 100%; } /* lebih kecil */
.title-clamp {
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}
</style>

<!-- resources/js/views/upload/FileUpload.vue -->
<template>
  <app-layout>
    <b-container>
      <b-row>
        <b-col cols="12">
          <b-breadcrumb :items="breadcrumbItems"></b-breadcrumb>
        </b-col>
      </b-row>
      <b-row class="mb-4">
        <b-col cols="12">
          <h2>Upload Design Files for Order #{{ order ? order.order_number : '...' }}</h2>
          <p class="text-muted">
            Please upload the design files for each item in your order.
            Allowed file types: JPG, JPEG, PNG, PDF. Max size per file: 10MB.
          </p>
        </b-col>
      </b-row>
      <!-- Loading State -->
      <b-row v-if="loadingOrder">
        <b-col cols="12" class="text-center py-5">
          <b-spinner variant="primary" style="width: 3rem; height: 3rem;"></b-spinner>
          <p class="mt-3">Loading order details...</p>
        </b-col>
      </b-row>
      <!-- Error State (Order Load) -->
      <b-row v-else-if="orderError">
        <b-col cols="12">
          <b-alert variant="danger" show>
            <h4><b-icon icon="exclamation-triangle"></b-icon> Error</h4>
            <p>{{ orderError }}</p>
            <b-button variant="primary" :to="{ name: 'Orders' }" size="sm">
              <b-icon icon="arrow-left"></b-icon> Back to Orders
            </b-button>
          </b-alert>
        </b-col>
      </b-row>
      <!-- Main Upload Content -->
      <b-row v-else-if="order">
        <!-- Order Summary Card -->
        <b-col lg="4" class="mb-4 mb-lg-0">
          <b-card no-body>
            <b-card-header class="bg-primary text-white">
              <b-icon icon="receipt" class="mr-2"></b-icon> Order Summary
            </b-card-header>
            <b-list-group flush>
              <b-list-group-item>
                <strong>Order #:</strong> {{ order.order_number }}
              </b-list-group-item>
              <b-list-group-item>
                <strong>Date:</strong> {{ formatDate(order.created_at) }}
              </b-list-group-item>
              <b-list-group-item>
                <strong>Status:</strong>
                <b-badge :variant="getStatusVariant(order.status)" class="ml-2">
                  {{ formatStatus(order.status) }}
                </b-badge>
              </b-list-group-item>
              <b-list-group-item>
                <strong>Total:</strong> Rp {{ formatCurrency(order.total_amount) }}
              </b-list-group-item>
            </b-list-group>
          </b-card>
          <!-- Order Items Card -->
          <b-card no-body class="mt-4">
            <b-card-header class="bg-info text-white">
              <b-icon icon="list" class="mr-2"></b-icon> Order Items
            </b-card-header>
            <b-list-group flush>
              <b-list-group-item v-for="item in order.items" :key="item.id">
                <div>
                  <h6 class="mb-1">{{ item.product ? item.product.name : 'No Product' }}</h6>
                  <p class="text-muted small mb-1">
                    Template: {{ item.template ? item.template.name : 'N/A' }}<br>
                    Qty: {{ item.quantity }}
                    <span v-if="!item.design_same">(Different designs)</span><br>
                    <span v-if="item.template && item.template.layout_data && item.template.layout_data.photo_slots !== undefined">
                      <b-icon icon="images" class="mr-1"></b-icon>
                      Required Photos:
                      <strong>
                        {{ getTotalPhotoSlotsForItem(item) }}
                        ({{ item.template.layout_data.photo_slots }} per book)
                      </strong>
                    </span>
                    <span v-else-if="item.template">
                      <b-icon icon="images" class="mr-1"></b-icon>
                      Required Photos: <strong>N/A</strong>
                    </span>
                  </p>
                  <!-- Display file count for this item -->
                  <b-badge variant="success" pill>
                    {{ getFilesForItem(item.id).length }} / {{ getTotalPhotoSlotsForItem(item) || 0 }} files uploaded
                  </b-badge>
                </div>
              </b-list-group-item>
            </b-list-group>
          </b-card>
        </b-col>
        <!-- Upload Area - Separate for each item -->
        <b-col lg="8">
          <b-card>
            <b-card-title>
              <b-icon icon="cloud-upload" class="mr-2"></b-icon> Upload Files
            </b-card-title>
            
            <b-alert v-if="!arePhotoSlotsDefinedForAllItems" variant="warning" show class="mb-3">
              <b-icon icon="exclamation-triangle"></b-icon>
              Upload is disabled because template photo slot information is missing for one or more items. Please contact support.
            </b-alert>

            <div v-for="item in order.items" :key="item.id" class="mb-4">
              <h5 class="mb-3">{{ item.product ? item.product.name : 'No Product' }} ({{ item.quantity }}x)</h5>
              
              <div class="drop-zone mb-3"
                :class="{ 'dragover': isDragOver[item.id] }"
                @dragover.prevent
                @dragenter.prevent="isDragOver[item.id] = true"
                @dragleave.prevent="isDragOver[item.id] = false"
                @drop.prevent="(event) => onDrop(event, item.id)"
              >
                <input
                  type="file"
                  :ref="`fileInput-${item.id}`"
                  multiple
                  accept=".jpg,.jpeg,.png,.pdf"
                  class="d-none"
                  @change="(event) => onFileInputChange(event, item.id)"
                >
                <div class="text-center py-4">
                  <b-icon icon="cloud-arrow-up" font-scale="1.5" class="mb-2"></b-icon>
                  <p class="mb-1">Drag & drop files here for this item</p>
                  <p class="text-muted small mb-2">or</p>
                  <b-button variant="outline-primary" size="sm" @click="() => $refs[`fileInput-${item.id}`][0].click()" :disabled="!arePhotoSlotsDefinedForAllItems">
                    <b-icon icon="folder"></b-icon> Browse Files
                  </b-button>
                  
                  <p class="text-muted small mt-2 mb-0">
                    Required: <strong>{{ getTotalPhotoSlotsForItem(item) }}</strong> |
                    Uploaded: <strong>{{ getFilesForItem(item.id).length }}</strong>
                  </p>
                </div>
              </div>

              <!-- Selected Files List for this item -->
              <div v-if="getFilesForItem(item.id).length > 0">
                <b-list-group>
                  <b-list-group-item
                    v-for="(fileObj, index) in getFilesForItem(item.id)"
                    :key="index"
                    class="d-flex justify-content-between align-items-center py-2"
                  >
                    <div>
                      <strong>{{ fileObj.file.name }}</strong>
                      <br>
                      <small class="text-muted">
                        Size: {{ formatFileSize(fileObj.file.size) }}
                      </small>
                    </div>
                    <b-button
                      variant="outline-danger"
                      size="sm"
                      @click="() => removeFileForItem(item.id, index)"
                    >
                      <b-icon icon="x"></b-icon> Remove
                    </b-button>
                  </b-list-group-item>
                </b-list-group>
              </div>
              
              <hr v-if="item !== order.items[order.items.length - 1]">
            </div>

            <!-- Upload Controls -->
            <div class="mt-4">
              <b-alert v-if="uploadError" variant="danger" dismissible @dismissed="uploadError = null">
                {{ uploadError }}
                <div v-if="failedFiles.length > 0" class="mt-2">
                  <strong>Failed files:</strong>
                  <ul class="mb-0">
                    <li v-for="(fileObj, idx) in failedFiles" :key="idx">
                      {{ fileObj.file.name }} (Item ID: {{ fileObj.order_item_id }})
                    </li>
                  </ul>
                  <b-button variant="warning" size="sm" @click="retryFailedUploads" class="mt-2">
                    <b-icon icon="arrow-repeat"></b-icon> Retry Failed Uploads
                  </b-button>
                </div>
              </b-alert>
              
              <b-button
                variant="success"
                size="lg"
                block
                @click="uploadFiles"
                :disabled="isUploading || filesToUpload.length === 0 || !areAllItemsFullyUploaded || !arePhotoSlotsDefinedForAllItems"
              >
                <b-spinner v-if="isUploading" small class="mr-2"></b-spinner>
                <b-icon v-else icon="cloud-upload" class="mr-2"></b-icon>
                {{ isUploading ? `Uploading ${uploadedCount}/${filesToUpload.length}...` : 'Upload All Files' }}
              </b-button>
              
              <b-button
                v-if="uploadedSuccessfully"
                variant="primary"
                size="lg"
                block
                :to="{ name: 'OrderDetail', params: { id: order.id } }"
                class="mt-2"
              >
                <b-icon icon="check-circle" class="mr-2"></b-icon> View Order Details
              </b-button>
            </div>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import orderService from '../../services/orderService';

export default {
  name: 'FileUpload',
  data() {
    return {
      order: null,
      loadingOrder: false,
      orderError: null,
      
      // filesToUpload now stores all files for all items
      filesToUpload: [], // Array of { file: File, order_item_id: Number }
      isDragOver: {}, // Object to track dragover state for each item
      
      isUploading: false,
      uploadedCount: 0,
      uploadError: null,
      uploadedSuccessfully: false,
      failedFiles: [] // Array of { file: File, order_item_id: Number } that failed
    };
  },
  computed: {
    breadcrumbItems() {
      return [
        { text: 'Dashboard', to: { name: 'Dashboard' } },
        { text: 'Orders', to: { name: 'Orders' } },
        { text: `Order #${this.order ? this.order.order_number : '...'}`, to: { name: 'OrderDetail', params: { id: this.$route.params.id } } },
        { text: 'Upload Files', active: true }
      ];
    },
    /**
     * Checks if photo slot information is defined for all order items.
     * @returns {boolean} True if all items have defined photo slots, false otherwise.
     */
    arePhotoSlotsDefinedForAllItems() {
      if (!this.order || !this.order.items || this.order.items.length === 0) {
        return false;
      }

      return this.order.items.every(item => {
        return item.template &&
               item.template.layout_data &&
               item.template.layout_data.photo_slots !== undefined &&
               item.template.layout_data.photo_slots !== null;
      });
    },
    /**
     * Checks if all items have the required number of files uploaded.
     * @returns {boolean} True if all items are fully uploaded, false otherwise.
     */
    areAllItemsFullyUploaded() {
      if (!this.order || !this.order.items || !this.arePhotoSlotsDefinedForAllItems) {
        return false;
      }

      return this.order.items.every(item => {
        const requiredSlots = this.getTotalPhotoSlotsForItem(item);
        const uploadedFiles = this.getFilesForItem(item.id).length;
        return requiredSlots !== null && uploadedFiles === requiredSlots;
      });
    },
    /**
     * Calculates the total number of photo files required for the entire order.
     * @returns {number|null} Total files required, or null if data is missing for any item.
     */
    totalPhotoFilesRequired() {
      if (!this.order || !this.order.items) {
        return null;
      }

      let total = 0;
      for (const item of this.order.items) {
        const slotsForItem = this.getTotalPhotoSlotsForItem(item);
        if (slotsForItem === null) {
          return null;
        }
        total += slotsForItem;
      }
      return total;
    }
  },
  async created() {
    await this.loadOrder();
    // Initialize isDragOver for each item
    if (this.order && this.order.items) {
      const dragOverState = {};
      this.order.items.forEach(item => {
        dragOverState[item.id] = false;
      });
      this.isDragOver = dragOverState;
    }
  },
  methods: {
    async loadOrder() {
      this.loadingOrder = true;
      this.orderError = null;
      try {
        const orderId = this.$route.params.id;
        const response = await orderService.getOrder(orderId);
        this.order = response.data;
        
        // Validasi status order
        if (!['paid', 'file_upload'].includes(this.order.status)) {
            this.orderError = 'You cannot upload files for this order at this time.';
            return;
        }
      } catch (error) {
        console.error('Failed to load order:', error);
        this.orderError = error.message || 'Failed to load order details. Please try again.';
      } finally {
        this.loadingOrder = false;
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    },
    getStatusVariant(status) {
      const statusMap = {
        'pending': 'warning',
        'paid': 'success',
        'file_upload': 'info',
        'processing': 'primary',
        'ready': 'success',
        'completed': 'success',
        'cancelled': 'danger'
      };
      return statusMap[status] || 'secondary';
    },
    formatStatus(status) {
       const statusLabels = {
        'pending': 'Pending Payment',
        'paid': 'Payment Received',
        'file_upload': 'Awaiting Files',
        'processing': 'In Production',
        'ready': 'Ready for Pickup',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
      };
      return statusLabels[status] || status;
    },
    /**
     * Calculates the total number of photo slots required for an order item.
     * @param {Object} item - The order item object.
     * @returns {number|null} Total slots required, or null if data is missing.
     */
    getTotalPhotoSlotsForItem(item) {
      if (!item.template || !item.template.layout_data) {
        return null;
      }

      const photoSlotsPerBook = item.template.layout_data.photo_slots;
      if (photoSlotsPerBook === undefined || photoSlotsPerBook === null) {
        return null;
      }

      const numberOfSets = item.design_same ? 1 : item.quantity;
      return photoSlotsPerBook * numberOfSets;
    },
    /**
     * Gets the list of files uploaded for a specific order item.
     * @param {number} orderItemId - The ID of the order item.
     * @returns {Array} Array of file objects for the item.
     */
    getFilesForItem(orderItemId) {
      return this.filesToUpload.filter(f => f.order_item_id === orderItemId);
    },
    onDrop(event, orderItemId) {
      this.isDragOver[orderItemId] = false;
      const files = event.dataTransfer.files;
      this.handleDroppedFiles(files, orderItemId);
    },
    onFileInputChange(event, orderItemId) {
      const files = event.target.files;
      this.handleDroppedFiles(files, orderItemId);
      // Reset input file
      event.target.value = '';
    },
    handleDroppedFiles(files, orderItemId) {
      const item = this.order.items.find(i => i.id === orderItemId);
      if (!item) {
        alert('Invalid order item.');
        return;
      }

      const requiredSlots = this.getTotalPhotoSlotsForItem(item);
      if (requiredSlots === null) {
        alert('Photo slot information is not available for this item.');
        return;
      }

      const currentFiles = this.getFilesForItem(orderItemId).length;
      const remainingSlots = requiredSlots - currentFiles;
      
      if (remainingSlots <= 0) {
        alert(`You have already uploaded the required number of files (${requiredSlots}) for this item.`);
        return;
      }

      const filesToProcess = Math.min(files.length, remainingSlots);

      for (let i = 0; i < filesToProcess; i++) {
        const file = files[i];
        
        // Validasi file (ukuran, tipe)
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        if (file.size > maxSize) {
          this.$store.dispatch('showNotification', {
            title: 'File Too Large',
            message: `File "${file.name}" exceeds the 10MB limit.`,
            type: 'danger'
          });
          continue;
        }

        if (!allowedTypes.includes(file.type)) {
          this.$store.dispatch('showNotification', {
            title: 'Invalid File Type',
            message: `File "${file.name}" is not a JPG, JPEG, PNG, or PDF.`,
            type: 'danger'
          });
          continue;
        }

        // Add file to the list
        this.filesToUpload.push({
          file: file,
          order_item_id: orderItemId
        });
      }
    },
    removeFileForItem(orderItemId, fileIndexInItem) {
      // Find the actual index in the main filesToUpload array
      const itemFiles = this.getFilesForItem(orderItemId);
      const fileToRemove = itemFiles[fileIndexInItem];
      const globalIndex = this.filesToUpload.findIndex(f => 
        f.order_item_id === fileToRemove.order_item_id && f.file.name === fileToRemove.file.name
      );
      
      if (globalIndex !== -1) {
        this.filesToUpload.splice(globalIndex, 1);
      }
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    async uploadFiles() {
      if (this.filesToUpload.length === 0) {
        this.uploadError = 'No files selected for upload.';
        return;
      }

      // Validate total file count
      if (this.totalPhotoFilesRequired !== null && this.filesToUpload.length !== this.totalPhotoFilesRequired) {
         this.uploadError = `Please upload exactly ${this.totalPhotoFilesRequired} files for the entire order. You have selected ${this.filesToUpload.length}.`;
         return;
      }

      this.isUploading = true;
      this.uploadError = null;
      this.uploadedCount = 0;
      this.uploadedSuccessfully = false;
      this.failedFiles = [];

      try {
        const filesPayload = this.filesToUpload.map(fObj => ({
          order_item_id: fObj.order_item_id,
          file: fObj.file
        }));

        const response = await orderService.uploadFiles(this.order.id, filesPayload);

        this.uploadedSuccessfully = true;
        this.isUploading = false;

        this.$store.dispatch('showNotification', {
          title: 'Upload Successful',
          message: response.message || 'Files uploaded successfully!',
          type: 'success'
        });

      } catch (error) {
        console.error('Upload failed:', error);
        this.isUploading = false;

        if (error && error.error) {
          this.uploadError = error.error;
        } else {
          this.uploadError = 'An error occurred during upload. Please try again.';
        }

        this.failedFiles = [...this.filesToUpload];
      }
    },
    async retryFailedUploads() {
      if (this.failedFiles.length === 0) return;

      this.filesToUpload = [...this.failedFiles];
      this.failedFiles = [];
      await this.uploadFiles();
    }
  }
};
</script>

<style scoped>
.drop-zone {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 15px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.3s ease, background-color 0.3s ease;
  background-color: #f9f9f9;
}
.drop-zone.dragover {
  border-color: #007bff;
  background-color: #e3f2fd;
}
.drop-zone:hover {
  border-color: #007bff;
  background-color: #f0f8ff;
}
</style>

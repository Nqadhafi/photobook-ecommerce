<!-- resources/js/views/upload/FileUpload.vue -->
<template>
  <app-layout>
    <b-container>
      <b-row class="justify-content-center">
        <b-col md="8" lg="6">
          <b-card class="text-center mt-5">
            <b-icon icon="cloud-slash" font-scale="3" variant="secondary" class="mb-3"></b-icon>
            <h3>Upload Feature Updated</h3>
            <p class="lead">
              The in-app file upload feature is no longer used.
            </p>
            <b-alert variant="info" show class="text-left">
              <h5><b-icon icon="info-circle"></b-icon> New Process:</h5>
              <ol>
                <li>Complete your payment.</li>
                <li>Wait for a notification (in-app & WhatsApp) confirming your Google Drive folder is ready.</li>
                <li>Click the "Open Google Drive Folder" button on the Order Details page.</li>
                <li>Upload your design files directly to the Google Drive folder.</li>
                <li>Our team will verify your files after you finish uploading.</li>
              </ol>
            </b-alert>
            <b-button
              v-if="orderIdFromRoute"
              :to="{ name: 'OrderDetail', params: { id: orderIdFromRoute } }"
              variant="primary"
              class="mt-2"
            >
              <b-icon icon="arrow-left"></b-icon> Back to Order Details
            </b-button>
            <b-button v-else :to="{ name: 'Orders' }" variant="primary" class="mt-2">
              <b-icon icon="list"></b-icon> View All Orders
            </b-button>
          </b-card>
        </b-col>
      </b-row>
    </b-container>
  </app-layout>
</template>

<script>
export default {
  name: 'FileUploadDisabled',
  computed: {
    orderIdFromRoute() {
      return this.$route.params.id;
    }
  }
};
</script>

<style scoped>
/* Gaya tambahan jika diperlukan */
</style>

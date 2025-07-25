<!-- resources/js/components/shared/LoadingSpinner.vue -->
<template>
  <div class="loading-spinner d-flex justify-content-center align-items-center">
    <b-spinner variant="primary" style="width: 3rem; height: 3rem;" />
  </div>
</template>

<script>
export default {
  name: 'LoadingSpinner'
}
</script>

<style scoped>
.loading-spinner {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  z-index: 9999;
}
</style>
<!-- resources/js/views/dashboard/Dashboard.vue -->
<template>
  <app-layout>
    <b-container>
    <b-row>
      <b-col>
        <h1>Welcome, {{ userName }}!</h1>
        <p class="lead">Create beautiful photobooks with our easy-to-use templates.</p>
      </b-col>
    </b-row>

    <b-row class="mt-4">
      <b-col md="4">
        <b-card class="h-100 text-center">
          <b-icon icon="images" font-scale="2" variant="primary"></b-icon>
          <b-card-title class="mt-3">Browse Products</b-card-title>
          <b-card-text>
            Explore our collection of photobook products and templates.
          </b-card-text>
          <b-button variant="primary" :to="{ name: 'Products' }">
            View Products
          </b-button>
        </b-card>
      </b-col>

      <b-col md="4">
        <b-card class="h-100 text-center">
          <b-icon icon="cart" font-scale="2" variant="success"></b-icon>
          <b-card-title class="mt-3">Your Cart</b-card-title>
          <b-card-text>
            You have {{ cartItemCount }} items in your cart.
          </b-card-text>
          <b-button variant="success" :to="{ name: 'Cart' }">
            View Cart
          </b-button>
        </b-card>
      </b-col>

      <b-col md="4">
        <b-card class="h-100 text-center">
          <b-icon icon="list" font-scale="2" variant="info"></b-icon>
          <b-card-title class="mt-3">Order History</b-card-title>
          <b-card-text>
            Track your previous orders and their status.
          </b-card-text>
          <b-button variant="info" :to="{ name: 'Orders' }">
            View Orders
          </b-button>
        </b-card>
      </b-col>
    </b-row>

    <b-row class="mt-4" v-if="recentOrders.length > 0">
      <b-col>
        <b-card>
          <b-card-title>Recent Orders</b-card-title>
          <b-table
            :items="recentOrders"
            :fields="orderFields"
            striped
            hover
            small
          >
            <template #cell(status)="data">
              <b-badge :variant="getStatusVariant(data.item.status)">
                {{ data.item.status }}
              </b-badge>
            </template>
            <template #cell(actions)="data">
              <b-button
                size="sm"
                variant="outline-primary"
                :to="{ name: 'OrderDetail', params: { id: data.item.id } }"
              >
                View
              </b-button>
            </template>
          </b-table>
        </b-card>
      </b-col>
    </b-row>
    </b-container>
  </app-layout>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'Dashboard',
  data() {
    return {
      recentOrders: [],
      orderFields: [
        { key: 'order_number', label: 'Order #' },
        { key: 'total_amount', label: 'Amount' },
        { key: 'status', label: 'Status' },
        { key: 'created_at', label: 'Date' },
        { key: 'actions', label: 'Actions' }
      ]
    };
  },
  computed: {
    ...mapGetters('auth', ['user']),
    ...mapGetters('cart', ['cartItemCount']),
    userName() {
      return this.user ? this.user.name : 'Guest';
    }
  },
  methods: {
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
    }
  }
};
</script>
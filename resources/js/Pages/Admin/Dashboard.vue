<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    dailySalesTotal: {
        type: Number,
        default: 0,
    },
    pendingBoxOrders: {
        type: Array,
        default: () => [],
    },
    boxOrderStats: {
        type: Object,
        default: () => ({}),
    },
    todaySessions: {
        type: Array,
        default: () => [],
    },
    quickStats: {
        type: Object,
        default: () => ({}),
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Penjualan Hari Ini</h3>
                <p class="text-2xl font-bold text-green-600 mt-2">
                    {{ formatCurrency(dailySalesTotal) }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Order Box Pending</h3>
                <p class="text-2xl font-bold text-orange-600 mt-2">
                    {{ pendingBoxOrders.length }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Partners</h3>
                <p class="text-2xl font-bold text-blue-600 mt-2">
                    {{ quickStats.total_partners || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Karyawan</h3>
                <p class="text-2xl font-bold text-purple-600 mt-2">
                    {{ quickStats.total_employees || 0 }}
                </p>
            </div>
        </div>

        <!-- Today's Sessions -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">Sesi Toko Hari Ini</h3>
            </div>
            <div class="p-6">
                <div v-if="todaySessions.length === 0" class="text-gray-500 text-center py-8">
                    Belum ada sesi toko hari ini
                </div>
                <table v-else class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500">
                            <th class="pb-3">Karyawan</th>
                            <th class="pb-3">Waktu Buka</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Total Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in todaySessions" :key="session.id" class="border-t">
                            <td class="py-3">{{ session.user?.name }}</td>
                            <td class="py-3">{{ session.opened_at }}</td>
                            <td class="py-3">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        session.status === 'open'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    ]"
                                >
                                    {{ session.status === 'open' ? 'Aktif' : 'Tutup' }}
                                </span>
                            </td>
                            <td class="py-3">{{ session.consignments?.length || 0 }} item</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Box Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">Order Box Pending</h3>
            </div>
            <div class="p-6">
                <div v-if="pendingBoxOrders.length === 0" class="text-gray-500 text-center py-8">
                    Tidak ada order pending
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in pendingBoxOrders"
                        :key="order.id"
                        class="flex items-center justify-between p-4 bg-orange-50 rounded-lg"
                    >
                        <div>
                            <p class="font-medium">{{ order.customer_name }}</p>
                            <p class="text-sm text-gray-600">
                                {{ order.template?.name }} x {{ order.quantity }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-orange-600">
                                {{ formatCurrency(order.total_price) }}
                            </p>
                            <p class="text-xs text-gray-500">{{ order.pickup_datetime }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    heavyMealTemplates: {
        type: Array,
        default: () => [],
    },
    snackBoxTemplates: {
        type: Array,
        default: () => [],
    },
    todayOrders: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Menunggu',
        paid: 'Dibayar',
        completed: 'Selesai',
        cancelled: 'Batal',
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Box Order" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Box Order</h2>
        </template>

        <!-- Today's Orders -->
        <div v-if="todayOrders.length > 0" class="mb-6">
            <h3 class="font-medium text-gray-700 mb-3">📋 Order Hari Ini</h3>
            <div class="space-y-2">
                <div
                    v-for="order in todayOrders"
                    :key="order.id"
                    class="bg-white rounded-lg shadow p-3 flex justify-between items-center"
                >
                    <div>
                        <p class="font-medium">{{ order.customer_name }}</p>
                        <p class="text-sm text-gray-500">
                            {{ order.template?.name }} x {{ order.quantity }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(order.status)]">
                            {{ getStatusLabel(order.status) }}
                        </span>
                        <p class="text-sm font-medium mt-1">{{ formatCurrency(order.total_price) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Heavy Meal Section -->
        <div class="mb-6">
            <h3 class="font-medium text-gray-700 mb-3">🍱 Makan Berat</h3>
            <div v-if="heavyMealTemplates.length === 0" class="text-gray-500 text-center py-4 bg-gray-50 rounded-lg">
                Tidak ada template aktif
            </div>
            <div v-else class="grid grid-cols-2 gap-3">
                <Link
                    v-for="template in heavyMealTemplates"
                    :key="template.id"
                    :href="`/pos/box/create/${template.id}`"
                    class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow"
                >
                    <h4 class="font-medium mb-1">{{ template.name }}</h4>
                    <p class="text-lg font-bold text-green-600">{{ formatCurrency(template.price) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ template.items_json?.length || 0 }} item
                    </p>
                </Link>
            </div>
        </div>

        <!-- Snack Box Section -->
        <div>
            <h3 class="font-medium text-gray-700 mb-3">🍪 Snack Box</h3>
            <div v-if="snackBoxTemplates.length === 0" class="text-gray-500 text-center py-4 bg-gray-50 rounded-lg">
                Tidak ada template aktif
            </div>
            <div v-else class="grid grid-cols-2 gap-3">
                <Link
                    v-for="template in snackBoxTemplates"
                    :key="template.id"
                    :href="`/pos/box/create/${template.id}`"
                    class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow"
                >
                    <h4 class="font-medium mb-1">{{ template.name }}</h4>
                    <p class="text-lg font-bold text-green-600">{{ formatCurrency(template.price) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ template.items_json?.length || 0 }} item
                    </p>
                </Link>
            </div>
        </div>
    </EmployeeLayout>
</template>

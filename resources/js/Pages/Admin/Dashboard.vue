<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatMoney } from '@/utils/formatMoney';

const props = defineProps({
    dailySalesTotal: {
        type: Number,
        default: 0,
    },
    salesTrend: {
        type: Object,
        default: () => ({
            today: 0,
            yesterday: 0,
            trend_percent: 0,
            trend_direction: 'flat',
        }),
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
    dailyRevenue: {
        type: Array,
        default: () => [],
    },
    weeklyRevenue: {
        type: Array,
        default: () => [],
    },
    monthlyRevenue: {
        type: Array,
        default: () => [],
    },
    globalProfit: {
        type: Object,
        default: () => ({}),
    },
    sessionHistory: {
        type: Array,
        default: () => [],
    },
    boxOrderHistory: {
        type: Array,
        default: () => [],
    },
});

const chartTab = ref('daily');
const hoveredPoint = ref(null);

// Get data based on selected tab
const chartData = computed(() => {
    switch (chartTab.value) {
        case 'daily':
            return props.dailyRevenue;
        case 'weekly':
            return props.weeklyRevenue;
        case 'monthly':
            return props.monthlyRevenue;
        default:
            return props.dailyRevenue;
    }
});

// Calculate max value for chart scaling
const chartMax = computed(() => Math.max(...chartData.value.map(d => d.revenue), 1));

// SVG Line Chart calculations
const chartWidth = 600;
const chartHeight = 200;
const padding = 40;

const linePoints = computed(() => {
    if (chartData.value.length === 0) return '';
    
    const points = chartData.value.map((d, i) => {
        const x = padding + (i * (chartWidth - padding * 2)) / (chartData.value.length - 1 || 1);
        const y = chartHeight - padding - ((d.revenue / chartMax.value) * (chartHeight - padding * 2));
        return `${x},${y}`;
    });
    
    return points.join(' ');
});

const areaPath = computed(() => {
    if (chartData.value.length === 0) return '';
    
    const points = chartData.value.map((d, i) => {
        const x = padding + (i * (chartWidth - padding * 2)) / (chartData.value.length - 1 || 1);
        const y = chartHeight - padding - ((d.revenue / chartMax.value) * (chartHeight - padding * 2));
        return { x, y };
    });
    
    let path = `M ${points[0].x},${chartHeight - padding}`;
    path += ` L ${points[0].x},${points[0].y}`;
    points.forEach((p, i) => {
        if (i > 0) path += ` L ${p.x},${p.y}`;
    });
    path += ` L ${points[points.length - 1].x},${chartHeight - padding}`;
    path += ' Z';
    
    return path;
});

// Format date label for chart
const formatDateLabel = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = date.getDate();
    const month = date.getMonth() + 1;
    return `${day}/${month}`;
};

const getStatusBadge = (status) => {
    const badges = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
        open: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Menunggu',
        paid: 'Lunas',
        completed: 'Selesai',
        cancelled: 'Batal',
        open: 'Aktif',
        closed: 'Tutup',
    };
    return texts[status] || status;
};

// Detail Modal States
const showSessionModal = ref(false);
const selectedSession = ref(null);
const showOrderModal = ref(false);
const selectedOrder = ref(null);

// Open Session Detail Modal
const openSessionDetail = (session) => {
    selectedSession.value = session;
    showSessionModal.value = true;
};

// Close Session Modal
const closeSessionModal = () => {
    showSessionModal.value = false;
    selectedSession.value = null;
};

// Open Order Detail Modal
const openOrderDetail = (order) => {
    selectedOrder.value = order;
    showOrderModal.value = true;
};

// Close Order Modal
const closeOrderModal = () => {
    showOrderModal.value = false;
    selectedOrder.value = null;
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
                    {{ formatMoney(dailySalesTotal) }}
                </p>
                <div class="mt-2 flex items-center gap-1">
                    <span 
                        v-if="salesTrend.trend_direction !== 'flat'"
                        :class="[
                            'text-sm font-medium flex items-center gap-1',
                            salesTrend.trend_direction === 'up' ? 'text-green-600' : 'text-red-600'
                        ]"
                    >
                        <span v-if="salesTrend.trend_direction === 'up'">↑</span>
                        <span v-else>↓</span>
                        {{ Math.abs(salesTrend.trend_percent) }}%
                    </span>
                    <span v-else class="text-sm text-gray-400">— 0%</span>
                    <span class="text-xs text-gray-400">vs kemarin</span>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Profit Hari Ini</h3>
                <p class="text-2xl font-bold text-blue-600 mt-2">
                    {{ formatMoney(globalProfit.today_profit || 0) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Konsinyasi: {{ formatMoney(globalProfit.today_session_profit || 0) }} | 
                    Box: {{ formatMoney(globalProfit.today_box_profit || 0) }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Order Box Pending</h3>
                <p class="text-2xl font-bold text-orange-600 mt-2">
                    {{ boxOrderStats.pending_orders || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Partners</h3>
                <p class="text-2xl font-bold text-purple-600 mt-2">
                    {{ quickStats.total_partners || 0 }}
                </p>
            </div>
        </div>

        <!-- Revenue Line Chart -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">📈 Trend Penjualan</h3>
                <div class="flex gap-2">
                    <button
                        @click="chartTab = 'daily'"
                        :class="[
                            'px-4 py-1 rounded-full text-sm transition-colors',
                            chartTab === 'daily'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        Harian
                    </button>
                    <button
                        @click="chartTab = 'weekly'"
                        :class="[
                            'px-4 py-1 rounded-full text-sm transition-colors',
                            chartTab === 'weekly'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        Mingguan
                    </button>
                    <button
                        @click="chartTab = 'monthly'"
                        :class="[
                            'px-4 py-1 rounded-full text-sm transition-colors',
                            chartTab === 'monthly'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        ]"
                    >
                        Bulanan
                    </button>
                </div>
            </div>
            <div class="p-6">
                <!-- SVG Line Chart -->
                <svg :viewBox="`0 0 ${chartWidth} ${chartHeight + 20}`" class="w-full h-72">
                    <!-- Grid lines -->
                    <line 
                        v-for="i in 4" 
                        :key="'grid-' + i"
                        :x1="padding" 
                        :y1="padding + ((i - 1) * (chartHeight - padding * 2) / 3)"
                        :x2="chartWidth - padding" 
                        :y2="padding + ((i - 1) * (chartHeight - padding * 2) / 3)"
                        stroke="#e5e7eb" 
                        stroke-width="1"
                    />
                    
                    <!-- Area fill -->
                    <path 
                        :d="areaPath"
                        fill="url(#gradient)"
                        opacity="0.3"
                    />
                    
                    <!-- Line -->
                    <polyline
                        :points="linePoints"
                        fill="none"
                        stroke="#3b82f6"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    
                    <!-- Data points -->
                    <g v-for="(d, i) in chartData" :key="i">
                        <!-- Invisible larger circle for easier hover -->
                        <circle
                            :cx="padding + (i * (chartWidth - padding * 2)) / (chartData.length - 1 || 1)"
                            :cy="chartHeight - padding - ((d.revenue / chartMax) * (chartHeight - padding * 2))"
                            r="15"
                            fill="transparent"
                            class="cursor-pointer"
                            @mouseenter="hoveredPoint = { ...d, index: i }"
                            @mouseleave="hoveredPoint = null"
                        />
                        <!-- Visible data point -->
                        <circle
                            :cx="padding + (i * (chartWidth - padding * 2)) / (chartData.length - 1 || 1)"
                            :cy="chartHeight - padding - ((d.revenue / chartMax) * (chartHeight - padding * 2))"
                            r="5"
                            fill="#3b82f6"
                            stroke="white"
                            stroke-width="2"
                            class="pointer-events-none"
                        />
                        <!-- Label -->
                        <text
                            :x="padding + (i * (chartWidth - padding * 2)) / (chartData.length - 1 || 1)"
                            :y="chartHeight - 10"
                            text-anchor="middle"
                            class="text-xs fill-gray-600 pointer-events-none"
                            font-weight="500"
                        >
                            {{ d.label }}
                        </text>
                        <!-- Date below label (for daily view) -->
                        <text
                            v-if="chartTab === 'daily' && d.full_date"
                            :x="padding + (i * (chartWidth - padding * 2)) / (chartData.length - 1 || 1)"
                            :y="chartHeight + 5"
                            text-anchor="middle"
                            class="text-[10px] fill-gray-400 pointer-events-none"
                        >
                            {{ formatDateLabel(d.full_date) }}
                        </text>
                        <!-- Date range for weekly/monthly view -->
                        <text
                            v-if="chartTab !== 'daily' && d.full_date"
                            :x="padding + (i * (chartWidth - padding * 2)) / (chartData.length - 1 || 1)"
                            :y="chartHeight + 5"
                            text-anchor="middle"
                            class="text-[10px] fill-gray-400 pointer-events-none"
                        >
                            {{ formatDateLabel(d.full_date) }}
                        </text>
                    </g>
                    
                    <!-- Gradient definition -->
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.4" />
                            <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                </svg>
                
                <!-- Tooltip -->
                <div
                    v-if="hoveredPoint"
                    class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg transition-all"
                >
                    <div class="text-sm font-semibold text-gray-700 mb-2">
                        {{ hoveredPoint.label }}
                        <span v-if="hoveredPoint.full_date && chartTab === 'daily'" class="text-gray-500 font-normal">
                            ({{ hoveredPoint.full_date }})
                        </span>
                        <span v-if="hoveredPoint.date_range" class="text-gray-500 font-normal block text-xs">
                            {{ hoveredPoint.date_range }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="text-gray-500 text-xs">Total</div>
                            <div class="font-bold text-blue-600">{{ formatMoney(hoveredPoint.revenue) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 text-xs">Konsinyasi</div>
                            <div class="font-semibold text-green-600">{{ formatMoney(hoveredPoint.session_revenue) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 text-xs">Box Order</div>
                            <div class="font-semibold text-purple-600">{{ formatMoney(hoveredPoint.box_revenue) }}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Info text when no hover -->
                <div v-else class="mt-4 text-center text-sm text-gray-400">
                    Arahkan kursor ke titik data untuk melihat detail
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Session History -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">📋 Riwayat Sesi</h3>
                    <a
                        href="/admin/reports/daily"
                        target="_blank"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        📥 Laporan Hari Ini
                    </a>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div v-if="sessionHistory.length === 0" class="text-gray-500 text-center py-8">
                        Belum ada riwayat sesi
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="session in sessionHistory"
                            :key="session.id"
                            class="border rounded-lg p-3 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium text-sm">{{ session.user?.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ new Date(session.opened_at).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </p>
                                </div>
                                <span :class="['px-2 py-1 text-xs rounded-full', getStatusBadge(session.status)]">
                                    {{ getStatusText(session.status) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs mb-2">
                                <div>
                                    <span class="text-gray-500">Item:</span>
                                    <span class="font-medium"> {{ session.consignments?.length || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Terjual:</span>
                                    <span class="font-medium text-green-600"> {{ session.consignments?.reduce((sum, c) => sum + (c.qty_sold || 0), 0) || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Revenue:</span>
                                    <span class="font-medium text-blue-600"> {{ formatMoney(session.consignments?.reduce((sum, c) => sum + (c.subtotal_income || 0), 0) || 0) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t">
                                <button
                                    @click="openSessionDetail(session)"
                                    class="text-xs text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100"
                                >
                                    👁️ Detail
                                </button>
                                <a
                                    v-if="session.status === 'closed'"
                                    :href="`/admin/reports/session/${session.id}`"
                                    target="_blank"
                                    class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
                                >
                                    📄 Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Box Order History -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">📦 Riwayat Box Order</h3>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div v-if="boxOrderHistory.length === 0" class="text-gray-500 text-center py-8">
                        Belum ada riwayat order
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="order in boxOrderHistory"
                            :key="order.id"
                            :class="[
                                'border rounded-lg p-3 transition',
                                order.status === 'pending' ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium text-sm">{{ order.customer_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        Pickup: {{ new Date(order.pickup_datetime).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                    </p>
                                </div>
                                <span :class="['px-2 py-1 text-xs rounded-full', getStatusBadge(order.status)]">
                                    {{ getStatusText(order.status) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs mb-2">
                                <div>
                                    <span class="text-gray-500">Item:</span>
                                    <span class="font-medium"> {{ order.items?.length || 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Qty:</span>
                                    <span class="font-medium"> {{ order.quantity || 1 }} box</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total:</span>
                                    <span class="font-medium text-green-600"> {{ formatMoney(order.total_price) }}</span>
                                </div>
                            </div>
                            <!-- Show items summary -->
                            <div v-if="order.items?.length > 0" class="text-xs text-gray-500 mb-2">
                                {{ order.items.map(i => i.product_name).join(', ') }}
                            </div>
                            <!-- Show cancellation reason if cancelled -->
                            <div v-if="order.status === 'cancelled' && order.cancellation_reason" class="text-xs text-red-600 mb-2 p-2 bg-red-50 rounded">
                                Alasan: {{ order.cancellation_reason }}
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t">
                                <button
                                    @click="openOrderDetail(order)"
                                    class="text-xs text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100"
                                >
                                    👁️ Detail
                                </button>
                                <a
                                    :href="`/pos/box/${order.id}/receipt`"
                                    target="_blank"
                                    class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
                                >
                                    📄 Kwitansi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box Order Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📦 Statistik Box Order</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ boxOrderStats.today_orders || 0 }}</p>
                    <p class="text-sm text-gray-600">Order Hari Ini</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ formatMoney(boxOrderStats.today_revenue || 0) }}</p>
                    <p class="text-sm text-gray-600">Revenue Hari Ini</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">{{ boxOrderStats.month_orders || 0 }}</p>
                    <p class="text-sm text-gray-600">Order Bulan Ini</p>
                </div>
                <div class="text-center p-4 bg-orange-50 rounded-lg">
                    <p class="text-2xl font-bold text-orange-600">{{ formatMoney(boxOrderStats.month_revenue || 0) }}</p>
                    <p class="text-sm text-gray-600">Revenue Bulan Ini</p>
                </div>
            </div>
        </div>

        <!-- Session Detail Modal -->
        <Teleport to="body">
            <div 
                v-if="showSessionModal" 
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeSessionModal"
            >
                <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
                        <div>
                            <h3 class="font-semibold text-gray-800">Detail Sesi Toko</h3>
                            <p class="text-sm text-gray-500">{{ selectedSession?.user?.name }}</p>
                        </div>
                        <button @click="closeSessionModal" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <div class="p-4" v-if="selectedSession">
                        <!-- Session Info -->
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <span class="text-gray-500">Dibuka:</span>
                                <span class="font-medium ml-1">{{ new Date(selectedSession.opened_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span :class="['ml-1 px-2 py-0.5 text-xs rounded-full', getStatusBadge(selectedSession.status)]">
                                    {{ getStatusText(selectedSession.status) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">Kas Awal:</span>
                                <span class="font-medium ml-1">{{ formatMoney(selectedSession.opening_cash) }}</span>
                            </div>
                            <div v-if="selectedSession.status === 'closed'">
                                <span class="text-gray-500">Ditutup:</span>
                                <span class="font-medium ml-1">{{ new Date(selectedSession.closed_at).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>

                        <!-- Consignment Items -->
                        <h4 class="font-medium text-gray-800 mb-2">Barang Konsinyasi</h4>
                        <div v-if="selectedSession.consignments?.length === 0" class="text-gray-500 text-center py-4">
                            Tidak ada barang
                        </div>
                        <div v-else class="border rounded-lg overflow-hidden mb-4">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="text-left px-3 py-2">Produk</th>
                                        <th class="text-center px-3 py-2">Awal</th>
                                        <th class="text-center px-3 py-2">Terjual</th>
                                        <th class="text-center px-3 py-2">Sisa</th>
                                        <th class="text-right px-3 py-2">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in selectedSession.consignments" :key="item.id" class="border-t">
                                        <td class="px-3 py-2">{{ item.product_name }}</td>
                                        <td class="text-center px-3 py-2">{{ item.qty_initial }}</td>
                                        <td class="text-center px-3 py-2 text-green-600 font-medium">{{ item.qty_sold }}</td>
                                        <td class="text-center px-3 py-2">{{ item.qty_remaining }}</td>
                                        <td class="text-right px-3 py-2 font-medium">{{ formatMoney(item.subtotal_income) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 font-medium">
                                    <tr class="border-t">
                                        <td class="px-3 py-2">Total</td>
                                        <td class="text-center px-3 py-2">{{ selectedSession.consignments?.reduce((s, c) => s + c.qty_initial, 0) }}</td>
                                        <td class="text-center px-3 py-2 text-green-600">{{ selectedSession.consignments?.reduce((s, c) => s + c.qty_sold, 0) }}</td>
                                        <td class="text-center px-3 py-2">{{ selectedSession.consignments?.reduce((s, c) => s + c.qty_remaining, 0) }}</td>
                                        <td class="text-right px-3 py-2 text-blue-600">{{ formatMoney(selectedSession.consignments?.reduce((s, c) => s + c.subtotal_income, 0)) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button @click="closeSessionModal" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Tutup</button>
                            <a
                                v-if="selectedSession.status === 'closed'"
                                :href="`/admin/reports/session/${selectedSession.id}`"
                                target="_blank"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                📄 Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Order Detail Modal -->
        <Teleport to="body">
            <div 
                v-if="showOrderModal" 
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeOrderModal"
            >
                <div class="bg-white rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white">
                        <div>
                            <h3 class="font-semibold text-gray-800">Detail Box Order</h3>
                            <p class="text-sm text-gray-500">{{ selectedOrder?.customer_name }}</p>
                        </div>
                        <button @click="closeOrderModal" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <div class="p-4" v-if="selectedOrder">
                        <!-- Order Info -->
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <span class="text-gray-500">Order ID:</span>
                                <span class="font-medium ml-1">#{{ selectedOrder.id }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span :class="['ml-1 px-2 py-0.5 text-xs rounded-full', getStatusBadge(selectedOrder.status)]">
                                    {{ getStatusText(selectedOrder.status) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">Pickup:</span>
                                <span class="font-medium ml-1">{{ new Date(selectedOrder.pickup_datetime).toLocaleString('id-ID') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Qty Box:</span>
                                <span class="font-medium ml-1">{{ selectedOrder.quantity }}</span>
                            </div>
                        </div>

                        <!-- Cancellation Reason -->
                        <div v-if="selectedOrder.status === 'cancelled' && selectedOrder.cancellation_reason" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800">
                                <span class="font-medium">Alasan Pembatalan:</span> {{ selectedOrder.cancellation_reason }}
                            </p>
                        </div>

                        <!-- Order Items -->
                        <h4 class="font-medium text-gray-800 mb-2">Item Order</h4>
                        <div v-if="selectedOrder.items?.length === 0" class="text-gray-500 text-center py-4">
                            Tidak ada item
                        </div>
                        <div v-else class="border rounded-lg overflow-hidden mb-4">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="text-left px-3 py-2">Produk</th>
                                        <th class="text-center px-3 py-2">Qty</th>
                                        <th class="text-right px-3 py-2">Harga</th>
                                        <th class="text-right px-3 py-2">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in selectedOrder.items" :key="item.id" class="border-t">
                                        <td class="px-3 py-2">{{ item.product_name }}</td>
                                        <td class="text-center px-3 py-2">{{ item.quantity }}</td>
                                        <td class="text-right px-3 py-2">{{ formatMoney(item.unit_price) }}</td>
                                        <td class="text-right px-3 py-2 font-medium">{{ formatMoney(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 font-medium">
                                    <tr class="border-t">
                                        <td colspan="3" class="px-3 py-2 text-right">Total:</td>
                                        <td class="text-right px-3 py-2 text-green-600 text-lg">{{ formatMoney(selectedOrder.total_price) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Payment Proof -->
                        <div v-if="selectedOrder.payment_proof_path" class="mb-4">
                            <h4 class="font-medium text-gray-800 mb-2">Bukti Pembayaran</h4>
                            <a 
                                :href="`/storage/${selectedOrder.payment_proof_path}`" 
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm"
                            >
                                📷 Lihat Bukti Pembayaran
                            </a>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button @click="closeOrderModal" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Tutup</button>
                            <a
                                :href="`/pos/box/${selectedOrder.id}/receipt`"
                                target="_blank"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                📄 Download Kwitansi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

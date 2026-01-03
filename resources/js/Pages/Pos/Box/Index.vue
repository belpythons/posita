<!-- Rivaldi | 202312050 -->
<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatMoney } from '@/utils/formatMoney';

// Lucide icons
import {
    Plus,
    Clock,
    Package,
    CalendarDays,
    CheckCircle,
    XCircle,
    DollarSign,
    AlertCircle,
    FileText,
    Image,
    Upload,
    PenSquare,
    ChevronDown,
    Bell,
    Sparkles,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps({
    upcomingOrders: {
        type: Array,
        default: () => [],
    },
    todayOrders: {
        type: Array,
        default: () => [],
    },
    hasActiveSession: {
        type: Boolean,
        default: false,
    },
    activeSession: {
        type: Object,
        default: null,
    },
});

// Session info for sidebar status
const sessionInfo = computed(() => ({
  shiftName: props.activeSession?.shift_name ?? null,
  openingBalance: props.activeSession?.opening_cash ?? 0,
  isActive: props.hasActiveSession,
  openedAt: props.activeSession?.opened_at ?? null,
}));

// Countdown timer state
const countdowns = ref({});
let intervalId = null;

// Track which orders have been notified (to avoid repeated alerts)
const notifiedOrders = ref(new Set());

// Modal state
const showStatusModal = ref(false);
const selectedOrder = ref(null);
const statusForm = useForm({
    status: '',
    payment_proof: null,
    cancellation_reason: '',
});

// File input ref
const fileInputRef = ref(null);

// Calculate countdown for each order
const updateCountdowns = () => {
    const now = new Date();
    props.upcomingOrders.forEach(order => {
        const pickup = new Date(order.pickup_datetime);
        const diff = pickup - now;

        if (diff <= 0) {
            countdowns.value[order.id] = { expired: true, text: 'Sudah lewat' };
            
            // Auto-notification when countdown reaches zero
            if (!notifiedOrders.value.has(order.id) && order.status === 'pending') {
                notifiedOrders.value.add(order.id);
                showPickupNotification(order);
            }
        } else {
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            countdowns.value[order.id] = {
                expired: false,
                days,
                hours,
                minutes,
                seconds,
                text: days > 0 
                    ? `${days}h ${hours}j ${minutes}m` 
                    : `${hours}j ${minutes}m ${seconds}d`
            };
        }
    });
};

// Show pickup notification modal
const showPickupNotification = (order) => {
    selectedOrder.value = order;
    showNotificationModal.value = true;
};

// Notification modal state
const showNotificationModal = ref(false);

const closeNotificationModal = () => {
    showNotificationModal.value = false;
    selectedOrder.value = null;
};

const confirmFromNotification = () => {
    closeNotificationModal();
    if (selectedOrder.value) {
        openStatusModal(selectedOrder.value);
    }
};

onMounted(() => {
    updateCountdowns();
    intervalId = setInterval(updateCountdowns, 1000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

const getStatusBadge = (status) => {
    const badges = {
        pending: 'bg-amber-50 text-amber-700 border-amber-200',
        paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        completed: 'bg-sky-50 text-sky-700 border-sky-200',
        cancelled: 'bg-red-50 text-red-700 border-red-200',
    };
    return badges[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Menunggu',
        paid: 'Lunas',
        completed: 'Selesai',
        cancelled: 'Batal',
    };
    return texts[status] || status;
};

// Open status change modal
const openStatusModal = (order) => {
    selectedOrder.value = order;
    statusForm.status = order.status;
    statusForm.payment_proof = null;
    statusForm.cancellation_reason = '';
    showStatusModal.value = true;
};

// Close modal
const closeStatusModal = () => {
    showStatusModal.value = false;
    selectedOrder.value = null;
    statusForm.reset();
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

// Handle file selection
const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        statusForm.payment_proof = file;
    }
};

// Check if payment proof is required
const requiresPaymentProof = computed(() => {
    if (!selectedOrder.value) return false;
    return ['paid', 'completed'].includes(statusForm.status) && 
           !selectedOrder.value.payment_proof_path;
});

// Check if cancellation reason is required
const requiresCancellationReason = computed(() => {
    return statusForm.status === 'cancelled';
});

// Check if form is valid
const isFormValid = computed(() => {
    if (statusForm.status === selectedOrder.value?.status) return false;
    if (requiresPaymentProof.value && !statusForm.payment_proof) return false;
    if (requiresCancellationReason.value && !statusForm.cancellation_reason.trim()) return false;
    return true;
});

// Submit status change
const submitStatusChange = () => {
    const formData = new FormData();
    formData.append('status', statusForm.status);
    formData.append('_method', 'PATCH');
    
    if (statusForm.payment_proof) {
        formData.append('payment_proof', statusForm.payment_proof);
    }
    
    if (statusForm.cancellation_reason) {
        formData.append('cancellation_reason', statusForm.cancellation_reason);
    }

    router.post(`/pos/box/${selectedOrder.value.id}/status`, formData, {
        forceFormData: true,
        onSuccess: () => {
            closeStatusModal();
        },
        onError: (errors) => {
            console.error('Status update failed:', errors);
        },
    });
};

// Available status options
const statusOptions = [
    { value: 'pending', label: 'Menunggu', icon: 'clock', description: 'Order belum dibayar' },
    { value: 'paid', label: 'Lunas', icon: 'currency', description: 'Pembayaran sudah diterima' },
    { value: 'completed', label: 'Selesai', icon: 'check', description: 'Order sudah diambil' },
    { value: 'cancelled', label: 'Batal', icon: 'x', description: 'Order dibatalkan' },
];
</script>

<template>
    <Head title="Order Box" />

    <EmployeeLayout :session-info="sessionInfo">
        <template #header>
            <div class="flex items-center gap-2">
                <Package class="w-6 h-6 text-gray-600" />
                <h2 class="text-lg font-semibold text-gray-800">Order Box</h2>
            </div>
        </template>

        <!-- Main Container with Fixed Height - No Page Scroll -->
        <div class="h-[calc(100vh-180px)] lg:h-[calc(100vh-130px)] flex flex-col gap-3 overflow-hidden">
            <!-- Create New Order Button - Green Gradient -->
            <Link
                href="/pos/box/create"
                class="flex-shrink-0 flex items-center justify-center gap-2 w-full bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white py-4 rounded-2xl font-semibold text-center shadow-xl shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:scale-[1.01] transition-all duration-300 group"
            >
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center group-hover:bg-white/30 transition-colors">
                    <Plus class="w-5 h-5" />
                </div>
                <span class="text-lg">Buat Order Baru</span>
            </Link>

            <!-- Two Column Layout -->
            <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 gap-4 min-h-0">
                <!-- Left Column: Countdown + Today Orders -->
                <div class="flex flex-col gap-4 min-h-0">
                    <!-- Countdown Widget - Orange Gradient -->
                    <div v-if="upcomingOrders.length > 0" class="flex-shrink-0 bg-gradient-to-br from-orange-500 via-orange-600 to-amber-500 rounded-2xl shadow-xl shadow-orange-500/25 overflow-hidden">
                        <div class="px-5 py-4 flex items-center gap-3 border-b border-white/10">
                            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <Clock class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h3 class="font-bold text-white">Countdown Terdekat</h3>
                                <p class="text-white/70 text-xs">{{ upcomingOrders.length }} order mendatang</p>
                            </div>
                        </div>
                        <div class="p-4 max-h-48 overflow-y-auto custom-scrollbar space-y-2">
                            <div
                                v-for="order in upcomingOrders.slice(0, 5)"
                                :key="order.id"
                                class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex justify-between items-center hover:bg-white/20 transition-colors cursor-pointer"
                                @click="openStatusModal(order)"
                            >
                                <div>
                                    <p class="font-semibold text-white">{{ order.customer_name }}</p>
                                    <p class="text-sm text-white/70">{{ order.items?.length || 1 }} item</p>
                                </div>
                                <div class="text-right">
                                    <p 
                                        class="font-bold text-xl"
                                        :class="countdowns[order.id]?.expired ? 'text-red-300' : 'text-white'"
                                    >
                                        {{ countdowns[order.id]?.text || '...' }}
                                    </p>
                                    <p class="text-xs text-white/70">
                                        {{ new Date(order.pickup_datetime).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Orders -->
                    <div class="flex-1 min-h-0 bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col overflow-hidden">
                        <div class="flex-shrink-0 px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                        <Package class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Pengambilan Hari Ini</h3>
                                        <p class="text-xs text-gray-500">{{ todayOrders.length }} order</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                                    Today
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                            <div v-if="todayOrders.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400">
                                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <Package class="w-10 h-10 opacity-50" />
                                </div>
                                <p class="font-medium">Tidak ada order hari ini</p>
                                <p class="text-sm text-gray-400">Semua order sudah selesai!</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="order in todayOrders"
                                    :key="order.id"
                                    class="group bg-gradient-to-r from-gray-50 to-white border border-gray-100 rounded-xl p-4 hover:shadow-lg hover:border-emerald-200 hover:-translate-y-0.5 transition-all duration-300"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <p class="font-bold text-gray-800">{{ order.customer_name }}</p>
                                            <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                                                <Clock class="w-4 h-4" />
                                                {{ new Date(order.pickup_datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                                            </p>
                                        </div>
                                        <button
                                            @click="openStatusModal(order)"
                                            :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border cursor-pointer hover:scale-105 transition-all duration-200', getStatusBadge(order.status)]"
                                        >
                                            <component 
                                                :is="order.status === 'completed' ? CheckCircle : order.status === 'cancelled' ? XCircle : order.status === 'paid' ? DollarSign : Clock" 
                                                class="w-3.5 h-3.5" 
                                            />
                                            {{ getStatusText(order.status) }}
                                        </button>
                                    </div>
                                    
                                    <!-- Order Items -->
                                    <div v-if="order.items?.length > 0" class="mb-3 space-y-1 text-sm">
                                        <div v-for="item in order.items" :key="item.id" class="flex justify-between text-gray-600">
                                            <span>{{ item.product_name }} × {{ item.quantity }}</span>
                                            <span class="font-medium">{{ formatMoney(item.subtotal) }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Cancellation Reason -->
                                    <div v-if="order.status === 'cancelled' && order.cancellation_reason" class="mb-3 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-700 flex items-start gap-2">
                                        <AlertCircle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <span class="font-medium">Alasan batal:</span> {{ order.cancellation_reason }}
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                        <span class="font-bold text-lg bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">{{ formatMoney(order.total_price) }}</span>
                                        <a
                                            :href="`/pos/box/${order.id}/receipt`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium hover:underline"
                                        >
                                            <FileText class="w-4 h-4" />
                                            Kwitansi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Upcoming Orders - Green Theme -->
                <div class="min-h-0 bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col overflow-hidden">
                    <div class="flex-shrink-0 px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                    <CalendarDays class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Order Mendatang</h3>
                                    <p class="text-xs text-gray-500">{{ upcomingOrders.length }} order</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                                Upcoming
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                        <div v-if="upcomingOrders.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <CalendarDays class="w-10 h-10 opacity-50" />
                            </div>
                            <p class="font-medium">Tidak ada order mendatang</p>
                            <p class="text-sm text-gray-400">Buat order baru untuk memulai!</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="order in upcomingOrders"
                                :key="order.id"
                                class="group bg-gradient-to-r from-gray-50 to-white border border-gray-100 rounded-xl p-4 hover:shadow-lg hover:border-indigo-200 hover:-translate-y-0.5 transition-all duration-300"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-800 truncate">{{ order.customer_name }}</p>
                                        <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            <CalendarDays class="w-4 h-4 flex-shrink-0" />
                                            <span class="truncate">{{ new Date(order.pickup_datetime).toLocaleString('id-ID', { 
                                                weekday: 'short', 
                                                day: 'numeric', 
                                                month: 'short',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-3">
                                        <button
                                            @click="openStatusModal(order)"
                                            :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border cursor-pointer hover:scale-105 transition-all duration-200', getStatusBadge(order.status)]"
                                        >
                                            <component 
                                                :is="order.status === 'completed' ? CheckCircle : order.status === 'cancelled' ? XCircle : order.status === 'paid' ? DollarSign : Clock" 
                                                class="w-3.5 h-3.5" 
                                            />
                                            {{ getStatusText(order.status) }}
                                        </button>
                                        <p 
                                            class="text-sm font-bold mt-2"
                                            :class="countdowns[order.id]?.expired ? 'text-red-500' : 'text-indigo-600'"
                                        >
                                            {{ countdowns[order.id]?.text || '...' }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Order Items Summary -->
                                <div v-if="order.items?.length > 0" class="mb-3 text-sm text-gray-600 truncate">
                                    {{ order.items.map(i => i.product_name).join(', ') }}
                                </div>
                                
                                <!-- Cancellation Reason -->
                                <div v-if="order.status === 'cancelled' && order.cancellation_reason" class="mb-3 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-700 flex items-start gap-2">
                                    <AlertCircle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <span class="font-medium">Alasan batal:</span> {{ order.cancellation_reason }}
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                    <span class="font-bold text-lg bg-gradient-to-r from-indigo-600 to-primary bg-clip-text text-transparent">{{ formatMoney(order.total_price) }}</span>
                                    <a
                                        :href="`/pos/box/${order.id}/receipt`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium hover:underline"
                                    >
                                        <FileText class="w-4 h-4" />
                                        Kwitansi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Change Modal -->
        <Teleport to="body">
            <div 
                v-if="showStatusModal" 
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="closeStatusModal"
            >
                <div class="bg-white rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl animate-scale-in">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-muted rounded-t-3xl">
                        <h3 class="font-bold text-xl text-gray-800">Ubah Status Order</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ selectedOrder?.customer_name }}</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- Status Selection -->
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-gray-700">Pilih Status</label>
                            <div class="space-y-2">
                                <button
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    @click="statusForm.status = option.value"
                                    :class="[
                                        'w-full p-4 rounded-2xl border-2 text-left flex items-center gap-4 transition-all duration-300',
                                        statusForm.status === option.value 
                                            ? 'border-indigo-500 bg-gradient-to-r from-indigo-50 to-muted shadow-lg shadow-indigo-500/10 scale-[1.02]' 
                                            : 'border-gray-100 hover:border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <div :class="[
                                        'w-12 h-12 rounded-xl flex items-center justify-center shadow-lg',
                                        option.value === 'pending' ? 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30' :
                                        option.value === 'paid' ? 'bg-gradient-to-br from-emerald-400 to-teal-500 shadow-emerald-500/30' :
                                        option.value === 'completed' ? 'bg-gradient-to-br from-sky-400 to-blue-500 shadow-sky-500/30' :
                                        'bg-gradient-to-br from-red-400 to-rose-500 shadow-red-500/30'
                                    ]">
                                        <Clock v-if="option.icon === 'clock'" class="w-6 h-6 text-white" />
                                        <DollarSign v-else-if="option.icon === 'currency'" class="w-6 h-6 text-white" />
                                        <CheckCircle v-else-if="option.icon === 'check'" class="w-6 h-6 text-white" />
                                        <XCircle v-else class="w-6 h-6 text-white" />
                                    </div>
                                    <div class="flex-1">
                                        <span class="font-bold block text-gray-800">{{ option.label }}</span>
                                        <span class="text-xs text-gray-500">{{ option.description }}</span>
                                    </div>
                                    <div v-if="statusForm.status === option.value" class="text-indigo-500">
                                        <CheckCircle2 class="w-7 h-7" />
                                    </div>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Payment Proof Upload (for paid/completed) -->
                        <div v-if="requiresPaymentProof" class="space-y-3">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <Image class="w-5 h-5" />
                                Upload Bukti Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-indigo-400 hover:bg-gradient-to-br hover:from-indigo-50 hover:to-muted transition-all duration-300 cursor-pointer group">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="hidden"
                                    id="payment-proof-input"
                                />
                                <label for="payment-proof-input" class="cursor-pointer block">
                                    <div v-if="!statusForm.payment_proof" class="text-gray-500">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-100 transition-colors">
                                            <Upload class="w-8 h-8 text-gray-400 group-hover:text-indigo-500" />
                                        </div>
                                        <p class="text-sm font-medium">Klik untuk upload gambar</p>
                                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, max 5MB</p>
                                    </div>
                                    <div v-else class="text-emerald-600">
                                        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <CheckCircle2 class="w-8 h-8" />
                                        </div>
                                        <p class="text-sm font-semibold">{{ statusForm.payment_proof.name }}</p>
                                        <p class="text-xs text-gray-400 mt-1">Klik untuk ganti</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="selectedOrder?.payment_proof_path" class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                                <CheckCircle class="w-4 h-4" />
                                Bukti pembayaran sudah ada sebelumnya
                            </p>
                        </div>
                        
                        <!-- Cancellation Reason (for cancelled) -->
                        <div v-if="requiresCancellationReason" class="space-y-3">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <PenSquare class="w-5 h-5" />
                                Alasan Pembatalan <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="statusForm.cancellation_reason"
                                class="w-full border-2 border-gray-200 rounded-2xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200"
                                rows="3"
                                placeholder="Masukkan alasan pembatalan order..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 flex gap-3">
                        <button
                            @click="closeStatusModal"
                            class="flex-1 py-3.5 border-2 border-gray-200 rounded-2xl text-gray-700 font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitStatusChange"
                            :disabled="statusForm.processing || !isFormValid"
                            class="flex-1 py-3.5 bg-gradient-to-r from-indigo-600 to-primary text-white rounded-2xl font-semibold hover:from-indigo-700 hover:to-primary disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-indigo-500/30"
                        >
                            {{ statusForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Pickup Deadline Notification Modal -->
        <Teleport to="body">
            <div 
                v-if="showNotificationModal" 
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            >
                <div class="bg-white rounded-3xl w-full max-w-sm text-center shadow-2xl animate-scale-in">
                    <div class="p-8">
                        <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl shadow-amber-500/30 animate-pulse">
                            <Bell class="w-12 h-12 text-white" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Waktu Pengambilan Tiba!</h3>
                        <p class="text-gray-600 mb-6">
                            Order untuk <strong class="text-gray-800">{{ selectedOrder?.customer_name }}</strong> sudah waktunya diambil.
                        </p>
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-4">
                            <p class="text-sm text-amber-800 flex items-center justify-center gap-2 font-medium">
                                <Sparkles class="w-5 h-5" />
                                Segera konfirmasi status order ini.
                            </p>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 flex gap-3">
                        <button
                            @click="closeNotificationModal"
                            class="flex-1 py-3.5 border-2 border-gray-200 rounded-2xl text-gray-700 font-semibold hover:bg-gray-50 transition-colors duration-200"
                        >
                            Nanti
                        </button>
                        <button
                            @click="confirmFromNotification"
                            class="flex-1 py-3.5 bg-gradient-to-r from-indigo-600 to-primary text-white rounded-2xl font-semibold hover:from-indigo-700 hover:to-primary transition-all duration-200 shadow-lg shadow-indigo-500/30"
                        >
                            Konfirmasi Status
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </EmployeeLayout>
</template>

<style scoped>
/* Prevent page scroll */
:deep(.min-h-screen) {
    min-height: 100vh;
    max-height: 100vh;
    overflow: hidden;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* Animation */
@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}
</style>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatMoney } from '@/utils/formatMoney';

const props = defineProps({
    upcomingOrders: {
        type: Array,
        default: () => [],
    },
    todayOrders: {
        type: Array,
        default: () => [],
    },
});

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
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        completed: 'bg-blue-100 text-blue-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
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
    { value: 'pending', label: 'Menunggu', icon: '⏳', description: 'Order belum dibayar' },
    { value: 'paid', label: 'Lunas', icon: '💰', description: 'Pembayaran sudah diterima' },
    { value: 'completed', label: 'Selesai', icon: '✅', description: 'Order sudah diambil' },
    { value: 'cancelled', label: 'Batal', icon: '❌', description: 'Order dibatalkan' },
];
</script>

<template>
    <Head title="Order Box" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Order Box</h2>
        </template>

        <div class="space-y-6">
            <!-- Create New Order Button -->
            <Link
                href="/pos/box/create"
                class="block w-full bg-green-600 text-white py-3 rounded-lg font-medium text-center hover:bg-green-700"
            >
                + Buat Order Baru
            </Link>

            <!-- Countdown Widget -->
            <div v-if="upcomingOrders.length > 0" class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-4 text-white">
                <h3 class="font-semibold mb-3">⏰ Order Mendatang</h3>
                <div class="space-y-2">
                    <div
                        v-for="order in upcomingOrders.slice(0, 3)"
                        :key="order.id"
                        class="bg-white bg-opacity-20 rounded-lg p-3 flex justify-between items-center"
                    >
                        <div>
                            <p class="font-medium">{{ order.customer_name }}</p>
                            <p class="text-sm opacity-80">{{ order.items?.length || 1 }} item</p>
                        </div>
                        <div class="text-right">
                            <p 
                                class="font-bold text-lg"
                                :class="countdowns[order.id]?.expired ? 'text-red-300' : ''"
                            >
                                {{ countdowns[order.id]?.text || '...' }}
                            </p>
                            <p class="text-xs opacity-80">
                                {{ new Date(order.pickup_datetime).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b">
                    <h3 class="font-semibold text-gray-800">📦 Pengambilan Hari Ini</h3>
                </div>
                <div class="p-4">
                    <div v-if="todayOrders.length === 0" class="text-center py-8 text-gray-500">
                        Tidak ada order untuk hari ini
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="order in todayOrders"
                            :key="order.id"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium">{{ order.customer_name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ new Date(order.pickup_datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                                    </p>
                                </div>
                                <button
                                    @click="openStatusModal(order)"
                                    :class="['px-2 py-1 text-xs rounded-full cursor-pointer hover:opacity-80 transition', getStatusBadge(order.status)]"
                                >
                                    {{ getStatusText(order.status) }} ▾
                                </button>
                            </div>
                            
                            <!-- Order Items -->
                            <div v-if="order.items?.length > 0" class="mb-2 text-sm text-gray-600">
                                <div v-for="item in order.items" :key="item.id" class="flex justify-between">
                                    <span>{{ item.product_name }} x{{ item.quantity }}</span>
                                    <span>{{ formatMoney(item.subtotal) }}</span>
                                </div>
                            </div>
                            
                            <!-- Cancellation Reason -->
                            <div v-if="order.status === 'cancelled' && order.cancellation_reason" class="mb-2 p-2 bg-red-50 rounded text-sm text-red-700">
                                <span class="font-medium">Alasan batal:</span> {{ order.cancellation_reason }}
                            </div>
                            
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="font-bold text-green-600">{{ formatMoney(order.total_price) }}</span>
                                <a
                                    :href="`/pos/box/${order.id}/receipt`"
                                    target="_blank"
                                    class="text-sm text-blue-600 hover:text-blue-800"
                                >
                                    📄 Kwitansi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Upcoming Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-4 py-3 border-b">
                    <h3 class="font-semibold text-gray-800">📅 Order Mendatang</h3>
                </div>
                <div class="p-4">
                    <div v-if="upcomingOrders.length === 0" class="text-center py-8 text-gray-500">
                        Tidak ada order mendatang
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="order in upcomingOrders"
                            :key="order.id"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium">{{ order.customer_name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ new Date(order.pickup_datetime).toLocaleString('id-ID', { 
                                            weekday: 'long', 
                                            day: 'numeric', 
                                            month: 'long',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <button
                                        @click="openStatusModal(order)"
                                        :class="['px-2 py-1 text-xs rounded-full cursor-pointer hover:opacity-80 transition', getStatusBadge(order.status)]"
                                    >
                                        {{ getStatusText(order.status) }} ▾
                                    </button>
                                    <p 
                                        class="text-sm font-medium mt-1"
                                        :class="countdowns[order.id]?.expired ? 'text-red-600' : 'text-blue-600'"
                                    >
                                        {{ countdowns[order.id]?.text || '...' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Order Items Summary -->
                            <div v-if="order.items?.length > 0" class="mb-2 text-sm text-gray-600">
                                {{ order.items.map(i => i.product_name).join(', ') }}
                            </div>
                            
                            <!-- Cancellation Reason -->
                            <div v-if="order.status === 'cancelled' && order.cancellation_reason" class="mb-2 p-2 bg-red-50 rounded text-sm text-red-700">
                                <span class="font-medium">Alasan batal:</span> {{ order.cancellation_reason }}
                            </div>
                            
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="font-bold text-green-600">{{ formatMoney(order.total_price) }}</span>
                                <a
                                    :href="`/pos/box/${order.id}/receipt`"
                                    target="_blank"
                                    class="text-sm text-blue-600 hover:text-blue-800"
                                >
                                    📄 Kwitansi
                                </a>
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
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click.self="closeStatusModal"
            >
                <div class="bg-white rounded-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
                    <div class="p-4 border-b">
                        <h3 class="font-semibold text-gray-800">Ubah Status Order</h3>
                        <p class="text-sm text-gray-500">{{ selectedOrder?.customer_name }}</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Status Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Pilih Status</label>
                            <div class="space-y-2">
                                <button
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    @click="statusForm.status = option.value"
                                    :class="[
                                        'w-full p-3 rounded-lg border-2 text-left flex items-center gap-3 transition',
                                        statusForm.status === option.value 
                                            ? 'border-blue-500 bg-blue-50' 
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <span class="text-xl">{{ option.icon }}</span>
                                    <div>
                                        <span class="font-medium block">{{ option.label }}</span>
                                        <span class="text-xs text-gray-500">{{ option.description }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Payment Proof Upload (for paid/completed) -->
                        <div v-if="requiresPaymentProof" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                📷 Upload Bukti Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="hidden"
                                    id="payment-proof-input"
                                />
                                <label for="payment-proof-input" class="cursor-pointer">
                                    <div v-if="!statusForm.payment_proof" class="text-gray-500">
                                        <p class="text-2xl mb-2">📤</p>
                                        <p class="text-sm">Klik untuk upload gambar</p>
                                        <p class="text-xs text-gray-400">JPG, PNG, max 5MB</p>
                                    </div>
                                    <div v-else class="text-green-600">
                                        <p class="text-2xl mb-2">✅</p>
                                        <p class="text-sm font-medium">{{ statusForm.payment_proof.name }}</p>
                                        <p class="text-xs text-gray-400">Klik untuk ganti</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="selectedOrder?.payment_proof_path" class="text-xs text-green-600">
                                ✓ Bukti pembayaran sudah ada sebelumnya
                            </p>
                        </div>
                        
                        <!-- Cancellation Reason (for cancelled) -->
                        <div v-if="requiresCancellationReason" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                📝 Alasan Pembatalan <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="statusForm.cancellation_reason"
                                class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                rows="3"
                                placeholder="Masukkan alasan pembatalan order..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="p-4 border-t flex gap-3">
                        <button
                            @click="closeStatusModal"
                            class="flex-1 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitStatusChange"
                            :disabled="statusForm.processing || !isFormValid"
                            class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            >
                <div class="bg-white rounded-lg w-full max-w-sm text-center">
                    <div class="p-6">
                        <div class="text-5xl mb-4">⏰</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Waktu Pengambilan Tiba!</h3>
                        <p class="text-gray-600 mb-4">
                            Order untuk <strong>{{ selectedOrder?.customer_name }}</strong> sudah waktunya diambil.
                        </p>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-yellow-800">
                                Segera konfirmasi status order ini.
                            </p>
                        </div>
                    </div>
                    <div class="p-4 border-t flex gap-3">
                        <button
                            @click="closeNotificationModal"
                            class="flex-1 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Nanti
                        </button>
                        <button
                            @click="confirmFromNotification"
                            class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Konfirmasi Status
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </EmployeeLayout>
</template>

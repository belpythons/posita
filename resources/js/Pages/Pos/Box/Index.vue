<script setup>
import EmployeeLayout from "@/Layouts/EmployeeLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { formatMoney } from "@/utils/formatMoney";

const props = defineProps({
    upcomingOrders: { type: Array, default: () => [] },
    todayOrders: { type: Array, default: () => [] },
});

const countdowns = ref({});
let intervalId = null;
const showStatusModal = ref(false);
const selectedOrder = ref(null);
const isUrgentAction = ref(false); // State untuk membedakan asal klik

const statusForm = useForm({
    status: "",
    cancellation_reason: "",
});

// Update label status sesuai permintaan
const statusOptions = [
    { value: "pending", label: "Belum Bayar", icon: "⏳" },
    { value: "paid", label: "Lunas", icon: "💰" },
    { value: "completed", label: "Selesai", icon: "✅" },
    { value: "cancelled", label: "Batal", icon: "❌" },
];

const updateCountdowns = () => {
    const now = new Date();
    props.upcomingOrders.forEach((order) => {
        const pickup = new Date(order.pickup_datetime);
        const diff = pickup - now;
        if (diff <= 0) {
            countdowns.value[order.id] = { text: "Lewat" };
        } else {
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            countdowns.value[order.id] = { text: `${hours}j ${minutes}m` };
        }
    });
};

onMounted(() => {
    updateCountdowns();
    intervalId = setInterval(updateCountdowns, 1000);
});

onUnmounted(() => clearInterval(intervalId));

const openStatusModal = (order, urgent = false) => {
    selectedOrder.value = order;
    isUrgentAction.value = urgent;
    statusForm.status = order.status;
    statusForm.cancellation_reason = order.cancellation_reason || "";
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    isUrgentAction.value = false;
    statusForm.reset();
};

const submitStatusChange = () => {
    statusForm.patch(`/pos/box/${selectedOrder.value.id}/status`, {
        onSuccess: () => closeStatusModal(),
        preserveScroll: true,
    });
};

// Fungsi khusus tombol IYA pada Urgent Order
const submitUrgentToToday = () => {
    statusForm.status = 'paid'; // Langsung set ke Lunas agar masuk Today board
    submitStatusChange();
};

const isFormValid = computed(() => {
    if (statusForm.status === selectedOrder.value?.status && !isUrgentAction.value) return false;
    if (statusForm.status === 'cancelled' && !statusForm.cancellation_reason?.trim()) return false;
    return statusForm.status !== "";
});

const getStatusBadge = (s) => ({
    pending: "bg-yellow-100 text-yellow-800",
    paid: "bg-green-100 text-green-800",
    completed: "bg-blue-100 text-blue-800",
    cancelled: "bg-red-100 text-red-800",
}[s] || "bg-gray-100");

</script>

<template>
    <Head title="Order Box Dashboard" />

    <EmployeeLayout class="h-screen overflow-hidden flex flex-col bg-[#F3F4F6]">
        <template #header>
            <div class="flex justify-between items-center w-full px-12 h-[70px] bg-white border-b-4 border-black shrink-0">
                <h2 class="text-2xl font-[900] uppercase italic">Order Dashboard</h2>
            </div>
        </template>

        <div class="flex-1 flex overflow-hidden p-6 gap-8">
            <div class="w-[30%] flex flex-col gap-6 h-[635px] shrink-0">
                <Link href="/pos/box/create" class="bg-[#FF3E3E] border-4 border-black p-8 rounded-2xl shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:shadow-none transition-all active:translate-x-1 active:translate-y-1 shrink-0 text-center">
                    <div class="flex items-center justify-between text-white font-black">
                        <span class="text-3xl italic uppercase">NEW ORDER</span>
                        <span class="text-5xl">＋</span>
                    </div>
                </Link>

                <div class="flex-1 bg-white border-4 border-black rounded-2xl flex flex-col overflow-hidden shadow-[8px_8px_0px_0px_rgba(79,70,229,1)]">
                    <div class="p-4 bg-indigo-600 border-b-4 border-black text-white font-black text-xs uppercase italic shrink-0">
                        Urgent Pickup (Antrian)
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        <div v-for="order in upcomingOrders" :key="order.id" 
                            @click="openStatusModal(order, true)"
                            class="bg-indigo-50 border-2 border-indigo-200 p-4 rounded-xl flex justify-between items-center shrink-0 cursor-pointer hover:bg-indigo-100 transition-all active:scale-95 group">
                            <div class="flex flex-col">
                                <span class="font-black text-[10px] uppercase truncate w-32 group-hover:text-indigo-700">{{ order.customer_name }}</span>
                                <span class="text-[8px] font-bold opacity-50 uppercase">{{ order.items?.length || 0 }} Items</span>
                            </div>
                            <span class="font-mono text-indigo-600 font-bold text-xs">{{ countdowns[order.id]?.text }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-[70%] flex flex-col gap-6 h-[635px] overflow-hidden">
                <div class="flex-1 flex flex-col bg-white border-4 border-black rounded-2xl shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
                    <div class="p-5 border-b-4 border-black bg-yellow-400 flex justify-between items-center shrink-0">
                        <h3 class="font-[900] text-sm uppercase italic">📦 PENGAMBILAN HARI INI</h3>
                        <span class="bg-black text-white px-4 py-1 rounded-full text-[10px] italic font-bold">{{ todayOrders.length }} ORDERS</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6 bg-slate-50 custom-scrollbar">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="order in todayOrders" :key="order.id" class="bg-white border-4 border-black rounded-2xl p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex flex-col h-[160px] hover:shadow-none transition-all">
                                <div class="flex justify-between items-start">
                                    <div class="bg-black text-white p-2 rounded-lg text-center rotate-[-2deg]">
                                        <p class="text-lg font-black italic">
                                            {{ new Date(order.pickup_datetime).toLocaleTimeString([], { hour: '2-digit', minute:'2-digit' }) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <h4 class="font-black text-[11px] uppercase truncate w-28">{{ order.customer_name }}</h4>
                                        <p class="text-[10px] font-black opacity-40">{{ formatMoney(order.total_price) }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-auto">
                                    <a :href="`/pos/box/${order.id}/receipt`" target="_blank" class="w-10 h-10 flex items-center justify-center border-4 border-black rounded-xl hover:bg-gray-100 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none">📄</a>
                                    <button @click="openStatusModal(order, false)" :class="['flex-1 h-10 font-black rounded-xl border-4 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-[10px] uppercase active:shadow-none', getStatusBadge(order.status)]">
                                        {{ order.status === 'pending' ? 'Belum Bayar' : order.status }} ▾
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showStatusModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999]" @click="closeStatusModal"></div>
            <div v-if="showStatusModal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md max-h-[90vh] bg-white border-[6px] border-black rounded-[40px] z-[1000] flex flex-col overflow-hidden shadow-[15px_15px_0px_0px_rgba(0,0,0,1)]">
                <div class="p-6 bg-yellow-400 border-b-[6px] border-black text-center shrink-0">
                    <h3 class="text-2xl font-[950] italic uppercase">Detail Order</h3>
                    <p class="text-[10px] font-black opacity-50 uppercase">{{ selectedOrder?.customer_name }}</p>
                </div>

                <div class="flex-1 overflow-y-auto p-8 space-y-6 custom-scrollbar">
                    <div class="bg-slate-50 border-4 border-black p-4 rounded-2xl shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                        <p class="text-[10px] font-black uppercase opacity-40 mb-2 italic">Daftar Belanja:</p>
                        <ul class="space-y-2">
                            <li v-for="item in selectedOrder?.items" :key="item.id" class="flex justify-between items-center border-b border-dashed border-gray-400 pb-1">
                                <span class="font-bold text-xs uppercase">{{ item.quantity }}x {{ item.product_name }}</span>
                                <span class="font-mono text-xs">{{ formatMoney(item.unit_price * item.quantity) }}</span>
                            </li>
                        </ul>
                        <div class="mt-4 pt-2 border-t-4 border-black flex justify-between font-[950] text-xl italic">
                            <span>TOTAL</span>
                            <span>{{ formatMoney(selectedOrder?.total_price) }}</span>
                        </div>
                    </div>

                    <div v-if="isUrgentAction" class="space-y-4">
                        <div class="bg-indigo-600 text-white p-4 rounded-2xl border-4 border-black text-center shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                            <p class="font-black uppercase italic text-sm">Masuk antrian hari ini?</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <button @click="submitUrgentToToday" class="bg-green-400 p-6 border-4 border-black rounded-3xl font-[950] text-xl italic hover:translate-x-1 hover:translate-y-1 hover:shadow-none shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-all">
                                IYA ✅
                            </button>
                            <button @click="statusForm.status = 'cancelled'" class="bg-red-400 p-6 border-4 border-black rounded-3xl font-[950] text-xl italic hover:translate-x-1 hover:translate-y-1 hover:shadow-none shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-all">
                                TIDAK ❌
                            </button>
                        </div>

                        <div v-if="statusForm.status === 'cancelled'" class="mt-4 animate-in fade-in slide-in-from-top-2 duration-300">
                            <label class="text-[10px] font-black uppercase mb-1 block">Alasan Pembatalan:</label>
                            <textarea v-model="statusForm.cancellation_reason" class="w-full border-4 border-black p-4 rounded-2xl font-bold text-sm" placeholder="Kenapa dibatalkan?"></textarea>
                            <button @click="submitStatusChange" :disabled="!statusForm.cancellation_reason" class="w-full mt-3 bg-black text-white p-4 rounded-2xl font-black uppercase text-xs">Konfirmasi Batal</button>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-2 gap-4">
                        <button v-for="opt in statusOptions" :key="opt.value" type="button"
                            @click="statusForm.status = opt.value"
                            :class="['p-5 rounded-3xl border-4 border-black flex flex-col items-center gap-2 transition-all active:scale-95', 
                            statusForm.status === opt.value ? 'bg-black text-white shadow-none translate-x-1 translate-y-1' : 'bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]']">
                            <span class="text-3xl">{{ opt.icon }}</span>
                            <span class="text-[11px] font-[900] uppercase">{{ opt.label }}</span>
                        </button>

                        <div v-if="statusForm.status === 'cancelled'" class="col-span-2 mt-4 animate-in fade-in slide-in-from-top-2 duration-300">
                            <label class="text-[10px] font-black uppercase mb-1 block">Alasan Pembatalan:</label>
                            <textarea v-model="statusForm.cancellation_reason" class="w-full border-4 border-black p-4 rounded-2xl font-bold text-sm" placeholder="Alasan batal..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="p-8 pt-0 flex gap-4 shrink-0 bg-white">
                    <button type="button" @click="closeStatusModal" class="flex-1 py-4 border-4 border-black rounded-2xl font-black uppercase text-xs hover:bg-gray-100 transition-all">Kembali</button>
                    <button v-if="!isUrgentAction" @click="submitStatusChange" :disabled="!isFormValid || statusForm.processing" class="flex-1 py-4 bg-black text-white border-4 border-black rounded-2xl font-black uppercase text-xs shadow-[6px_6px_0px_0px_rgba(0,0,0,0.3)] disabled:opacity-50">
                        Simpan
                    </button>
                </div>
            </div>
        </Teleport>
    </EmployeeLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #000; border: 2px solid #f1f1f1; border-radius: 5px; }
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: #000 #f1f1f1; }
</style>